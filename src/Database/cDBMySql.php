<?php

namespace PXMBoard\Database;

/**
 * abstraction layer for DB handling (MySql)
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cDBMySql extends cDB
{
    /**
     * open a connection to a DB Server
     *
     * @param string $sHostName hostname
     * @param string $sUserName username
     * @param string $sPassword password
     * @param string $sDBName db name
     * @return bool success / failure
     */
    public function connectDBServer(string $sHostName = 'localhost', string $sUserName = 'defaultuser', string $sPassword = '', string $sDBName = ''): bool
    {
        try {
            $this->m_resDBLink = @mysqli_connect($sHostName, $sUserName, $sPassword, $sDBName);
        } catch (\mysqli_sql_exception $e) {
            $this->_handleError('DB-Verbindung fehlgeschlagen: ' . $e->getMessage());
        }
        return true;
    }

    /**
     * execute a query
     *
     * @param string $sQuery query string
     * @param int $iLimit row limit
     * @param int $iOffset row offset
     * @return cDBResultSet|null query result set
     */
    public function executeQuery(string $sQuery, int $iLimit = 0, int $iOffset = 0): ?cDBResultSet
    {
        $objReturn = null;
        if (empty($sQuery)) {
            $this->_handleError('invalid querystring');
        } else {
            if (!empty($iLimit) || !empty($iOffset)) {
                if (!empty($iOffset)) {
                    $sQuery .= " LIMIT $iOffset";
                    if (!empty($iLimit)) {
                        $sQuery .= ",$iLimit";
                    }
                } else {
                    $sQuery .= " LIMIT $iLimit";
                }
            }

            if ($mResult = @mysqli_query($this->m_resDBLink, $sQuery)) {
                if ($mResult === true) {
                    $mResult = null;
                }
                $objResultSet = new cDBMySqlResultSet($mResult);
                $objResultSet->setAffectedRows(@mysqli_affected_rows($this->m_resDBLink));
                $objReturn =  $objResultSet;
            } else {
                $this->m_iLastErrorId		= mysqli_errno($this->m_resDBLink);
                $this->m_sLastErrorMessage	= mysqli_error($this->m_resDBLink);
                $this->_handleError("couldn't execute query");
            }
        }
        return $objReturn;
    }

    /**
     * get the id generated from the previous insert operation
     *
     * @param string $sTableName table name
     * @param string $sColumnName column name
     * @return int insert id
     */
    public function getInsertID(string $sTableName, string $sColumnName): int
    {
        return mysqli_insert_id($this->m_resDBLink);
    }

    /**
     * close a connection to a DB Server
     *
     * @return void
     */
    public function disconnectDBServer(): void
    {
        @mysqli_close($this->m_resDBLink);
    }

    /**
     * get the type of the connection
     *
     * @return string db type
     */
    public function getDBType(): string
    {
        return 'MySQL';
    }

    /**
     * get the version of the db
     *
     * @return string db version
     */
    public function getDBVersion(): string
    {
        return mysqli_get_server_info($this->m_resDBLink);
    }

    /**
     * get the column metatype (integer, string)
     *
     * @param string $sMetaType meta name of the column type (integer, string)
     * @param int $iSize size of the requested field
     * @return string db dependent column type
     */
    public function getMetaType(string $sMetaType, int $iSize = -1): string
    {
        $sColumnType = '';
        switch ($sMetaType) {
            case 'integer':	$sColumnType = 'INT';
                break;
            case 'string':	if ($iSize <= 255) {
                $sColumnType = "VARCHAR($iSize)";
            } else {
                $sColumnType = 'TEXT';
            }
                break;
        }
        return $sColumnType;
    }

    /**
     * escape special chars in the string for use in a db query
     * Overrides cDB::quote() to use mysqli_real_escape_string instead of addslashes
     *
     * @param string $sString string to quote
     * @return string quoted string
     */
    public function quote(string $sString): string
    {
        return "'".mysqli_real_escape_string($this->m_resDBLink, $sString)."'";
    }
}

/**
 * database resultset (MySql)
 *
 * @author Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright Torsten Rentsch 2001 - 2026
 */
class cDBMySqlResultSet extends cDBResultSet
{
    protected int $m_iAffectedRows = 0;

    /**
     * get next result row as an object
     *
     * @return object|null|false result row
     */
    public function getNextResultRowObject(): object|null|false
    {
        return mysqli_fetch_object($this->m_resResultSet);
    }

    /**
     * get next result row as an associative array
     *
     * @return array<string, mixed>|false result row
     */
    public function getNextResultRowAssociative(): array|false
    {
        return mysqli_fetch_array($this->m_resResultSet, MYSQLI_ASSOC);
    }

    /**
     * get next result row as an numeric array
     *
     * @return array<mixed>|false result row
     */
    public function getNextResultRowNumeric(): array|false
    {
        return mysqli_fetch_array($this->m_resResultSet, MYSQLI_NUM);
    }

    /**
     * set result pointer to ...
     *
     * @param int $iRowId id of the row
     * @return bool success / failure
     */
    public function setResultPointer(int $iRowId = 0): bool
    {
        if (@mysqli_data_seek($this->m_resResultSet, $iRowId)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * get number of rows in result (for select)
     *
     * @return int number of rows
     */
    public function getNumRows(): int
    {
        return mysqli_num_rows($this->m_resResultSet);
    }

    /**
     * set number of affected rows (for insert, update and delete)
     *
     * @param int $iAffectedRows number of rows
     * @return void
     */
    public function setAffectedRows(int $iAffectedRows): void
    {
        $this->m_iAffectedRows = $iAffectedRows;
    }

    /**
     * get number of affected rows (for insert, update and delete)
     *
     * @return int number of rows
     */
    public function getAffectedRows(): int
    {
        return $this->m_iAffectedRows;
    }

    /**
     * free result memory
     *
     * @return void
     */
    public function freeResult(): void
    {
        mysqli_free_result($this->m_resResultSet);
    }
}
