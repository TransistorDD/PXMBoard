<?php
/**
 * Unit test for cInputHandler class
 *
 * @author Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright Torsten Rentsch 2001 - 2026
 */
declare(strict_types=1);

namespace PXMBoard\Tests\Unit\Validation;

use PHPUnit\Framework\TestCase;

class cInputHandlerTest extends TestCase
{
    private \cInputHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->handler = new \cInputHandler();
        // Clear superglobals
        $_POST = [];
        $_GET = [];
    }

    protected function tearDown(): void
    {
        $_POST = [];
        $_GET = [];
        parent::tearDown();
    }

    /**
     * Test getStringFormVar from POST
     *
     * @return void
     */
    public function test_getStringFormVar_fromPost_returnsValue(): void
    {
        $_POST['test'] = 'Hello World';

        $sValue = $this->handler->getStringFormVar('test', '', true, false);

        $this->assertSame('Hello World', $sValue);
    }

    /**
     * Test getStringFormVar from GET
     *
     * @return void
     */
    public function test_getStringFormVar_fromGet_returnsValue(): void
    {
        $_GET['test'] = 'Test Value';

        $sValue = $this->handler->getStringFormVar('test', '', false, true);

        $this->assertSame('Test Value', $sValue);
    }

    /**
     * Test getStringFormVar prefers POST over GET
     *
     * @return void
     */
    public function test_getStringFormVar_withBothPostAndGet_prefersPost(): void
    {
        $_POST['test'] = 'From POST';
        $_GET['test'] = 'From GET';

        $sValue = $this->handler->getStringFormVar('test', '', true, true);

        $this->assertSame('From POST', $sValue);
    }

    /**
     * Test getStringFormVar normalizes line endings
     *
     * @return void
     */
    public function test_getStringFormVar_normalizesCRLF_toNewlines(): void
    {
        $_POST['test'] = "Line1\r\nLine2\rLine3\nLine4";

        $sValue = $this->handler->getStringFormVar('test', '', true, false);

        $this->assertSame("Line1\nLine2\nLine3\nLine4", $sValue);
    }

    /**
     * Test getStringFormVar applies trim function
     *
     * @return void
     */
    public function test_getStringFormVar_withTrimFunction_trimsValue(): void
    {
        $_POST['test'] = '  Hello World  ';

        $sValue = $this->handler->getStringFormVar('test', '', true, false, 'trim');

        $this->assertSame('Hello World', $sValue);
    }

    /**
     * Test getStringFormVar applies validation (truncation)
     *
     * @return void
     */
    public function test_getStringFormVar_withValidation_appliesValidation(): void
    {
        $_POST['test'] = str_repeat('a', 200);

        $sValue = $this->handler->getStringFormVar('test', 'subject', true, false);

        // subject validation truncates to 90 characters
        $this->assertSame(90, mb_strlen($sValue));
    }

    /**
     * Test getStringFormVar with missing variable returns empty string
     *
     * @return void
     */
    public function test_getStringFormVar_withMissingVariable_returnsEmpty(): void
    {
        $sValue = $this->handler->getStringFormVar('nonexistent', '', true, true);

        $this->assertSame('', $sValue);
    }

    /**
     * Test getStringFormVar removes control characters (not replace with ?)
     *
     * @return void
     */
    public function test_getStringFormVar_filtersControlChars_removesThemCompletely(): void
    {
        $_POST['test'] = "Valid\x01Chars\x1F";

        $sValue = $this->handler->getStringFormVar('test', '', true, false);

        $this->assertSame('ValidChars', $sValue);
        $this->assertStringNotContainsString('?', $sValue);
    }

    /**
     * Test that tab character (\x09) is removed by the character filter
     *
     * @return void
     */
    public function test_getStringFormVar_withTabCharacter_removesTab(): void
    {
        $_POST['test'] = "before\tafter";

        $sValue = $this->handler->getStringFormVar('test', '', true, false);

        $this->assertSame('beforeafter', $sValue);
    }

    /**
     * Test that newline (\x0A) is preserved by the character filter
     *
     * @return void
     */
    public function test_getStringFormVar_withNewline_preservesNewline(): void
    {
        $_POST['test'] = "line1\nline2";

        $sValue = $this->handler->getStringFormVar('test', '', true, false);

        $this->assertStringContainsString("\n", $sValue);
        $this->assertSame("line1\nline2", $sValue);
    }

    /**
     * Test that unicode characters are preserved
     *
     * @return void
     */
    public function test_getStringFormVar_withUnicodeChars_preservesUnicode(): void
    {
        $_POST['test'] = 'Schöne Grüße aus München';

        $sValue = $this->handler->getStringFormVar('test', '', true, false);

        $this->assertSame('Schöne Grüße aus München', $sValue);
    }

    /**
     * Test 'type' validation passes through valid alpha string
     *
     * @return void
     */
    public function test_getStringFormVar_withTypeValidation_andAlphaString_returnsValue(): void
    {
        $_POST['test'] = 'MySql';

        $sValue = $this->handler->getStringFormVar('test', 'type', true, false);

        $this->assertSame('MySql', $sValue);
    }

    /**
     * Test 'type' validation empties non-alpha string
     *
     * @return void
     */
    public function test_getStringFormVar_withTypeValidation_andNonAlphaString_returnsEmpty(): void
    {
        $_POST['test'] = 'MySql123';

        $sValue = $this->handler->getStringFormVar('test', 'type', true, false);

        $this->assertSame('', $sValue);
    }

    /**
     * Test getIntFormVar from POST
     *
     * @return void
     */
    public function test_getIntFormVar_fromPost_returnsInteger(): void
    {
        $_POST['test'] = '42';

        $iValue = $this->handler->getIntFormVar('test', true, false);

        $this->assertSame(42, $iValue);
    }

    /**
     * Test getIntFormVar from GET
     *
     * @return void
     */
    public function test_getIntFormVar_fromGet_returnsInteger(): void
    {
        $_GET['test'] = '123';

        $iValue = $this->handler->getIntFormVar('test', false, true);

        $this->assertSame(123, $iValue);
    }

    /**
     * Test getIntFormVar with negative value
     *
     * @return void
     */
    public function test_getIntFormVar_withNegativeValue_returnsNegative(): void
    {
        $_POST['test'] = '-42';

        $iValue = $this->handler->getIntFormVar('test', true, false, false);

        $this->assertSame(-42, $iValue);
    }

    /**
     * Test getIntFormVar forces positive when requested
     *
     * @return void
     */
    public function test_getIntFormVar_withForcePositive_convertsNegativeToZero(): void
    {
        $_POST['test'] = '-42';

        $iValue = $this->handler->getIntFormVar('test', true, false, true);

        $this->assertSame(0, $iValue);
    }

    /**
     * Test getIntFormVar with missing variable returns zero
     *
     * @return void
     */
    public function test_getIntFormVar_withMissingVariable_returnsZero(): void
    {
        $iValue = $this->handler->getIntFormVar('nonexistent', true, true, false);

        $this->assertSame(0, $iValue);
    }

    /**
     * Test getIntFormVar with string converts to zero
     *
     * @return void
     */
    public function test_getIntFormVar_withNonNumericString_returnsZero(): void
    {
        $_POST['test'] = 'not a number';

        $iValue = $this->handler->getIntFormVar('test', true, false);

        $this->assertSame(0, $iValue);
    }

    /**
     * Test getArrFormVar from POST
     *
     * @return void
     */
    public function test_getArrFormVar_fromPost_returnsArray(): void
    {
        $_POST['test'] = ['value1', 'value2', 'value3'];

        $arrValue = $this->handler->getArrFormVar('test', true, false);

        $this->assertSame(['value1', 'value2', 'value3'], $arrValue);
    }

    /**
     * Test getArrFormVar from GET
     *
     * @return void
     */
    public function test_getArrFormVar_fromGet_returnsArray(): void
    {
        $_GET['test'] = ['a', 'b', 'c'];

        $arrValue = $this->handler->getArrFormVar('test', false, true);

        $this->assertSame(['a', 'b', 'c'], $arrValue);
    }

    /**
     * Test getArrFormVar with unique values
     *
     * @return void
     */
    public function test_getArrFormVar_withForceUnique_removiesDuplicates(): void
    {
        $_POST['test'] = ['a', 'b', 'a', 'c', 'b'];

        $arrValue = $this->handler->getArrFormVar('test', true, false, true);

        $this->assertCount(3, $arrValue);
        $this->assertContains('a', $arrValue);
        $this->assertContains('b', $arrValue);
        $this->assertContains('c', $arrValue);
    }

    /**
     * Test getArrFormVar with missing variable returns empty array
     *
     * @return void
     */
    public function test_getArrFormVar_withMissingVariable_returnsEmptyArray(): void
    {
        $arrValue = $this->handler->getArrFormVar('nonexistent', true, true);

        $this->assertSame([], $arrValue);
    }

    /**
     * Test getArrFormVar applies trim function
     *
     * @return void
     */
    public function test_getArrFormVar_withTrimFunction_trimsAllValues(): void
    {
        $_POST['test'] = ['  value1  ', '  value2  '];

        $arrValue = $this->handler->getArrFormVar('test', true, false, false, 'trim');

        $this->assertSame(['value1', 'value2'], $arrValue);
    }

    /**
     * Test getInputSize delegates to string validations
     *
     * @return void
     */
    public function test_getInputSize_withSubjectType_returnsCorrectLength(): void
    {
        $iSize = $this->handler->getInputSize('subject');

        $this->assertSame(90, $iSize);
    }

    /**
     * Test getInputSizes returns array with all keys
     *
     * @return void
     */
    public function test_getInputSizes_returnsArrayWithExpectedKeys(): void
    {
        $arrSizes = $this->handler->getInputSizes();

        $this->assertIsArray($arrSizes);
        foreach (['username', 'password', 'email', 'subject', 'body'] as $sKey) {
            $this->assertArrayHasKey($sKey, $arrSizes, "Missing key: {$sKey}");
        }
    }
}
