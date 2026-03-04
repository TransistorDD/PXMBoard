<?php

require_once(SRCDIR . '/Exception/cDatabaseException.php');
/**
 * abstraction layer for DB handling (interface)
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
abstract class cDB
{
    protected mixed $m_resDBLink;					//link id returned by connect
    protected int $m_iLastErrorId;					//last error id
    protected string $m_sLastErrorMessage;			//last error message

    /**
     * Constructor
     *
     * @return void
     */
    public function __construct()
    {
        $this->m_resDBLink			= null;
        $this->m_iLastErrorId		= 0;
        $this->m_sLastErrorMessage	= '';
    }

    /**
     * open a connection to a DB Server
     *
     * @param string $sHostName hostname
     * @param string $sUserName username
     * @param string $sPassword password
     * @param string $sDBName db name
     * @return bool success / failure
     */
    abstract public function connectDBServer(string $sHostName = 'localhost', string $sUserName = 'defaultuser', string $sPassword = '', string $sDBName = ''): bool;

    /**
     * execute a query
     *
     * @param string $sQuery query string
     * @param int $iLimit row limit
     * @param int $iOffset row offset
     * @return cDBResultSet|null query result set
     */
    abstract public function executeQuery(string $sQuery, int $iLimit = 0, int $iOffset = 0): ?cDBResultSet;

    /**
     * get the id generated from the previous insert operation
     *
     * @param string $sTableName table name
     * @param string $sColumnName column name
     * @return int insert id
     */
    abstract public function getInsertId(string $sTableName, string $sColumnName): int;

    /**
     * escape special chars in the string for use in a db query
     *
     * @param string $sString string to quote
     * @return string quoted string
     */
    public function quote(string $sString): string
    {
        return "'".addslashes($sString)."'";
    }

    /**
     * close a connection to a DB Server
     *
     * @return void
     */
    abstract public function disconnectDBServer(): void;

    /**
     * get the type of the connection
     *
     * @return string db type
     */
    abstract public function getDBType(): string;

    /**
     * get the version of the db
     *
     * @return string db version
     */
    abstract public function getDBVersion(): string;

    /**
     * get the column metatype (integer, string, text)
     *
     * @param string $sMetaType meta name of the column type (integer, string)
     * @param int $iSize size of the requested field
     * @return string db dependent column type
     */
    abstract public function getMetaType(string $sMetaType, int $iSize = -1): string;

    /**
     * Handle a database error by throwing a cDatabaseException.
     *
     * @param string $sErrorMessage Additional context message
     * @return never
     * @throws cDatabaseException Always
     */
    protected function _handleError(string $sErrorMessage = ''): never
    {
        $sMessage = $sErrorMessage;
        if (!empty($this->m_iLastErrorId)) {
            $sMessage .= ' [#' . $this->m_iLastErrorId . ']';
        }
        if (!empty($this->m_sLastErrorMessage)) {
            $sMessage .= ': ' . $this->m_sLastErrorMessage;
        }
        throw new cDatabaseException($sMessage);
    }
}

/**
 * database resultset (interface)
 *
 * @author Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright Torsten Rentsch 2001 - 2026
 */
abstract class cDBResultSet
{
    protected mixed $m_resResultSet;

    /**
     * Constructor
     *
     * @param mixed $resResultSet result set resource
     * @return void
     */
    public function __construct(mixed $resResultSet)
    {
        $this->m_resResultSet = $resResultSet;
    }

    /**
     * get next result row as an object
     *
     * @return object|null|false result row
     */
    abstract public function getNextResultRowObject(): object|null|false;

    /**
     * get next result row as an associative array
     *
     * @return array<string, mixed>|false result row
     */
    abstract public function getNextResultRowAssociative(): array|false;

    /**
     * get next result row as an numeric array
     *
     * @return array<mixed>|false result row
     */
    abstract public function getNextResultRowNumeric(): array|false;

    /**
     * set result pointer to ...
     *
     * @param int $iRowId id of the row
     * @return bool success / failure
     */
    abstract public function setResultPointer(int $iRowId = 0): bool;

    /**
     * get number of rows in result (for select)
     *
     * @return int number of rows
     */
    abstract public function getNumRows(): int;

    /**
     * get number of affected rows (for insert, update and delete)
     *
     * @return int number of rows
     */
    abstract public function getAffectedRows(): int;

    /**
     * free result memory
     *
     * @return void
     */
    abstract public function freeResult(): void;
}
