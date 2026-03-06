<?php

declare(strict_types=1);

namespace PXMBoard\Tests\Unit\Model;

use PXMBoard\Model\cMessageReadTracker;
use PXMBoard\Tests\TestCase\PxmTestCase;

/**
 * Unit test for cMessageReadTracker class
 * Tests input validation only (no database required).
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
     * Test markAsRead with invalid user ID returns false
     *
     * @return void
     */
    public function test_markAsRead_withInvalidUserId_returnsFalse(): void
    {
        $bResult = cMessageReadTracker::markAsRead(0, 123);
        $this->assertFalse($bResult);

        $bResult = cMessageReadTracker::markAsRead(-1, 123);
        $this->assertFalse($bResult);
    }

    /**
     * Test markAsRead with invalid message ID returns false
     *
     * @return void
     */
    public function test_markAsRead_withInvalidMessageId_returnsFalse(): void
    {
        $bResult = cMessageReadTracker::markAsRead(123, 0);
        $this->assertFalse($bResult);

        $bResult = cMessageReadTracker::markAsRead(123, -1);
        $this->assertFalse($bResult);
    }

    /**
     * Test getReadCount with invalid message ID returns zero
     *
     * @return void
     */
    public function test_getReadCount_withInvalidMessageId_returnsZero(): void
    {
        $iCount = cMessageReadTracker::getReadCount(0);
        $this->assertSame(0, $iCount);

        $iCount = cMessageReadTracker::getReadCount(-1);
        $this->assertSame(0, $iCount);
    }
}
