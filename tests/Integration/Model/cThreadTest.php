<?php

declare(strict_types=1);

namespace PXMBoard\Tests\Integration\Model;

use PXMBoard\Model\cThread;
use PXMBoard\Tests\TestCase\IntegrationTestCase;

/**
 * Integration test for cThread class
 * Tests thread data loading against the real test database
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cThreadTest extends IntegrationTestCase
{
    /**
     * Test loading thread by ID
     *
     * @return void
     */
    public function test_loadDataById_withValidId_loadsThread(): void
    {
        $iBoardId  = $this->insertBoard();
        $iThreadId = $this->insertThread($iBoardId, [
            't_active'  => 1,
            't_fixed'   => 0,
            't_msgquantity' => 5,
            't_views'   => 100,
        ]);

        $objThread = new cThread();
        $bResult = $objThread->loadDataById($iThreadId, $iBoardId);

        $this->assertTrue($bResult);
        $this->assertSame($iThreadId, $objThread->getId());
        $this->assertTrue($objThread->isActive());
    }

    /**
     * Test loading thread with non-existent ID returns false
     *
     * @return void
     */
    public function test_loadDataById_withInvalidId_returnsFalse(): void
    {
        $iBoardId = $this->insertBoard();

        $objThread = new cThread();
        $bResult = $objThread->loadDataById(999999, $iBoardId);

        $this->assertFalse($bResult);
    }

    /**
     * Test thread active status
     *
     * @return void
     */
    public function test_isActive_withActiveThread_returnsTrue(): void
    {
        $iBoardId  = $this->insertBoard();
        $iThreadId = $this->insertThread($iBoardId, ['t_active' => 1]);

        $objThread = new cThread();
        $objThread->loadDataById($iThreadId, $iBoardId);

        $this->assertTrue($objThread->isActive());
    }

    /**
     * Test getDataArray uses DB read status for logged-in users
     * A message pre-marked as read should have new=0; an unread message should have new=1.
     *
     * @return void
     */
    public function test_getDataArray_setsNewFlag_basedOnReadStatus(): void
    {
        $iUserId   = $this->insertUser();
        $iBoardId  = $this->insertBoard();
        $iThreadId = $this->insertThread($iBoardId);
        $iMsgRead  = $this->insertMessage($iThreadId, ['m_userid' => $iUserId, 'm_subject' => 'Read message']);
        $iMsgUnread = $this->insertMessage($iThreadId, ['m_userid' => $iUserId, 'm_parentid' => $iMsgRead, 'm_subject' => 'Unread message']);

        // Pre-mark only the first message as read
        \cMessageReadTracker::markAsRead($iUserId, $iMsgRead);

        $objThread = new \cThread();
        $objThread->loadDataById($iThreadId, $iBoardId);

        $arrData = $objThread->getDataArray(0, 'd.m.Y', 0, $iUserId);

        // Flatten the message tree into a map keyed by id
        $arrMsgMap = [];
        $this->flattenMsgTree($arrData['msg'] ?? [], $arrMsgMap);

        $this->assertArrayHasKey($iMsgRead, $arrMsgMap, 'Read message should be in tree');
        $this->assertArrayHasKey($iMsgUnread, $arrMsgMap, 'Unread message should be in tree');
        $this->assertSame(0, $arrMsgMap[$iMsgRead]['new'], 'Pre-read message should have new=0');
        $this->assertSame(1, $arrMsgMap[$iMsgUnread]['new'], 'Unread message should have new=1');
    }

    /**
     * Recursively flatten a message tree into a map keyed by message id.
     *
     * @param array<int,mixed> $arrMessages
     * @param array<int,mixed> $arrMap (out)
     * @return void
     */
    private function flattenMsgTree(array $arrMessages, array &$arrMap): void
    {
        foreach ($arrMessages as $arrMsg) {
            $arrMap[(int)$arrMsg['id']] = $arrMsg;
            if (!empty($arrMsg['msg'])) {
                $this->flattenMsgTree($arrMsg['msg'], $arrMap);
            }
        }
    }

    /**
     * Test thread pinned status
     *
     * @return void
     */
    public function test_isPinned_withPinnedThread_returnsTrue(): void
    {
        $iBoardId  = $this->insertBoard();
        $iThreadId = $this->insertThread($iBoardId, [
            't_active' => 1,
            't_fixed'  => 1,
        ]);

        $objThread = new cThread();
        $objThread->loadDataById($iThreadId, $iBoardId);

        $this->assertTrue($objThread->isFixed());
    }
}
