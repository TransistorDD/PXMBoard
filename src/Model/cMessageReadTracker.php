<?php

namespace PXMBoard\Model;

use PXMBoard\Database\cDB;

/**
 * Message read tracking
 *
 * Supports constructor injection of a cDB instance for testability.
 * Use `new cMessageReadTracker(cDB::getInstance())` in production code.
 *
 * @author Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright Torsten Rentsch 2001 - 2026
 */
class cMessageReadTracker
{
    /**
     * @param cDB $m_objDb Database instance (inject for testing)
     */
    public function __construct(private readonly cDB $m_objDb)
    {
    }

    /**
     * Mark single message as read
     *
     * @param int $iUserId User ID
     * @param int $iMessageId Message ID
     * @return bool Success
     */
    public function markAsRead(int $iUserId, int $iMessageId): bool
    {
        if ($iUserId <= 0 || $iMessageId <= 0) {
            return false;
        }

        $iTimestamp = time();

        // Single INSERT with ON DUPLICATE KEY UPDATE
        $sQuery = 'INSERT INTO pxm_message_read (mr_userid, mr_messageid, mr_timestamp) VALUES (' .
                  (int) $iUserId . ',' .
                  (int) $iMessageId . ',' .
                  (int) $iTimestamp .
                  ') ON DUPLICATE KEY UPDATE mr_timestamp=' . (int) $iTimestamp;

        return $this->m_objDb->executeQuery($sQuery) !== null;
    }

    /**
     * Mark all messages in a thread as read
     *
     * @param int $iUserId User ID
     * @param int $iThreadId Thread ID
     * @return bool Success
     */
    public function markThreadAsRead(int $iUserId, int $iThreadId): bool
    {
        if ($iUserId <= 0 || $iThreadId <= 0) {
            return false;
        }

        $iTimestamp = time();

        $sQuery = 'INSERT INTO pxm_message_read (mr_userid, mr_messageid, mr_timestamp) ' .
                  'SELECT ' . (int) $iUserId . ', m_id, ' . (int) $iTimestamp . ' ' .
                  'FROM pxm_message ' .
                  'WHERE m_threadid = ' . (int) $iThreadId . ' ' .
                  'ON DUPLICATE KEY UPDATE mr_timestamp = ' . (int) $iTimestamp;

        return $this->m_objDb->executeQuery($sQuery) !== null;
    }

    /**
     * Get read count for a message
     *
     * @param int $iMessageId Message ID
     * @return int Number of registered users who have read this message
     */
    public function getReadCount(int $iMessageId): int
    {
        if ($iMessageId <= 0) {
            return 0;
        }

        $sQuery = 'SELECT COUNT(*) as readcount FROM pxm_message_read WHERE mr_messageid=' . (int) $iMessageId;

        if ($objResultSet = $this->m_objDb->executeQuery($sQuery)) {
            $iCount = 0;
            if ($objResultRow = $objResultSet->getNextResultRowObject()) {
                $iCount = (int) $objResultRow->readcount;
            }
            $objResultSet->freeResult();
            return $iCount;
        }

        return 0;
    }

    /**
     * Cleanup old entries
     *
     * Deletes in batches of 10000 rows to prevent lock escalation.
     * Loops until all eligible rows are deleted, with a short pause between
     * batches to allow the database to flush undo logs and reduce I/O pressure.
     *
     * MySQL/MariaDB supports DELETE ... LIMIT natively.
     * PostgreSQL requires a subquery workaround as DELETE ... LIMIT is not standard SQL.
     *
     * @param int $iDaysOld Delete entries older than X days (default: 365)
     * @return int Total number of deleted rows
     */
    public function cleanup(int $iDaysOld = 365): int
    {
        $iCutoff = time() - ($iDaysOld * 86400);
        $iTotal = 0;
        $bIsMysql = $this->m_objDb->getDBType() === 'MySQL';

        do {
            if ($bIsMysql) {
                // MySQL/MariaDB: DELETE ... LIMIT is supported natively
                $objResultSet = $this->m_objDb->executeQuery(
                    'DELETE FROM pxm_message_read WHERE mr_timestamp < ' . (int) $iCutoff,
                    10000
                );
            } else {
                // PostgreSQL: DELETE ... LIMIT is not supported; use a subquery instead
                $sQuery = 'DELETE FROM pxm_message_read WHERE (mr_userid, mr_messageid) IN (' .
                          'SELECT mr_userid, mr_messageid FROM pxm_message_read ' .
                          'WHERE mr_timestamp < ' . (int) $iCutoff . ' LIMIT 10000)';
                $objResultSet = $this->m_objDb->executeQuery($sQuery);
            }

            if (!$objResultSet) {
                break;
            }

            $iAffected = $objResultSet->getAffectedRows();
            $objResultSet->freeResult();
            $iTotal += $iAffected;

            if ($iAffected >= 10000) {
                usleep(50000); // 50ms pause: let the DB flush undo logs between batches
            }
        } while ($iAffected >= 10000);

        return $iTotal;
    }
}
