<?php

declare(strict_types=1);

namespace PXMBoard\Database;

/**
 * PDO-based database result set wrapper
 *
 * Replaces the former cDBMySqlResultSet and cDBPostgreSqlResultSet.
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cDBResultSet
{
    private ?\PDOStatement $m_stmt;

    /**
     * Constructor
     *
     * @param \PDOStatement $stmt PDO statement
     */
    public function __construct(\PDOStatement $stmt)
    {
        $this->m_stmt = $stmt;
    }

    /**
     * Get next result row as an object
     *
     * @return object|false result row or false when exhausted
     */
    public function getNextResultRowObject(): object|false
    {
        if ($this->m_stmt === null) {
            return false;
        }
        $row = $this->m_stmt->fetch(\PDO::FETCH_OBJ);
        return is_object($row) ? $row : false;
    }

    /**
     * Get next result row as an associative array
     *
     * @return array<string, mixed>|false result row or false when exhausted
     */
    public function getNextResultRowAssociative(): array|false
    {
        if ($this->m_stmt === null) {
            return false;
        }
        $row = $this->m_stmt->fetch(\PDO::FETCH_ASSOC);
        return is_array($row) ? $row : false;
    }

    /**
     * Get next result row as a numeric array
     *
     * @return array<mixed>|false result row or false when exhausted
     */
    public function getNextResultRowNumeric(): array|false
    {
        if ($this->m_stmt === null) {
            return false;
        }
        $row = $this->m_stmt->fetch(\PDO::FETCH_NUM);
        return is_array($row) ? $row : false;
    }

    /**
     * Set result pointer — not supported with PDO forward-only cursors
     *
     * @param int $iRowId id of the row
     * @return bool always false (not supported)
     */
    public function setResultPointer(int $iRowId = 0): bool
    {
        return false;
    }

    /**
     * Get number of affected rows (for INSERT, UPDATE, DELETE)
     *
     * @return int number of affected rows
     */
    public function getAffectedRows(): int
    {
        return ($this->m_stmt !== null) ? $this->m_stmt->rowCount() : 0;
    }

    /**
     * Free result memory
     *
     * @return void
     */
    public function freeResult(): void
    {
        if ($this->m_stmt !== null) {
            $this->m_stmt->closeCursor();
            $this->m_stmt = null;
        }
    }
}
