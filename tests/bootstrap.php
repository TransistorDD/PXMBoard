<?php
/**
 * PHPUnit Bootstrap
 * Sets up test environment, constants, autoloading, and real test DB connection.
 *
 * Integration tests use a dedicated MySQL test database (pxmboard_test).
 * Configure connection via TEST_DB_* environment variables (see phpunit.xml).
 *
 * @author Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright Torsten Rentsch 2001 - 2026
 */
declare(strict_types=1);

// Composer Autoloader
require_once __DIR__ . '/../vendor/autoload.php';

use PXMBoard\Database\cDB;
use PXMBoard\Exception\cDatabaseException;
use PXMBoard\Search\cSearchEngineFactory;
use PXMBoard\Exception\cSearchEngineException;

// PXMBoard constants (same as pxmboard.php)
define('BASEDIR', dirname(__DIR__));
define('PUBLICDIR', BASEDIR . '/public');
define('SRCDIR', BASEDIR . '/src');

// Error reporting for tests
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Timezone
date_default_timezone_set('Europe/Berlin');

// -------------------------------------------------------------------------
// Test database connection
// -------------------------------------------------------------------------
$sTestDbHost = (string)($_ENV['TEST_DB_HOST'] ?? getenv('TEST_DB_HOST') ?: '127.0.0.1');
$sTestDbName = (string)($_ENV['TEST_DB_NAME'] ?? getenv('TEST_DB_NAME') ?: 'pxmboard_test');
$sTestDbUser = (string)($_ENV['TEST_DB_USER'] ?? getenv('TEST_DB_USER') ?: 'pxmboard_test');
$sTestDbPass = (string)($_ENV['TEST_DB_PASS'] ?? getenv('TEST_DB_PASS') ?: '');

$arrTestDb = [
    'type' => 'MySql',
    'host' => $sTestDbHost,
    'name' => $sTestDbName,
    'user' => $sTestDbUser,
    'pass' => $sTestDbPass,
];

try {
    cDB::getInstance($arrTestDb);
} catch (cDatabaseException $e) {
    echo "ERROR: Cannot connect to test database '{$sTestDbName}' on '{$sTestDbHost}'." . PHP_EOL;
    echo "       " . $e->getMessage() . PHP_EOL;
    echo "       Configure TEST_DB_* in phpunit.xml (see phpunit.xml.dist for template)." . PHP_EOL;
    exit(1);
}

// -------------------------------------------------------------------------
// Import schema if tables do not exist yet
// -------------------------------------------------------------------------
_importSchemaIfNeeded(cDB::getInstance());

// -------------------------------------------------------------------------
// Initialize search engine (MySql FULLTEXT, uses the already initialized DB)
// -------------------------------------------------------------------------
try {
    cSearchEngineFactory::getInstance(['type' => 'MySql']);
} catch (cSearchEngineException $e) {
    echo "WARNING: Could not initialize search engine: " . $e->getMessage() . PHP_EOL;
}

/**
 * Import the PXMBoard schema into the test database if it is not yet set up.
 * Uses IF NOT EXISTS / INSERT IGNORE semantics so it is safe to call repeatedly.
 * After a ROLLBACK in tests, the tables remain but fixture rows are gone.
 *
 * @param cDB $objDb Database connection
 * @return void
 */
function _importSchemaIfNeeded(cDB $objDb): void
{
    // Quick check: if pxm_configuration exists, schema is already present
    $objResult = $objDb->executeQuery("SHOW TABLES LIKE 'pxm_configuration'");
    if ($objResult && $objResult->getNextResultRowObject()) {
        $objResult->freeResult();
        return;
    }

    $sSqlFile = BASEDIR . '/install/sql/pxmboard-mysql.sql';
    if (!file_exists($sSqlFile)) {
        echo "WARNING: Schema file not found: {$sSqlFile}" . PHP_EOL;
        return;
    }

    $sContent = (string)file_get_contents($sSqlFile);

    // Split into individual statements by semicolon
    $arrStatements = explode(';', $sContent);

    foreach ($arrStatements as $sStatement) {
        // Strip # comments and whitespace
        $sStatement = (string)preg_replace('/^#.*$/m', '', $sStatement);
        $sStatement = trim($sStatement);

        if ($sStatement === '') {
            continue;
        }

        try {
            $objDb->executeQuery($sStatement);
        } catch (cDatabaseException $e) {
            // Ignore errors for statements like CREATE TABLE IF NOT EXISTS
            // or duplicate INSERT errors from subsequent bootstrap runs
        }
    }
}
