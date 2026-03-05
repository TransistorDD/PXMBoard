<?php
/**
 * Integration test for cBoard class
 * Tests board data loading against the real test database
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
declare(strict_types=1);

namespace PXMBoard\Tests\Integration\Model;

use PXMBoard\Tests\TestCase\IntegrationTestCase;

class cBoardTest extends IntegrationTestCase
{
    /**
     * Test loading board by ID
     *
     * @return void
     */
    public function test_loadDataById_withValidId_loadsBoard(): void
    {
        $iBoardId = $this->insertBoard([
            'b_name'        => 'General Discussion',
            'b_description' => 'Talk about anything',
            'b_status'      => 1,
        ]);

        $objBoard = new \cBoard();
        $bResult = $objBoard->loadDataById($iBoardId);

        $this->assertTrue($bResult);
        $this->assertSame($iBoardId, $objBoard->getId());
        $this->assertSame('General Discussion', $objBoard->getName());
    }

    /**
     * Test loading board with non-existent ID returns false
     *
     * @return void
     */
    public function test_loadDataById_withInvalidId_returnsFalse(): void
    {
        $objBoard = new \cBoard();
        $bResult = $objBoard->loadDataById(999999);

        $this->assertFalse($bResult);
    }

    /**
     * Test board status enum integration
     *
     * @return void
     */
    public function test_getStatus_returnsBoardStatus(): void
    {
        $iBoardId = $this->insertBoard(['b_status' => 1]);

        $objBoard = new \cBoard();
        $objBoard->loadDataById($iBoardId);

        $objStatus = $objBoard->getStatus();
        $this->assertInstanceOf(\eBoardStatus::class, $objStatus);
        $this->assertSame(\eBoardStatus::PUBLIC, $objStatus);
    }
}
