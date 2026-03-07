#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * PXMBoard Seeder — CLI entry point
 *
 * Usage:
 *   php seeder/seeder.php                           All phases
 *   php seeder/seeder.php --phase=users,threads     Selected phases only
 *   php seeder/seeder.php --resume                  Resume from checkpoint
 *   php seeder/seeder.php --reset                   Delete checkpoint
 *   php seeder/seeder.php --dry-run                 Calculation only, no inserts
 *   php seeder/seeder.php --no-fulltext             Skip FULLTEXT index rebuild
 *
 * Phases (order matters):
 *   users       → populate pxm_user
 *   threads     → populate pxm_thread (skeleton)
 *   messages    → populate pxm_message + write thread_ranges.csv
 *   update      → update thread and user metadata via SQL
 *   readstatus  → populate pxm_message_read
 */

if (PHP_SAPI !== 'cli') {
    die("This script must be run from the CLI.\n");
}

// Remove time limit for long-running phases
set_time_limit(0);
ini_set('memory_limit', '512M');

// Autoload helpers and phases
require_once __DIR__ . '/Helper/DbHelper.php';
require_once __DIR__ . '/Helper/DataGenerator.php';
require_once __DIR__ . '/Helper/Progress.php';
require_once __DIR__ . '/Phase/AbstractPhase.php';
require_once __DIR__ . '/Phase/UserPhase.php';
require_once __DIR__ . '/Phase/ThreadPhase.php';
require_once __DIR__ . '/Phase/MessagePhase.php';
require_once __DIR__ . '/Phase/ThreadUpdatePhase.php';
require_once __DIR__ . '/Phase/ReadStatusPhase.php';

// Initialise DataGenerator (word lists)
DataGenerator::init();

// -------------------------------------------------------------------------
// Parse arguments
// -------------------------------------------------------------------------
$opts = getopt('', ['phase:', 'resume', 'dry-run', 'reset', 'no-fulltext', 'help']);

if (isset($opts['help'])) {
    echo file_get_contents(__FILE__);
    exit(0);
}

$allPhases     = ['users', 'threads', 'messages', 'update', 'readstatus'];
$selectedPhases = isset($opts['phase'])
    ? array_map('trim', explode(',', $opts['phase']))
    : $allPhases;

$resume      = isset($opts['resume']);
$dryRun      = isset($opts['dry-run']);
$reset       = isset($opts['reset']);

// -------------------------------------------------------------------------
// Load configuration
// -------------------------------------------------------------------------
$configFile = __DIR__ . '/config.php';
if (!file_exists($configFile)) {
    die("Configuration file not found: {$configFile}\n");
}

$config = require $configFile;

// Set start_timestamp if not configured
if (empty($config['seed']['start_timestamp'])) {
    $config['seed']['start_timestamp'] = strtotime('-5 years');
}

// -------------------------------------------------------------------------
// Reset
// -------------------------------------------------------------------------
if ($reset) {
    foreach ([$config['progress_file'], $config['thread_ranges_file']] as $f) {
        if (file_exists($f)) {
            unlink($f);
            echo "Deleted: {$f}\n";
        }
    }
    echo "Checkpoint reset.\n";
    exit(0);
}

// -------------------------------------------------------------------------
// Validate phase names
// -------------------------------------------------------------------------
$unknown = array_diff($selectedPhases, $allPhases);
if (!empty($unknown)) {
    echo "Unknown phase(s): " . implode(', ', $unknown) . "\n";
    echo "Valid phases: " . implode(', ', $allPhases) . "\n";
    exit(1);
}

// -------------------------------------------------------------------------
// Dry-Run
// -------------------------------------------------------------------------
if ($dryRun) {
    $seed = $config['seed'];
    $rp   = $config['read_pattern'];

    $avgMsgs       = ($seed['messages_per_thread_min'] + $seed['messages_per_thread_max']) / 2;
    $totalMessages = (int) ($seed['thread_count'] * $avgMsgs);
    $userCount     = $seed['user_count'];

    $heavyEntries    = (int) ($userCount * $rp['heavy_pct'] / 100) * $rp['heavy_thread_count'] * (int) $avgMsgs;
    $moderateEntries = (int) ($userCount * $rp['moderate_pct'] / 100) * ($rp['moderate_thread_count'] * (int) $avgMsgs + $rp['moderate_extra_messages']);
    $lurkerEntries   = (int) ($userCount * $rp['lurker_pct'] / 100) * $rp['lurker_message_count'];
    $totalRead       = $heavyEntries + $moderateEntries + $lurkerEntries;

    echo "\n=== Dry run: data calculation ===\n\n";
    echo "  User:                     " . number_format($userCount, 0, ',', '.') . "\n";
    echo "  Threads:                  " . number_format($seed['thread_count'], 0, ',', '.') . "\n";
    echo "  Messages (approx.):       " . number_format($totalMessages, 0, ',', '.') . "\n";
    echo "  Read entries (approx.):   " . number_format($totalRead, 0, ',', '.') . "\n";
    echo "\n  Selected phases:          " . implode(', ', $selectedPhases) . "\n";
    echo "  Bulk mode will be determined at startup.\n\n";
    exit(0);
}

// -------------------------------------------------------------------------
// Database connection
// -------------------------------------------------------------------------
$progress = new Progress();

try {
    $db = new DbHelper($config['db'], $config['batch']['commit_every']);
    $db->preflightCheck($config['seed']['board_ids']);
} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

// -------------------------------------------------------------------------
// Phase instances
// -------------------------------------------------------------------------
$phases = [
    'users'      => new UserPhase($db, $config, $progress),
    'threads'    => new ThreadPhase($db, $config, $progress),
    'messages'   => new MessagePhase($db, $config, $progress),
    'update'     => new ThreadUpdatePhase($db, $config, $progress),
    'readstatus' => new ReadStatusPhase($db, $config, $progress),
];

// -------------------------------------------------------------------------
// Enable bulk-insert optimisations
// -------------------------------------------------------------------------
$db->disableChecks();

// -------------------------------------------------------------------------
// Run phases
// -------------------------------------------------------------------------
$start = microtime(true);

foreach ($selectedPhases as $name) {
    try {
        $phases[$name]->run($resume);
    } catch (\Throwable $e) {
        echo "\n[ERROR in phase '{$name}']: " . $e->getMessage() . "\n";
        echo $e->getTraceAsString() . "\n";
        $db->enableChecks();
        exit(1);
    }
}

// -------------------------------------------------------------------------
// Clean up
// -------------------------------------------------------------------------
$db->enableChecks();

$totalElapsed = (int) (microtime(true) - $start);
$h = intdiv($totalElapsed, 3600);
$m = intdiv($totalElapsed % 3600, 60);
$s = $totalElapsed % 60;

echo "\n=== Seeder completed in {$h}h {$m}m {$s}s ===\n\n";
echo "Next steps for benchmarking:\n";
echo "  1. MySQL slow query log: SET GLOBAL slow_query_log=ON; SET GLOBAL long_query_time=0.1;\n";
echo "  2. Flush buffer pool:    FLUSH TABLES;\n";
echo "  3. Check thread list query with EXPLAIN ANALYZE\n\n";
