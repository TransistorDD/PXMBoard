<?php

declare(strict_types=1);

namespace PXMBoard\Tests\Unit\Model;

use PXMBoard\Database\cDB;
use PXMBoard\Database\cDBResultSet;
use PXMBoard\Model\cMessageReadTracker;
use PXMBoard\Tests\TestCase\PxmTestCase;

/**
 * Unit test for cMessageReadTracker class
 * Tests input validation and DB interactions via mock injection.
 * See Integration/Model/cMessageReadTrackerTest.php for real-DB tests.
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cMessageReadTrackerTest extends PxmTestCase
{
    /**
     * Test markAsRead with invalid user ID returns false without touching DB
     *
     * @return void
     */
    public function test_markAsRead_withInvalidUserId_returnsFalse(): void
    {
        $mockDb = $this->createMock(cDB::class);
        $mockDb->expects($this->never())->method('executeQuery');

        $objTracker = new cMessageReadTracker($mockDb);
        $this->assertFalse($objTracker->markAsRead(0, 123));
        $this->assertFalse($objTracker->markAsRead(-1, 123));
    }

    /**
     * Test markAsRead with invalid message ID returns false without touching DB
     *
     * @return void
     */
    public function test_markAsRead_withInvalidMessageId_returnsFalse(): void
    {
        $mockDb = $this->createMock(cDB::class);
        $mockDb->expects($this->never())->method('executeQuery');

        $objTracker = new cMessageReadTracker($mockDb);
        $this->assertFalse($objTracker->markAsRead(123, 0));
        $this->assertFalse($objTracker->markAsRead(123, -1));
    }

    /**
     * Test markAsRead with valid IDs calls DB and returns true
     *
     * @return void
     */
    public function test_markAsRead_withValidIds_callsDbAndReturnsTrue(): void
    {
        $stubResultSet = $this->createStub(cDBResultSet::class);

        $mockDb = $this->createMock(cDB::class);
        $mockDb->expects($this->once())
               ->method('executeQuery')
               ->willReturn($stubResultSet);

        $objTracker = new cMessageReadTracker($mockDb);
        $this->assertTrue($objTracker->markAsRead(1, 2));
    }

    /**
     * Test markAsRead returns false when DB query fails
     *
     * @return void
     */
    public function test_markAsRead_whenDbFails_returnsFalse(): void
    {
        $mockDb = $this->createMock(cDB::class);
        $mockDb->expects($this->once())
               ->method('executeQuery')
               ->willReturn(null);

        $objTracker = new cMessageReadTracker($mockDb);
        $this->assertFalse($objTracker->markAsRead(1, 2));
    }

    /**
     * Test markThreadAsRead with invalid user ID returns false without touching DB
     *
     * @return void
     */
    public function test_markThreadAsRead_withInvalidUserId_returnsFalse(): void
    {
        $mockDb = $this->createMock(cDB::class);
        $mockDb->expects($this->never())->method('executeQuery');

        $objTracker = new cMessageReadTracker($mockDb);
        $this->assertFalse($objTracker->markThreadAsRead(0, 1));
        $this->assertFalse($objTracker->markThreadAsRead(-1, 1));
    }

    /**
     * Test getReadCount with invalid message ID returns zero without touching DB
     *
     * @return void
     */
    public function test_getReadCount_withInvalidMessageId_returnsZero(): void
    {
        $mockDb = $this->createMock(cDB::class);
        $mockDb->expects($this->never())->method('executeQuery');

        $objTracker = new cMessageReadTracker($mockDb);
        $this->assertSame(0, $objTracker->getReadCount(0));
        $this->assertSame(0, $objTracker->getReadCount(-1));
    }

    /**
     * Test cleanup calls DB and returns affected row count
     *
     * @return void
     */
    public function test_cleanup_withMockDb_returnsAffectedRows(): void
    {
        $mockResultSet = $this->createMock(cDBResultSet::class);
        $mockResultSet->expects($this->once())->method('getAffectedRows')->willReturn(5);
        $mockResultSet->expects($this->once())->method('freeResult');

        $mockDb = $this->createMock(cDB::class);
        $mockDb->expects($this->once())
               ->method('executeQuery')
               ->willReturn($mockResultSet);

        $objTracker = new cMessageReadTracker($mockDb);
        $this->assertSame(5, $objTracker->cleanup(30));
    }

    /**
     * Test cleanup returns zero when DB query fails
     *
     * @return void
     */
    public function test_cleanup_whenDbFails_returnsZero(): void
    {
        $mockDb = $this->createMock(cDB::class);
        $mockDb->expects($this->once())
               ->method('executeQuery')
               ->willReturn(null);

        $objTracker = new cMessageReadTracker($mockDb);
        $this->assertSame(0, $objTracker->cleanup(30));
    }
}
