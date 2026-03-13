<?php


declare(strict_types=1);

namespace PXMBoard\Tests\Integration\Model;

use PXMBoard\Database\cDB;
use PXMBoard\Model\cMessageReadTracker;
use PXMBoard\Tests\TestCase\IntegrationTestCase;

/**
 * Integration test for cMessageReadTracker class
 * Tests read-tracking against the real test database
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cMessageReadTrackerTest extends IntegrationTestCase
{
    /**
     * Test markAsRead with valid IDs inserts a record and returns true
     *
     * @return void
     */
    public function test_markAsRead_withValidIds_returnsTrue(): void
    {
        $iUserId    = $this->insertUser();
        $iBoardId   = $this->insertBoard();
        $iThreadId  = $this->insertThread($iBoardId);
        $iMessageId = $this->insertMessage($iThreadId, ['m_userid' => $iUserId]);

        $objTracker = new cMessageReadTracker(cDB::getInstance());
        $bResult = $objTracker->markAsRead($iUserId, $iMessageId);

        $this->assertTrue($bResult);
    }

    /**
     * Test getReadCount reflects inserted read records
     *
     * @return void
     */
    public function test_getReadCount_withReadRecord_returnsCount(): void
    {
        $iUserId    = $this->insertUser();
        $iBoardId   = $this->insertBoard();
        $iThreadId  = $this->insertThread($iBoardId);
        $iMessageId = $this->insertMessage($iThreadId, ['m_userid' => $iUserId]);

        $objTracker = new cMessageReadTracker(cDB::getInstance());
        $objTracker->markAsRead($iUserId, $iMessageId);

        $iCount = $objTracker->getReadCount($iMessageId);

        $this->assertSame(1, $iCount);
    }

    /**
     * Test that marking the same message+user as read twice within the same month
     * inserts only one row (INSERT IGNORE deduplication).
     *
     * @return void
     */
    public function test_markAsRead_sameMessageSameMonth_noDuplicate(): void
    {
        $iUserId    = $this->insertUser();
        $iBoardId   = $this->insertBoard();
        $iThreadId  = $this->insertThread($iBoardId);
        $iMessageId = $this->insertMessage($iThreadId, ['m_userid' => $iUserId]);

        $objTracker = new cMessageReadTracker(cDB::getInstance());
        $objTracker->markAsRead($iUserId, $iMessageId);
        $objTracker->markAsRead($iUserId, $iMessageId);

        $iCount = $objTracker->getReadCount($iMessageId);
        $this->assertSame(1, $iCount, 'Duplicate read in same month must be silently ignored');
    }

    /**
     * Test that managePartitions() is idempotent: calling it twice in the same
     * month must not throw and must not insert a second partition tracking row.
     *
     * @return void
     */
    public function test_managePartitions_isIdempotent(): void
    {
        $objTracker = new cMessageReadTracker(cDB::getInstance());

        $objTracker->managePartitions();
        $objTracker->managePartitions(); // Second call must be a no-op

        $iYearMonth = (int) date('Ym');
        $objResultSet = cDB::getInstance()->executeQuery(
            'SELECT COUNT(*) AS cnt FROM pxm_message_read_partition WHERE mrp_year_month=' . $iYearMonth
        );
        $this->assertNotNull($objResultSet);

        $iCount = 0;
        if ($objRow = $objResultSet->getNextResultRowObject()) {
            $iCount = (int) $objRow->cnt;
        }
        $objResultSet->freeResult();

        $this->assertSame(1, $iCount, 'Exactly one partition tracking row must exist for current month');
    }
}
