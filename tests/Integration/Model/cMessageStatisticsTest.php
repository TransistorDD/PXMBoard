<?php

declare(strict_types=1);

namespace PXMBoard\Tests\Integration\Model;

use PXMBoard\Model\cMessageStatistics;
use PXMBoard\Tests\TestCase\IntegrationTestCase;

/**
 * Integration test for cMessageStatistics class
 * Tests message statistics against the real test database
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cMessageStatisticsTest extends IntegrationTestCase
{
    private cMessageStatistics $statistics;

    protected function setUp(): void
    {
        parent::setUp();
        $this->statistics = new cMessageStatistics();
    }

    /**
     * Test getMessageCount returns a numeric value
     *
     * @return void
     */
    public function test_getMessageCount_returnsNumeric(): void
    {
        $iCount = $this->statistics->getMessageCount();

        $this->assertIsNumeric($iCount);
        $this->assertGreaterThanOrEqual(0, (int)$iCount);
    }

    /**
     * Test getMessageCount increases after inserting a message
     *
     * @return void
     */
    public function test_getMessageCount_increasesAfterInsert(): void
    {
        $iCountBefore = (int)$this->statistics->getMessageCount();

        $iUserId    = $this->insertUser();
        $iBoardId   = $this->insertBoard();
        $iThreadId  = $this->insertThread($iBoardId);
        $this->insertMessage($iThreadId, ['m_userid' => $iUserId]);

        $iCountAfter = (int)$this->statistics->getMessageCount();

        $this->assertSame($iCountBefore + 1, $iCountAfter);
    }

    /**
     * Test getPrivateMessageCount returns a numeric value
     *
     * @return void
     */
    public function test_getPrivateMessageCount_returnsNumeric(): void
    {
        $iCount = $this->statistics->getPrivateMessageCount();

        $this->assertIsNumeric($iCount);
        $this->assertGreaterThanOrEqual(0, (int)$iCount);
    }
}
