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
        $arrCases = \eError::cases();

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
        $this->assertSame('ungültiger modus', \eError::INVALID_MODE->value);
    }

    /**
     * Test USERNAME_UNKNOWN case
     *
     * @return void
     */
    public function test_usernameUnknown_hasCorrectValue(): void
    {
        $this->assertSame('nutzername unbekannt', \eError::USERNAME_UNKNOWN->value);
    }

    /**
     * Test INVALID_PASSWORD case
     *
     * @return void
     */
    public function test_invalidPassword_hasCorrectValue(): void
    {
        $this->assertSame('passwort ungültig', \eError::INVALID_PASSWORD->value);
    }

    /**
     * Test SUBJECT_MISSING case
     *
     * @return void
     */
    public function test_subjectMissing_hasCorrectValue(): void
    {
        $this->assertSame('subject fehlt', \eError::SUBJECT_MISSING->value);
    }

    /**
     * Test THREAD_CLOSED case
     *
     * @return void
     */
    public function test_threadClosed_hasCorrectValue(): void
    {
        $this->assertSame('dieser thread ist geschlossen', \eError::THREAD_CLOSED->value);
    }

    /**
     * Test NOT_AUTHORIZED case
     *
     * @return void
     */
    public function test_notAuthorized_hasCorrectValue(): void
    {
        $this->assertSame('sie sind nicht dazu berechtigt', \eError::NOT_AUTHORIZED->value);
    }

    /**
     * Test BOARD_CLOSED case
     *
     * @return void
     */
    public function test_boardClosed_hasCorrectValue(): void
    {
        $this->assertSame('dieses board ist geschlossen', \eError::BOARD_CLOSED->value);
    }

    /**
     * Test BOARD_READONLY case
     *
     * @return void
     */
    public function test_boardReadonly_hasCorrectValue(): void
    {
        $this->assertSame('dieses board ist im nur-lesen-modus', \eError::BOARD_READONLY->value);
    }

    /**
     * Test NOT_LOGGED_IN case
     *
     * @return void
     */
    public function test_notLoggedIn_hasCorrectValue(): void
    {
        $this->assertSame('sie sind nicht angemeldet', \eError::NOT_LOGGED_IN->value);
    }

    /**
     * Test USERNAME_ALREADY_EXISTS case
     *
     * @return void
     */
    public function test_usernameAlreadyExists_hasCorrectValue(): void
    {
        $this->assertSame('dieser nutzername ist bereits vergeben', \eError::USERNAME_ALREADY_EXISTS->value);
    }

    /**
     * Test CANNOT_MOVE_TO_SELF case (message movement errors)
     *
     * @return void
     */
    public function test_cannotMoveToSelf_hasCorrectValue(): void
    {
        $this->assertSame('Nachricht kann nicht zu sich selbst verschoben werden.', \eError::CANNOT_MOVE_TO_SELF->value);
    }

    /**
     * Test CANNOT_MOVE_TO_SUBTREE case
     *
     * @return void
     */
    public function test_cannotMoveToSubtree_hasCorrectValue(): void
    {
        $this->assertSame('Nachricht kann nicht in einen ihrer eigenen Unterbäume verschoben werden (Zirkelreferenz).', \eError::CANNOT_MOVE_TO_SUBTREE->value);
    }

    /**
     * Test enum values are strings
     *
     * @return void
     */
    public function test_allCases_haveStringValues(): void
    {
        $arrCases = \eError::cases();

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
        $objError = \eError::from('ungültiger modus');

        $this->assertSame(\eError::INVALID_MODE, $objError);
    }

    /**
     * Test all case names are unique
     *
     * @return void
     */
    public function test_allCases_haveUniqueNames(): void
    {
        $arrCases = \eError::cases();
        $arrNames = array_map(fn($case) => $case->name, $arrCases);

        $this->assertSame(count($arrNames), count(array_unique($arrNames)));
    }
}
