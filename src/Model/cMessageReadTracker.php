<?php

/**
 * Message read tracking
 *
 * @author Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright Torsten Rentsch 2001 - 2026
 */
class cMessageReadTracker
{
    /**
     * Mark single message as read
     *
     * @param int $iUserId User ID
     * @param int $iMessageId Message ID
     * @return bool Success
     */
    public static function markAsRead(int $iUserId, int $iMessageId): bool
    {
        $objDb = cDBFactory::getInstance();

        if ($iUserId <= 0 || $iMessageId <= 0) {
            return false;
        }

        $iTimestamp = time();

        // Single INSERT with ON DUPLICATE KEY UPDATE
        $sQuery = 'INSERT INTO pxm_message_read (mr_userid, mr_messageid, mr_timestamp) VALUES (' .
                  (int)$iUserId . ',' .
                  (int)$iMessageId . ',' .
                  (int)$iTimestamp .
                  ') ON DUPLICATE KEY UPDATE mr_timestamp=VALUES(mr_timestamp)';

        return $objDb->executeQuery($sQuery) != false;
    }

    /**
     * Mark all messages in a thread as read
     *
     * @param int $iUserId User ID
     * @param int $iThreadId Thread ID
     * @return bool Success
     */
    public static function markThreadAsRead(int $iUserId, int $iThreadId): bool
    {
        $objDb = cDBFactory::getInstance();

        if ($iUserId <= 0 || $iThreadId <= 0) {
            return false;
        }

        $iTimestamp = time();

        $sQuery = 'INSERT INTO pxm_message_read (mr_userid, mr_messageid, mr_timestamp) ' .
                  'SELECT ' . (int)$iUserId . ', m_id, ' . (int)$iTimestamp . ' ' .
                  'FROM pxm_message ' .
                  'WHERE m_threadid = ' . (int)$iThreadId . ' ' .
                  'ON DUPLICATE KEY UPDATE mr_timestamp = VALUES(mr_timestamp)';

        return $objDb->executeQuery($sQuery) != false;
    }

    /**
     * Get read count for a message
     *
     * @param int $iMessageId Message ID
     * @return int Number of registered users who have read this message
     */
    public static function getReadCount(int $iMessageId): int
    {
        $objDb = cDBFactory::getInstance();

        if ($iMessageId <= 0) {
            return 0;
        }

        $sQuery = 'SELECT COUNT(*) as readcount FROM pxm_message_read WHERE mr_messageid=' . (int)$iMessageId;

        if ($objResultSet = $objDb->executeQuery($sQuery)) {
            if ($objResultRow = $objResultSet->getNextResultRowObject()) {
                return (int)$objResultRow->readcount;
            }
        }

        return 0;
    }

    /**
     * Cleanup old entries (Cron-Job)
     *
     * @param int $iDaysOld Delete entries older than X days
     * @return int Number of deleted rows
     */
    public static function cleanup(int $iDaysOld = 60): int
    {
        $objDb = cDBFactory::getInstance();
        $iCutoff = time() - ($iDaysOld * 86400);

        $sQuery = 'DELETE FROM pxm_message_read ' .
                  'WHERE mr_timestamp < ' . (int)$iCutoff . ' ' .
                  'LIMIT 10000'; // Prevent lock escalation

        $objResultSet = $objDb->executeQuery($sQuery);
        return $objResultSet ? $objResultSet->getAffectedRows() : 0;
    }
}
