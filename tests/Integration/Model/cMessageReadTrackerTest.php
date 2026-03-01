<?php
/**
 * Integration test for cMessageReadTracker class
 * Tests read-tracking against the real test database
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
declare(strict_types=1);

namespace PXMBoard\Tests\Integration\Model;

use PXMBoard\Tests\TestCase\IntegrationTestCase;

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

        $bResult = \cMessageReadTracker::markAsRead($iUserId, $iMessageId);

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

        \cMessageReadTracker::markAsRead($iUserId, $iMessageId);

        $iCount = \cMessageReadTracker::getReadCount($iMessageId);

        $this->assertSame(1, $iCount);
    }

    /**
     * Test cleanup removes old entries and returns deleted count
     *
     * @return void
     */
    public function test_cleanup_removesOldEntries(): void
    {
        $iUserId    = $this->insertUser();
        $iBoardId   = $this->insertBoard();
        $iThreadId  = $this->insertThread($iBoardId);
        $iMessageId = $this->insertMessage($iThreadId, ['m_userid' => $iUserId]);

        // Insert a read record with an old timestamp directly (90 days ago)
        $iOldTimestamp = time() - (90 * 86400);
        \cDBFactory::getInstance()->executeQuery(
            'INSERT INTO pxm_message_read (mr_userid, mr_messageid, mr_timestamp) VALUES ('
            . $iUserId . ',' . $iMessageId . ',' . $iOldTimestamp . ')'
        );

        // Clean up records older than 60 days
        $iDeleted = \cMessageReadTracker::cleanup(60);

        $this->assertGreaterThanOrEqual(1, $iDeleted);
    }
}
