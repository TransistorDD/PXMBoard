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
     * Test markThreadAsRead marks all messages in the thread as read
     *
     * @return void
     */
    public function test_markThreadAsRead_marksAllMessagesInThread(): void
    {
        $iUserId   = $this->insertUser();
        $iBoardId  = $this->insertBoard();
        $iThreadId = $this->insertThread($iBoardId);
        $iMsgId1   = $this->insertMessage($iThreadId, ['m_userid' => $iUserId]);
        $iMsgId2   = $this->insertMessage($iThreadId, ['m_userid' => $iUserId, 'm_parentid' => $iMsgId1]);
        $iMsgId3   = $this->insertMessage($iThreadId, ['m_userid' => $iUserId, 'm_parentid' => $iMsgId1]);

        $objTracker = new cMessageReadTracker(cDB::getInstance());
        $bResult = $objTracker->markThreadAsRead($iUserId, $iThreadId);

        $this->assertTrue($bResult);

        foreach ([$iMsgId1, $iMsgId2, $iMsgId3] as $iMsgId) {
            $iCount = $objTracker->getReadCount($iMsgId);
            $this->assertSame(1, $iCount, "Message $iMsgId should be marked as read");
        }
    }

    /**
     * Test markThreadAsRead with invalid userId returns false
     *
     * @return void
     */
    public function test_markThreadAsRead_withInvalidUserId_returnsFalse(): void
    {
        $objTracker = new cMessageReadTracker(cDB::getInstance());
        $this->assertFalse($objTracker->markThreadAsRead(0, 1));
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
        cDB::getInstance()->executeQuery(
            'INSERT INTO pxm_message_read (mr_userid, mr_messageid, mr_timestamp) VALUES ('
            . $iUserId . ',' . $iMessageId . ',' . $iOldTimestamp . ')'
        );

        // Clean up records older than 60 days
        $objTracker = new cMessageReadTracker(cDB::getInstance());
        $iDeleted = $objTracker->cleanup(60);

        $this->assertGreaterThanOrEqual(1, $iDeleted);
    }
}
