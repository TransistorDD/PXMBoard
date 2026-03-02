<?php
/**
 * Unit test for cThreadHeader class
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
declare(strict_types=1);

namespace PXMBoard\Tests\Unit\Model;

use PHPUnit\Framework\TestCase;

class cThreadHeaderTest extends TestCase
{
    private \cThreadHeader $threadHeader;

    protected function setUp(): void
    {
        parent::setUp();
        $this->threadHeader = new \cThreadHeader();
    }

    /**
     * Test setBoardId and getBoardId
     *
     * @return void
     */
    public function test_setBoardId_andGetBoardId_worksCorrectly(): void
    {
        $this->threadHeader->setBoardId(42);
        $this->assertSame(42, $this->threadHeader->getBoardId());
    }

    /**
     * Test setThreadId and getThreadId
     *
     * @return void
     */
    public function test_setThreadId_andGetThreadId_worksCorrectly(): void
    {
        $this->threadHeader->setThreadId(123);
        $this->assertSame(123, $this->threadHeader->getThreadId());
    }

    /**
     * Test setThreadActive and isThreadActive
     *
     * @return void
     */
    public function test_setThreadActive_true_setsActive(): void
    {
        $this->threadHeader->setThreadActive(true);
        $this->assertTrue($this->threadHeader->isThreadActive());
    }

    /**
     * Test setThreadActive false sets inactive
     *
     * @return void
     */
    public function test_setThreadActive_false_setsInactive(): void
    {
        $this->threadHeader->setThreadActive(false);
        $this->assertFalse($this->threadHeader->isThreadActive());
    }

    /**
     * Test setIsThreadFixed and isThreadFixed
     *
     * @return void
     */
    public function test_setIsThreadFixed_true_setsFixed(): void
    {
        $this->threadHeader->setIsThreadFixed(true);
        $this->assertTrue($this->threadHeader->isThreadFixed());
    }

    /**
     * Test setIsThreadFixed false sets not fixed
     *
     * @return void
     */
    public function test_setIsThreadFixed_false_setsNotFixed(): void
    {
        $this->threadHeader->setIsThreadFixed(false);
        $this->assertFalse($this->threadHeader->isThreadFixed());
    }

    /**
     * Test setLastMessageId and getLastMessageId
     *
     * @return void
     */
    public function test_setLastMessageId_andGetLastMessageId_worksCorrectly(): void
    {
        $this->threadHeader->setLastMessageId(999);
        $this->assertSame(999, $this->threadHeader->getLastMessageId());
    }

    /**
     * Test setLastMessageTimestamp and getLastMessageTimestamp
     *
     * @return void
     */
    public function test_setLastMessageTimestamp_andGetLastMessageTimestamp_worksCorrectly(): void
    {
        $iTimestamp = 1640000000;
        $this->threadHeader->setLastMessageTimestamp($iTimestamp);
        $this->assertSame($iTimestamp, $this->threadHeader->getLastMessageTimestamp());
    }

    /**
     * Test setMessageQuantity and getMessageQuantity
     *
     * @return void
     */
    public function test_setMessageQuantity_andGetMessageQuantity_worksCorrectly(): void
    {
        $this->threadHeader->setMessageQuantity(50);
        $this->assertSame(50, $this->threadHeader->getMessageQuantity());
    }

    /**
     * Test setViews and getViews
     *
     * @return void
     */
    public function test_setViews_andGetViews_worksCorrectly(): void
    {
        $this->threadHeader->setViews(1234);
        $this->assertSame(1234, $this->threadHeader->getViews());
    }

    /**
     * Test setThreadMsgRead and isThreadMsgRead
     *
     * @return void
     */
    public function test_setThreadMsgRead_true_setsRead(): void
    {
        $this->threadHeader->setThreadMsgRead(true);
        $this->assertTrue($this->threadHeader->isThreadMsgRead());
    }

    /**
     * Test setThreadMsgRead false sets unread
     *
     * @return void
     */
    public function test_setThreadMsgRead_false_setsUnread(): void
    {
        $this->threadHeader->setThreadMsgRead(false);
        $this->assertFalse($this->threadHeader->isThreadMsgRead());
    }

    /**
     * Test setLastMsgRead and isLastMsgRead
     *
     * @return void
     */
    public function test_setLastMsgRead_true_setsRead(): void
    {
        $this->threadHeader->setLastMsgRead(true);
        $this->assertTrue($this->threadHeader->isLastMsgRead());
    }

    /**
     * Test setLastMsgRead false sets unread
     *
     * @return void
     */
    public function test_setLastMsgRead_false_setsUnread(): void
    {
        $this->threadHeader->setLastMsgRead(false);
        $this->assertFalse($this->threadHeader->isLastMsgRead());
    }

    /**
     * Test initial state has default values
     *
     * @return void
     */
    public function test_initialState_hasDefaultValues(): void
    {
        $this->assertSame(0, $this->threadHeader->getBoardId());
        $this->assertSame(0, $this->threadHeader->getThreadId());
        $this->assertFalse($this->threadHeader->isThreadActive());
        $this->assertFalse($this->threadHeader->isThreadFixed());
        $this->assertSame(0, $this->threadHeader->getLastMessageId());
        $this->assertSame(0, $this->threadHeader->getLastMessageTimestamp());
        $this->assertSame(0, $this->threadHeader->getMessageQuantity());
        $this->assertSame(0, $this->threadHeader->getViews());
        $this->assertFalse($this->threadHeader->isThreadMsgRead());
        $this->assertFalse($this->threadHeader->isLastMsgRead());
    }

}
