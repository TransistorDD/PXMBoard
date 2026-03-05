<?php
/**
 * Unit tests for UserStatus Enum
 * Tests user status values and methods
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
declare(strict_types=1);

namespace PXMBoard\Tests\Unit\Enum;

use PHPUnit\Framework\TestCase;

class UserStatusTest extends TestCase
{
    /**
     * Load translations before each test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        \cTranslator::load('de');
    }


    /**
     * Test ACTIVE value is 1
     *
     * @return void
     */
    public function test_activeValue_isOne(): void
    {
        $this->assertSame(1, \eUserStatus::ACTIVE->value);
    }

    /**
     * Test NOT_ACTIVATED value is 2
     *
     * @return void
     */
    public function test_notActivatedValue_isTwo(): void
    {
        $this->assertSame(2, \eUserStatus::NOT_ACTIVATED->value);
    }

    /**
     * Test DISABLED value is 3
     *
     * @return void
     */
    public function test_disabledValue_isThree(): void
    {
        $this->assertSame(3, \eUserStatus::DISABLED->value);
    }

    /**
     * Test getLabel for ACTIVE
     *
     * @return void
     */
    public function test_getLabel_forActive_returnsString(): void
    {
        $this->assertSame('Aktiv', \eUserStatus::ACTIVE->getLabel());
    }

    /**
     * Test getLabel for NOT_ACTIVATED
     *
     * @return void
     */
    public function test_getLabel_forNotActivated_returnsString(): void
    {
        $this->assertSame('Nicht aktiviert', \eUserStatus::NOT_ACTIVATED->getLabel());
    }

    /**
     * Test getLabel for DISABLED
     *
     * @return void
     */
    public function test_getLabel_forDisabled_returnsString(): void
    {
        $this->assertSame('Gesperrt', \eUserStatus::DISABLED->getLabel());
    }

    /**
     * Test getAll returns all cases
     *
     * @return void
     */
    public function test_getAll_returnsAllCases(): void
    {
        $arrAll = \eUserStatus::getAll();
        $this->assertIsArray($arrAll);
        $this->assertCount(3, $arrAll);
        $this->assertArrayHasKey(1, $arrAll);
        $this->assertArrayHasKey(2, $arrAll);
        $this->assertArrayHasKey(3, $arrAll);
    }

    /**
     * Test from method with valid integer
     *
     * @return void
     */
    public function test_from_withValidInt_returnsEnum(): void
    {
        $status = \eUserStatus::from(1);
        $this->assertSame(\eUserStatus::ACTIVE, $status);
    }
}
