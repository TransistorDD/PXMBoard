<?php

declare(strict_types=1);

namespace PXMBoard\Database;

use PXMBoard\Exception\cDatabaseException;

/**
 * PDO-based database abstraction layer (singleton)
 *
 * Replaces the former cDBFactory + cDBMySql + cDBPostgreSql trio.
 * Supports MySQL/MariaDB (driver 'MySql') and PostgreSQL (driver 'PostgreSql').
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cDB
{
    /** @var cDB|null singleton instance */
    private static ?cDB $objInstance = null;

    /** @var \PDO|null PDO connection */
    private ?\PDO $m_pdo = null;

    /** @var string normalized driver name: 'mysql' or 'pgsql' */
    private string $m_sDbType = '';

    /** prevent direct instantiation */
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
        if (self::$objInstance === null) {
            if ($arrConfig === null) {
                throw new cDatabaseException('Database configuration required on first getInstance() call');
            }
            if (!isset($arrConfig['type'], $arrConfig['host'], $arrConfig['user'], $arrConfig['pass'], $arrConfig['name'])) {
                throw new cDatabaseException('Invalid database configuration: missing required keys');
            }
            self::$objInstance = new self();
            self::$objInstance->connectDBServer(
                (string) $arrConfig['type'],
                (string) $arrConfig['host'],
                (string) $arrConfig['user'],
                (string) $arrConfig['pass'],
                (string) $arrConfig['name']
            );
        }
        return self::$objInstance;
    }

    /**
     * Open a PDO connection based on the driver type
     *
     * @param string $sType  driver type string ('MySql' or 'PostgreSql')
     * @param string $sHost  hostname
     * @param string $sUser  username
     * @param string $sPass  password
     * @param string $sName  database name
     * @throws cDatabaseException on unsupported driver or connection failure
     */
    private function connectDBServer(string $sType, string $sHost, string $sUser, string $sPass, string $sName): void
    {
        $sNormalized = strtolower($sType);
        if (str_starts_with($sNormalized, 'mysql')) {
            $this->m_sDbType = 'mysql';
            $sDsn = "mysql:host=$sHost;dbname=$sName;charset=utf8mb4";
        } elseif (str_starts_with($sNormalized, 'pgsql') || str_starts_with($sNormalized, 'postgre')) {
            $this->m_sDbType = 'pgsql';
            $sDsn = "pgsql:host=$sHost;dbname=$sName";
        } else {
            throw new cDatabaseException("Unsupported database driver: $sType");
        }

        try {
            $this->m_pdo = new \PDO($sDsn, $sUser, $sPass, [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            ]);
        } catch (\PDOException $e) {
            throw new cDatabaseException('DB connection failed! Check the connection details.');
        }
    }

    /**
     * Returns the PDO instance, asserting it is connected.
     *
     * @throws cDatabaseException if not connected
     */
    private function pdo(): \PDO
    {
        if ($this->m_pdo === null) {
            throw new cDatabaseException('Database connection not established');
        }
        return $this->m_pdo;
    }

    /**
     * Execute a query
     *
     * @param string $sQuery   query string
     * @param int    $iLimit   max rows to return (0 = no limit)
     * @param int    $iOffset  rows to skip (0 = no offset)
     * @return cDBResultSet|null query result set
     * @throws cDatabaseException on failure
     */
    public function executeQuery(string $sQuery, int $iLimit = 0, int $iOffset = 0): ?cDBResultSet
    {
        if (empty($sQuery)) {
            throw new cDatabaseException('invalid querystring');
        }

        if ($iLimit > 0) {
            $sQuery .= " LIMIT $iLimit";
        }
        if ($iOffset > 0) {
            $sQuery .= " OFFSET $iOffset";
        }

        try {
            $stmt = $this->pdo()->query($sQuery);
            if (!$stmt instanceof \PDOStatement) {
                throw new cDatabaseException("couldn't execute query");
            }
            return new cDBResultSet($stmt);
        } catch (\PDOException $e) {
            throw new cDatabaseException("couldn't execute query");
        }
    }

    /**
     * Get the id generated from the previous insert operation
     *
     * @param string $sTableName  table name (used for PostgreSQL sequence lookup)
     * @param string $sColumnName column name (used for PostgreSQL sequence lookup)
     * @return int last insert id
     */
    public function getInsertId(string $sTableName, string $sColumnName): int
    {
        if ($this->m_sDbType === 'pgsql') {
            return (int) $this->pdo()->lastInsertId($sTableName . '_' . $sColumnName . '_seq');
        }
        return (int) $this->pdo()->lastInsertId();
    }

    /**
     * Escape and quote a string for use in a DB query
     *
     * @param string $sString string to quote
     * @return string quoted string (including surrounding single-quotes)
     */
    public function quote(string $sString): string
    {
        $sQuoted = $this->pdo()->quote($sString);
        return ($sQuoted !== false) ? $sQuoted : "'" . addslashes($sString) . "'";
    }

    /**
     * Close the database connection
     *
     * @return void
     */
    public function disconnectDBServer(): void
    {
        $this->m_pdo = null;
    }

    /**
     * Get the type of the connection
     *
     * @return string db type ('MySQL' or 'PostgreSQL')
     */
    public function getDBType(): string
    {
        return match($this->m_sDbType) {
            'mysql' => 'MySQL',
            'pgsql' => 'PostgreSQL',
            default => $this->m_sDbType,
        };
    }

    /**
     * Get the version of the db server
     *
     * @return string db version
     */
    public function getDBVersion(): string
    {
        try {
            $sVersion = $this->pdo()->getAttribute(\PDO::ATTR_SERVER_VERSION);
            return is_string($sVersion) ? $sVersion : '0.0.0';
        } catch (\PDOException $e) {
            return '0.0.0';
        }
    }

    /**
     * Get the column metatype (integer, string)
     *
     * @param string $sMetaType meta name of the column type ('integer' or 'string')
     * @param int    $iSize     size of the requested field
     * @return string db-dependent column type
     */
    public function getMetaType(string $sMetaType, int $iSize = -1): string
    {
        if ($this->m_sDbType === 'pgsql') {
            return match($sMetaType) {
                'integer' => 'INT4',
                'string'  => "VARCHAR($iSize)",
                default   => '',
            };
        }
        // MySQL (default)
        return match($sMetaType) {
            'integer' => 'INT',
            'string'  => ($iSize <= 255) ? "VARCHAR($iSize)" : 'TEXT',
            default   => '',
        };
    }
}
