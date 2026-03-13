<?php

declare(strict_types=1);

namespace PXMBoard\Model;

use PXMBoard\Database\cDB;

/**
 * Message read tracking with monthly range partitioning.
 *
 * Partition management fires at most once per month per DB server, guarded
 * by pxm_message_read_partition (INSERT IGNORE as a concurrency gate).
 * Old partitions are dropped automatically once retention is exceeded.
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
     * @param cDB $m_objDb              Database instance (inject for testing)
     * @param int $m_iRetentionMonths   Retention in months; partitions older than this are dropped
     */
    public function __construct(private readonly cDB $m_objDb, private readonly int $m_iRetentionMonths = 13)
    {
    }

    /**
     * Mark a single message as read for a user.
     *
     * Calls managePartitions() first to ensure the current month's partition
     * exists. INSERT IGNORE silently skips duplicate reads (same user +
     * message within the same month).
     *
     * @param int $iUserId    User ID
     * @param int $iMessageId Message ID
     * @return bool Success
     */
    public function markAsRead(int $iUserId, int $iMessageId): bool
    {
        if ($iUserId <= 0 || $iMessageId <= 0) {
            return false;
        }

        $this->managePartitions();

        $iYearMonth = (int) date('ym');

        $sQuery = 'INSERT IGNORE INTO pxm_message_read (mr_userid, mr_messageid, mr_year_month) VALUES ('
                . (int) $iUserId . ','
                . (int) $iMessageId . ','
                . $iYearMonth
                . ')';

        return $this->m_objDb->executeQuery($sQuery) !== null;
    }

    /**
     * Get read count for a message.
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
     * Ensure the current month's partition exists, create it if necessary.
     *
     * Uses INSERT IGNORE into pxm_message_read_partition as a concurrency gate:
     * only the process that successfully inserts the row performs DDL.
     * Partitions older than RETENTION_MONTHS are dropped afterwards.
     *
     * @return void
     */
    public function managePartitions(): void
    {
        $iYearMonth = (int) date('ym');

        // Fast PK-lookup: is this month already managed?
        $objResultSet = $this->m_objDb->executeQuery(
            'SELECT mrp_year_month FROM pxm_message_read_partition WHERE mrp_year_month=' . $iYearMonth
        );
        if ($objResultSet) {
            $bExists = $objResultSet->getNextResultRowObject() !== false;
            $objResultSet->freeResult();
            if ($bExists) {
                return;
            }
        }

        // Try to claim DDL ownership for this month (INSERT IGNORE as concurrency gate)
        $objResultSet = $this->m_objDb->executeQuery(
            'INSERT IGNORE INTO pxm_message_read_partition (mrp_year_month, mrp_created_timestamp) VALUES ('
            . $iYearMonth . ',' . time() . ')'
        );
        if (!$objResultSet) {
            return;
        }

        $iInserted = $objResultSet->getAffectedRows();
        $objResultSet->freeResult();

        if ($iInserted === 0) {
            return; // Another process won the race; partition already created
        }

        // Create the partition for the current month
        $this->_addPartition($iYearMonth);

        // Drop partitions older than the retention limit
        $iCutoffYearMonth = $this->_subtractMonths($iYearMonth, $this->m_iRetentionMonths);
        $this->_dropOldPartitions($iCutoffYearMonth);
    }

    /**
     * Add a new RANGE partition for the given year-month.
     *
     * MySQL:      ALTER TABLE ... ADD PARTITION (PARTITION p_YYMM VALUES LESS THAN (next))
     * PostgreSQL: CREATE TABLE pxm_message_read_YYMM PARTITION OF ...
     *
     * @param int $iYearMonth YYMM (e.g. 2601)
     * @return void
     */
    private function _addPartition(int $iYearMonth): void
    {
        $iYear  = (int) ($iYearMonth / 100);
        $iMonth = $iYearMonth % 100;

        $iNextYearMonth = ($iMonth === 12)
            ? ($iYear + 1) * 100 + 1
            : $iYear * 100 + ($iMonth + 1);

        if ($this->m_objDb->getDBType() === 'MySQL') {
            $this->m_objDb->executeQuery(
                'ALTER TABLE pxm_message_read ADD PARTITION '
                . '(PARTITION p_' . $iYearMonth . ' VALUES LESS THAN (' . $iNextYearMonth . '))'
            );
        } else {
            $this->m_objDb->executeQuery(
                'CREATE TABLE IF NOT EXISTS pxm_message_read_' . $iYearMonth
                . ' PARTITION OF pxm_message_read'
                . ' FOR VALUES FROM (' . $iYearMonth . ') TO (' . $iNextYearMonth . ')'
            );
        }
    }

    /**
     * Drop partitions older than the cutoff year-month.
     *
     * Queries pxm_message_read_partition for managed months below the cutoff,
     * drops each partition, then removes the tracking row.
     *
     * @param int $iCutoffYearMonth YYMM – partitions with value < cutoff are dropped
     * @return void
     */
    private function _dropOldPartitions(int $iCutoffYearMonth): void
    {
        $objResultSet = $this->m_objDb->executeQuery(
            'SELECT mrp_year_month FROM pxm_message_read_partition WHERE mrp_year_month < ' . $iCutoffYearMonth
        );
        if (!$objResultSet) {
            return;
        }

        $arrOldMonths = [];
        while ($objRow = $objResultSet->getNextResultRowObject()) {
            $arrOldMonths[] = (int) $objRow->mrp_year_month;
        }
        $objResultSet->freeResult();

        foreach ($arrOldMonths as $iOldYearMonth) {
            if ($this->m_objDb->getDBType() === 'MySQL') {
                $this->m_objDb->executeQuery(
                    'ALTER TABLE pxm_message_read DROP PARTITION p_' . $iOldYearMonth
                );
            } else {
                $this->m_objDb->executeQuery(
                    'DROP TABLE IF EXISTS pxm_message_read_' . $iOldYearMonth
                );
            }

            $this->m_objDb->executeQuery(
                'DELETE FROM pxm_message_read_partition WHERE mrp_year_month=' . $iOldYearMonth
            );
        }
    }

    /**
     * Subtract months from a YYMM value.
     *
     * @param int $iYearMonth Base YYMM value
     * @param int $iMonths    Number of months to subtract
     * @return int Resulting YYMM
     */
    private function _subtractMonths(int $iYearMonth, int $iMonths): int
    {
        $iYear  = (int) ($iYearMonth / 100);
        $iMonth = ($iYearMonth % 100) - $iMonths;

        while ($iMonth <= 0) {
            $iMonth += 12;
            $iYear--;
        }

        return $iYear * 100 + $iMonth;
    }
}
