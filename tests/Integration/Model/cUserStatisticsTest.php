<?php
/**
 * Integration test for cUserStatistics class
 * Tests user statistics against the real test database
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
declare(strict_types=1);

namespace PXMBoard\Tests\Integration\Model;

use PXMBoard\Tests\TestCase\IntegrationTestCase;

class cUserStatisticsTest extends IntegrationTestCase
{
    private \cUserStatistics $statistics;

    protected function setUp(): void
    {
        parent::setUp();
        $this->statistics = new \cUserStatistics();
    }

    /**
     * Test getMemberCount returns a numeric value
     *
     * @return void
     */
    public function test_getMemberCount_returnsNumeric(): void
    {
        $iCount = $this->statistics->getMemberCount();

        $this->assertIsNumeric($iCount);
        $this->assertGreaterThanOrEqual(0, (int)$iCount);
    }

    /**
     * Test getMemberCount increases after inserting a user
     *
     * @return void
     */
    public function test_getMemberCount_increasesAfterInsert(): void
    {
        $iCountBefore = (int)$this->statistics->getMemberCount();

        $this->insertUser();

        $iCountAfter = (int)$this->statistics->getMemberCount();

        $this->assertSame($iCountBefore + 1, $iCountAfter);
    }

    /**
     * Test getNewestMember returns the most recently inserted user
     *
     * @return void
     */
    public function test_getNewestMember_returnsNewestUser(): void
    {
        $this->insertUser(['u_username' => 'newest_user_stats_test']);

        $objUser = $this->statistics->getNewestMember();

        $this->assertInstanceOf(\cUser::class, $objUser);
        $this->assertSame('newest_user_stats_test', $objUser->getUserName());
    }
}
