<?php
/**
 * Unit tests for cStringValidations
 * Tests string truncation, alpha check, and length table
 *
 * @author Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright Torsten Rentsch 2001 - 2026
 */
declare(strict_types=1);

namespace PXMBoard\Tests\Unit\Validation;

use PHPUnit\Framework\TestCase;

class cStringValidationsTest extends TestCase
{
    /**
     * @var \cStringValidations validator instance
     */
    private $validator;

    /**
     * Set up validator before each test
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new \cStringValidations();
    }

    // -------------------------------------------------------------------------
    // getLength()
    // -------------------------------------------------------------------------

    public function test_getLength_forUsername_returns30(): void
    {
        $this->assertSame(30, $this->validator->getLength('username'));
    }

    public function test_getLength_forSubject_returns90(): void
    {
        $this->assertSame(90, $this->validator->getLength('subject'));
    }

    public function test_getLength_forBody_returns60000(): void
    {
        $this->assertSame(60000, $this->validator->getLength('body'));
    }

    public function test_getLength_forEmail_returns100(): void
    {
        $this->assertSame(100, $this->validator->getLength('email'));
    }

    public function test_getLength_forPassword_returns20(): void
    {
        $this->assertSame(20, $this->validator->getLength('password'));
    }

    public function test_getLength_forUnknownType_returnsNull(): void
    {
        $this->assertNull($this->validator->getLength('nonexistenttype'));
    }

    // -------------------------------------------------------------------------
    // getAllLimits()
    // -------------------------------------------------------------------------

    public function test_getAllLimits_returnsArray(): void
    {
        $arrLimits = $this->validator->getAllLimits();
        $this->assertIsArray($arrLimits);
    }

    public function test_getAllLimits_containsExpectedKeys(): void
    {
        $arrLimits = $this->validator->getAllLimits();
        foreach (['username', 'password', 'email', 'subject', 'body', 'firstname', 'lastname', 'city', 'signature'] as $sKey) {
            $this->assertArrayHasKey($sKey, $arrLimits, "Missing key: {$sKey}");
        }
    }

    public function test_getAllLimits_valuesMatchGetLength(): void
    {
        $arrLimits = $this->validator->getAllLimits();
        foreach ($arrLimits as $sKey => $iLimit) {
            $this->assertSame($iLimit, $this->validator->getLength($sKey));
        }
    }

    // -------------------------------------------------------------------------
    // truncate()
    // -------------------------------------------------------------------------

    public function test_truncate_forSubjectType_truncatesAtLimit(): void
    {
        $sResult = $this->validator->truncate(str_repeat('a', 100), 'subject');
        $this->assertSame(90, mb_strlen($sResult));
    }

    public function test_truncate_forUsernameType_truncatesAtLimit(): void
    {
        $sResult = $this->validator->truncate(str_repeat('x', 50), 'username');
        $this->assertSame(30, mb_strlen($sResult));
    }

    public function test_truncate_withShortString_returnsUnchanged(): void
    {
        $sInput = 'Hello';
        $this->assertSame($sInput, $this->validator->truncate($sInput, 'username'));
    }

    public function test_truncate_withExactLimitString_returnsUnchanged(): void
    {
        $sInput = str_repeat('a', 30);
        $this->assertSame($sInput, $this->validator->truncate($sInput, 'username'));
    }

    public function test_truncate_withUnknownType_returnsUnchanged(): void
    {
        $sInput = str_repeat('a', 1000);
        $this->assertSame($sInput, $this->validator->truncate($sInput, 'unknowntype'));
    }

    public function test_truncate_withMultiByteChars_truncatesAtMbBoundary(): void
    {
        // Each "ä" is 2 bytes in UTF-8 but 1 character; ensure mb_substr is used
        $sInput = str_repeat('ä', 50);   // 50 mb-chars, 100 bytes
        $sResult = $this->validator->truncate($sInput, 'username'); // limit = 30 mb-chars
        $this->assertSame(30, mb_strlen($sResult));
        $this->assertSame(str_repeat('ä', 30), $sResult);
    }

    public function test_truncate_withMultiByteChars_doesNotSplitSequence(): void
    {
        // Ensure no garbled bytes at the cut point
        $sInput = str_repeat('€', 40);   // 40 mb-chars, 120 bytes
        $sResult = $this->validator->truncate($sInput, 'username'); // limit = 30
        $this->assertSame(str_repeat('€', 30), $sResult);
        $this->assertTrue(mb_check_encoding($sResult, 'UTF-8'));
    }

    // -------------------------------------------------------------------------
    // isAlpha()
    // -------------------------------------------------------------------------

    public function test_isAlpha_withPureAlphaString_returnsTrue(): void
    {
        $this->assertTrue($this->validator->isAlpha('MySql'));
    }

    public function test_isAlpha_withDigits_returnsFalse(): void
    {
        $this->assertFalse($this->validator->isAlpha('MySql123'));
    }

    public function test_isAlpha_withSpecialChars_returnsFalse(): void
    {
        $this->assertFalse($this->validator->isAlpha('My-Sql'));
    }

    public function test_isAlpha_withEmptyString_returnsFalse(): void
    {
        $this->assertFalse($this->validator->isAlpha(''));
    }

    public function test_isAlpha_withUppercaseOnly_returnsTrue(): void
    {
        $this->assertTrue($this->validator->isAlpha('MYSQL'));
    }

    public function test_isAlpha_withLowercaseOnly_returnsTrue(): void
    {
        $this->assertTrue($this->validator->isAlpha('mysql'));
    }
}
