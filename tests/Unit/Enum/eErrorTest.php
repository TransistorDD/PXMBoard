<?php

/**
 * Unit test for eError enum
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
declare(strict_types=1);

namespace PXMBoard\Tests\Unit\Enum;

use PHPUnit\Framework\TestCase;

class eErrorTest extends TestCase
{
    /**
     * Test enum has expected cases
     *
     * @return void
     */
    public function test_enum_hasExpectedCases(): void
    {
        $arrCases = \eErrorKeys::cases();

        $this->assertNotEmpty($arrCases);
        $this->assertGreaterThan(30, count($arrCases));
    }

    /**
     * Test INVALID_MODE case
     *
     * @return void
     */
    public function test_invalidMode_hasCorrectValue(): void
    {
        $this->assertSame('error.invalid_mode', \eErrorKeys::INVALID_MODE->value);
    }

    /**
     * Test USERNAME_UNKNOWN case
     *
     * @return void
     */
    public function test_usernameUnknown_hasCorrectValue(): void
    {
        $this->assertSame('error.username_unknown', \eErrorKeys::USERNAME_UNKNOWN->value);
    }

    /**
     * Test INVALID_PASSWORD case
     *
     * @return void
     */
    public function test_invalidPassword_hasCorrectValue(): void
    {
        $this->assertSame('error.invalid_password', \eErrorKeys::INVALID_PASSWORD->value);
    }

    /**
     * Test SUBJECT_MISSING case
     *
     * @return void
     */
    public function test_subjectMissing_hasCorrectValue(): void
    {
        $this->assertSame('error.subject_missing', \eErrorKeys::SUBJECT_MISSING->value);
    }

    /**
     * Test THREAD_CLOSED case
     *
     * @return void
     */
    public function test_threadClosed_hasCorrectValue(): void
    {
        $this->assertSame('error.thread_closed', \eErrorKeys::THREAD_CLOSED->value);
    }

    /**
     * Test NOT_AUTHORIZED case
     *
     * @return void
     */
    public function test_notAuthorized_hasCorrectValue(): void
    {
        $this->assertSame('error.not_authorized', \eErrorKeys::NOT_AUTHORIZED->value);
    }

    /**
     * Test BOARD_CLOSED case
     *
     * @return void
     */
    public function test_boardClosed_hasCorrectValue(): void
    {
        $this->assertSame('error.board_closed', \eErrorKeys::BOARD_CLOSED->value);
    }

    /**
     * Test BOARD_READONLY case
     *
     * @return void
     */
    public function test_boardReadonly_hasCorrectValue(): void
    {
        $this->assertSame('error.board_readonly', \eErrorKeys::BOARD_READONLY->value);
    }

    /**
     * Test NOT_LOGGED_IN case
     *
     * @return void
     */
    public function test_notLoggedIn_hasCorrectValue(): void
    {
        $this->assertSame('error.not_logged_in', \eErrorKeys::NOT_LOGGED_IN->value);
    }

    /**
     * Test USERNAME_ALREADY_EXISTS case
     *
     * @return void
     */
    public function test_usernameAlreadyExists_hasCorrectValue(): void
    {
        $this->assertSame('error.username_already_exists', \eErrorKeys::USERNAME_ALREADY_EXISTS->value);
    }

    /**
     * Test CANNOT_MOVE_TO_SELF case (message movement errors)
     *
     * @return void
     */
    public function test_cannotMoveToSelf_hasCorrectValue(): void
    {
        $this->assertSame('error.cannot_move_to_self', \eErrorKeys::CANNOT_MOVE_TO_SELF->value);
    }

    /**
     * Test CANNOT_MOVE_TO_SUBTREE case
     *
     * @return void
     */
    public function test_cannotMoveToSubtree_hasCorrectValue(): void
    {
        $this->assertSame('error.cannot_move_to_subtree', \eErrorKeys::CANNOT_MOVE_TO_SUBTREE->value);
    }

    /**
     * Test enum values are strings
     *
     * @return void
     */
    public function test_allCases_haveStringValues(): void
    {
        $arrCases = \eErrorKeys::cases();

        foreach ($arrCases as $objCase) {
            $this->assertIsString($objCase->value);
            $this->assertNotEmpty($objCase->value);
        }
    }

    /**
     * Test enum can be instantiated from string
     *
     * @return void
     */
    public function test_from_withValidValue_returnsCase(): void
    {
        $objError = \eErrorKeys::from('error.invalid_mode');

        $this->assertSame(\eErrorKeys::INVALID_MODE, $objError);
    }

    /**
     * Test all case names are unique
     *
     * @return void
     */
    public function test_allCases_haveUniqueNames(): void
    {
        $arrCases = \eErrorKeys::cases();
        $arrNames = array_map(fn ($case) => $case->name, $arrCases);

        $this->assertSame(count($arrNames), count(array_unique($arrNames)));
    }
}
