<?php

namespace PXMBoard\Database;

use PXMBoard\Exception\cDatabaseException;

/**
 * factory class for db abstraction (singleton pattern)
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cDBFactory
{
    /**
     * @var cDB|null singleton instance of database connection
     */
    private static ?cDB $objInstance = null;

    /**
     * Private Constructor - prevents direct instantiation
     */
    private function __construct()
    {
    }

    /**
     * Get singleton instance of database connection
     *
     * @param array<string, mixed>|null $arrConfig database configuration (required on first call)
     *                              expected keys: type, host, user, pass, name
     * @return cDB database connection object
     * @throws cDatabaseException if configuration is invalid or connection fails
     */
    public static function getInstance(?array $arrConfig = null): cDB
    {
        // First call: initialize with config
        if (self::$objInstance === null) {
            if ($arrConfig === null) {
                throw new cDatabaseException('Database configuration required on first getInstance() call');
            }

            // Validate config
            if (!isset($arrConfig['type']) || !isset($arrConfig['host']) ||
               !isset($arrConfig['user']) || !isset($arrConfig['pass']) ||
               !isset($arrConfig['name'])) {
                throw new cDatabaseException('Invalid database configuration: missing required keys');
            }

            // Create database object
            self::$objInstance = self::getDBObject($arrConfig['type']);
            if (!self::$objInstance) {
                throw new cDatabaseException('Invalid database driver: ' . $arrConfig['type']);
            }

            // Connect to database
            if (!self::$objInstance->connectDBServer(
                $arrConfig['host'],
                $arrConfig['user'],
                $arrConfig['pass'],
                $arrConfig['name']
            )) {
                throw new cDatabaseException('Could not connect to database server');
            }
        }

        return self::$objInstance;
    }

    /**
     * Instantiates and returns the selected db object
     *
     * @param string $sDbType database type (MySql,PostgreSql,...)
     * @return object|null database connection object
     */
    private static function getDBObject(string $sDbType): ?object
    {
        $objDb = null;
        if (preg_match('/^[a-zA-Z]+$/', $sDbType)) {
            $sDbType = __NAMESPACE__ . '\\cDB' . $sDbType;
            $objDb = new $sDbType();
            return $objDb;
        }
        return $objDb;
    }
}
