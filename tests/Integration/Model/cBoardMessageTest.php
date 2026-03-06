<?php

declare(strict_types=1);

namespace PXMBoard\Tests\Integration\Model;

use PXMBoard\Enum\eMessageStatus;
use PXMBoard\Model\cBoardMessage;
use PXMBoard\Tests\TestCase\IntegrationTestCase;

/**
 * Integration test for cBoardMessage class
 * Tests message data loading against the real test database
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cBoardMessageTest extends IntegrationTestCase
{
    /**
     * Test loading message by ID
     *
     * @return void
     */
    public function test_loadDataById_withValidId_loadsMessage(): void
    {
        $iUserId    = $this->insertUser(['u_username' => 'msg_author']);
        $iBoardId   = $this->insertBoard(['b_name' => 'Test Board']);
        $iThreadId  = $this->insertThread($iBoardId);
        $iMessageId = $this->insertMessage($iThreadId, [
            'm_subject' => 'Test Message',
            'm_body'    => 'This is a test message.',
            'm_status'  => 1,
            'm_userid'  => $iUserId,
        ]);

        $objMessage = new cBoardMessage();
        $bResult = $objMessage->loadDataById($iMessageId, $iBoardId);

        $this->assertTrue($bResult);
        $this->assertSame($iMessageId, $objMessage->getId());
        $this->assertSame('Test Message', $objMessage->getSubject());
    }

    /**
     * Test loading message with non-existent ID returns false
     *
     * @return void
     */
    public function test_loadDataById_withInvalidId_returnsFalse(): void
    {
        $iBoardId = $this->insertBoard();

        $objMessage = new cBoardMessage();
        $bResult = $objMessage->loadDataById(999999, $iBoardId);

        $this->assertFalse($bResult);
    }

    /**
     * Test message status enum integration
     *
     * @return void
     */
    public function test_getStatus_returnsMessageStatus(): void
    {
        $iUserId    = $this->insertUser(['u_username' => 'status_author']);
        $iBoardId   = $this->insertBoard();
        $iThreadId  = $this->insertThread($iBoardId);
        $iMessageId = $this->insertMessage($iThreadId, [
            'm_status' => 1,
            'm_userid' => $iUserId,
        ]);

        $objMessage = new cBoardMessage();
        $objMessage->loadDataById($iMessageId, $iBoardId);

        $objStatus = $objMessage->getStatus();
        $this->assertInstanceOf(eMessageStatus::class, $objStatus);
        $this->assertSame(eMessageStatus::PUBLISHED, $objStatus);
    }

    /**
     * Test draft message status
     *
     * @return void
     */
    public function test_isDraft_withDraftMessage_returnsTrue(): void
    {
        $iUserId    = $this->insertUser(['u_username' => 'draft_author']);
        $iBoardId   = $this->insertBoard();
        $iThreadId  = $this->insertThread($iBoardId);
        $iMessageId = $this->insertMessage($iThreadId, [
            'm_subject' => 'Draft Message',
            'm_body'    => 'This is a draft.',
            'm_status'  => 0,
            'm_userid'  => $iUserId,
        ]);

        $objMessage = new cBoardMessage();
        $objMessage->loadDataById($iMessageId, $iBoardId);

        $this->assertTrue($objMessage->getStatus()->isDraft());
    }
}
