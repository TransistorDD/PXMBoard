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
     * Test markAsRead with valid IDs calls DB and returns true.
     * managePartitions() performs a SELECT first; the INSERT IGNORE for the
     * partition tracking row returns 0 (race-condition path) so no DDL fires;
     * then the actual INSERT IGNORE into pxm_message_read is executed.
     *
     * @return void
     */
    public function test_markAsRead_withValidIds_callsDbAndReturnsTrue(): void
    {
        // Stub for the partition SELECT (returns no row → month not yet tracked)
        $stubEmptyResult = $this->createStub(cDBResultSet::class);
        $stubEmptyResult->method('getNextResultRowObject')->willReturn(false);

        // Stub for the partition INSERT IGNORE (returns 0 affected → race condition won by other)
        $stubInsertResult = $this->createMock(cDBResultSet::class);
        $stubInsertResult->expects($this->once())->method('getAffectedRows')->willReturn(0);
        $stubInsertResult->expects($this->once())->method('freeResult');

        // Stub for final INSERT IGNORE into pxm_message_read
        $stubReadResult = $this->createStub(cDBResultSet::class);

        $mockDb = $this->createMock(cDB::class);
        $mockDb->expects($this->exactly(3))
               ->method('executeQuery')
               ->willReturnOnConsecutiveCalls(
                   $stubEmptyResult, // SELECT from pxm_message_read_partition
                   $stubInsertResult, // INSERT IGNORE into pxm_message_read_partition
                   $stubReadResult    // INSERT IGNORE into pxm_message_read
               );

        $objTracker = new cMessageReadTracker($mockDb);
        $this->assertTrue($objTracker->markAsRead(1, 2));
    }

    /**
     * Test markAsRead returns false when the final INSERT into pxm_message_read fails.
     * managePartitions runs (partition already tracked → single SELECT), then the INSERT fails.
     *
     * @return void
     */
    public function test_markAsRead_whenDbFails_returnsFalse(): void
    {
        // Partition SELECT returns an existing row => managePartitions exits early
        $stubRowObj = new \stdClass();
        $stubRowObj->mrp_year_month = (int) date('ym');

        $stubPartitionResult = $this->createStub(cDBResultSet::class);
        $stubPartitionResult->method('getNextResultRowObject')->willReturn($stubRowObj);

        $mockDb = $this->createMock(cDB::class);
        $mockDb->expects($this->exactly(2))
               ->method('executeQuery')
               ->willReturnOnConsecutiveCalls(
                   $stubPartitionResult, // SELECT from pxm_message_read_partition
                   null                  // INSERT IGNORE into pxm_message_read fails
               );

        $objTracker = new cMessageReadTracker($mockDb);
        $this->assertFalse($objTracker->markAsRead(1, 2));
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
     * Test managePartitions returns early when partition row already exists
     *
     * @return void
     */
    public function test_managePartitions_whenPartitionExists_skipsAllDdl(): void
    {
        $stubRowObj = new \stdClass();
        $stubRowObj->mrp_year_month = (int) date('ym');

        $stubResult = $this->createStub(cDBResultSet::class);
        $stubResult->method('getNextResultRowObject')->willReturn($stubRowObj);

        $mockDb = $this->createMock(cDB::class);
        // Only the one SELECT query is expected; no INSERT, no DDL
        $mockDb->expects($this->once())
               ->method('executeQuery')
               ->willReturn($stubResult);

        $objTracker = new cMessageReadTracker($mockDb);
        $objTracker->managePartitions(); // Must not throw; no DDL executed
    }

    /**
     * Test managePartitions creates the partition when INSERT IGNORE succeeds
     * (no pre-existing partition, race won by current process)
     *
     * @return void
     */
    public function test_managePartitions_whenNewMonth_createsPartitionAndDropsOld(): void
    {
        // SELECT: no row found (month not tracked yet)
        $stubEmptyResult = $this->createStub(cDBResultSet::class);
        $stubEmptyResult->method('getNextResultRowObject')->willReturn(false);

        // INSERT IGNORE into pxm_message_read_partition: 1 row inserted (we won the race)
        $stubInsertResult = $this->createStub(cDBResultSet::class);
        $stubInsertResult->method('getAffectedRows')->willReturn(1);
        $stubInsertResult->method('freeResult');

        // ALTER TABLE ADD PARTITION
        $stubAlterResult = $this->createStub(cDBResultSet::class);

        // SELECT old partitions: none
        $stubOldResult = $this->createStub(cDBResultSet::class);
        $stubOldResult->method('getNextResultRowObject')->willReturn(false);

        $mockDb = $this->createMock(cDB::class);
        $mockDb->method('getDBType')->willReturn('MySQL');
        $mockDb->expects($this->exactly(4))
               ->method('executeQuery')
               ->willReturnOnConsecutiveCalls(
                   $stubEmptyResult, // SELECT from pxm_message_read_partition
                   $stubInsertResult, // INSERT IGNORE into pxm_message_read_partition
                   $stubAlterResult,  // ALTER TABLE ADD PARTITION
                   $stubOldResult     // SELECT old partitions to drop (empty)
               );

        $objTracker = new cMessageReadTracker($mockDb);
        $objTracker->managePartitions();
    }
}
