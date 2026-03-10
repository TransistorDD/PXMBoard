<?php

declare(strict_types=1);

namespace PXMBoard\Tests\Performance\Phase;

use PXMBoard\Tests\Performance\Helper\DbHelper;
use PXMBoard\Tests\Performance\Helper\Progress;

/**
 * Base class for all seeder phases.
 *
 * Handles:
 *   - Reading / writing checkpoints (progress.json)
 *   - Skip logic for already completed phases
 *   - Helper methods for logging
 */
abstract class AbstractPhase
{
    public function __construct(
        protected DbHelper  $db,
        protected array     $config,
        protected Progress  $progress
    ) {
    }

    // -------------------------------------------------------------------------
    // Public API
    // -------------------------------------------------------------------------

    abstract public function getName(): string;

    abstract protected function execute(bool $resume): void;

    public function run(bool $resume = false): void
    {
        $checkpoint = $this->loadCheckpoint();

        if (!$resume && ($checkpoint[$this->getName()]['completed'] ?? false)) {
            echo "\n[{$this->getName()}] Already completed — skipping.\n";
            return;
        }

        echo "\n[{$this->getName()}] Starting phase...\n";
        $start = microtime(true);

        $this->execute($resume);
        $this->markCompleted();

        $elapsed = (int) (microtime(true) - $start);
        echo "\n[{$this->getName()}] Phase completed in " . $this->formatDuration($elapsed) . "\n";
    }

    // -------------------------------------------------------------------------
    // Checkpoint management
    // -------------------------------------------------------------------------

    protected function loadCheckpoint(): array
    {
        $file = $this->config['progress_file'];
        if (!file_exists($file)) {
            return [];
        }
        $data = json_decode(file_get_contents($file), true);
        return is_array($data) ? $data : [];
    }

    protected function getCheckpointValue(string $key, mixed $default = null): mixed
    {
        $checkpoint = $this->loadCheckpoint();
        return $checkpoint[$this->getName()][$key] ?? $default;
    }

    protected function saveCheckpointValue(string $key, mixed $value): void
    {
        $checkpoint                              = $this->loadCheckpoint();
        $checkpoint[$this->getName()][$key]      = $value;
        $checkpoint[$this->getName()]['updated'] = date('Y-m-d H:i:s');

        file_put_contents(
            $this->config['progress_file'],
            json_encode($checkpoint, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
    }

    protected function markCompleted(): void
    {
        $checkpoint                               = $this->loadCheckpoint();
        $checkpoint[$this->getName()]['completed'] = true;
        $checkpoint[$this->getName()]['finished']  = date('Y-m-d H:i:s');

        file_put_contents(
            $this->config['progress_file'],
            json_encode($checkpoint, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
    }

    // -------------------------------------------------------------------------
    // Helper methods
    // -------------------------------------------------------------------------

    protected function log(string $msg): void
    {
        $this->progress->log($msg);
    }

    protected function formatDuration(int $seconds): string
    {
        if ($seconds < 60) {
            return "{$seconds}s";
        }
        $m = intdiv($seconds, 60);
        $s = $seconds % 60;
        if ($m < 60) {
            return "{$m}m {$s}s";
        }
        $h = intdiv($m, 60);
        $m %= 60;
        return "{$h}h {$m}m";
    }
}
