<?php

declare(strict_types=1);

namespace PXMBoard\Tests\Unit\Validation;

use PHPUnit\Framework\TestCase;
use PXMBoard\Validation\cStringValidations;

/**
 * Unit tests for cStringValidations
 * Tests string truncation, alpha check, and length table
 *
 * @author Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright Torsten Rentsch 2001 - 2026
 */
class cStringValidationsTest extends TestCase
{
    // -------------------------------------------------------------------------
    // getLength()
    // -------------------------------------------------------------------------

    public function test_getLength_forUsername_returns30(): void
    {
        $this->assertSame(30, cStringValidations::getLength('username'));
    }

    public function test_getLength_forSubject_returns90(): void
    {
        $this->assertSame(90, cStringValidations::getLength('subject'));
    }

    public function test_getLength_forBody_returns60000(): void
    {
        $this->assertSame(60000, cStringValidations::getLength('body'));
    }

    public function test_getLength_forEmail_returns100(): void
    {
        $this->assertSame(100, cStringValidations::getLength('email'));
    }

    public function test_getLength_forPassword_returns20(): void
    {
        $this->assertSame(20, cStringValidations::getLength('password'));
    }

    public function test_getLength_forUnknownType_returnsNull(): void
    {
        $this->assertNull(cStringValidations::getLength('nonexistenttype'));
    }

    // -------------------------------------------------------------------------
    // getAllLimits()
    // -------------------------------------------------------------------------

    public function test_getAllLimits_returnsArray(): void
    {
        $this->assertIsArray(cStringValidations::getAllLimits());
    }

    public function test_getAllLimits_containsExpectedKeys(): void
    {
        $arrLimits = cStringValidations::getAllLimits();
        foreach (['username', 'password', 'email', 'subject', 'body', 'firstname', 'lastname', 'city', 'signature'] as $sKey) {
            $this->assertArrayHasKey($sKey, $arrLimits, "Missing key: {$sKey}");
        }
    }

    public function test_getAllLimits_valuesMatchGetLength(): void
    {
        foreach (cStringValidations::getAllLimits() as $sKey => $iLimit) {
            $this->assertSame($iLimit, cStringValidations::getLength($sKey));
        }
    }

    // -------------------------------------------------------------------------
    // truncate()
    // -------------------------------------------------------------------------

    public function test_truncate_forSubjectType_truncatesAtLimit(): void
    {
        $sResult = cStringValidations::truncate(str_repeat('a', 100), 'subject');
        $this->assertSame(90, mb_strlen($sResult));
    }

    public function test_truncate_forUsernameType_truncatesAtLimit(): void
    {
        $sResult = cStringValidations::truncate(str_repeat('x', 50), 'username');
        $this->assertSame(30, mb_strlen($sResult));
    }

    public function test_truncate_withShortString_returnsUnchanged(): void
    {
        $sInput = 'Hello';
        $this->assertSame($sInput, cStringValidations::truncate($sInput, 'username'));
    }

    public function test_truncate_withExactLimitString_returnsUnchanged(): void
    {
        $sInput = str_repeat('a', 30);
        $this->assertSame($sInput, cStringValidations::truncate($sInput, 'username'));
    }

    public function test_truncate_withUnknownType_returnsUnchanged(): void
    {
        $sInput = str_repeat('a', 1000);
        $this->assertSame($sInput, cStringValidations::truncate($sInput, 'unknowntype'));
    }

    public function test_truncate_withMultiByteChars_truncatesAtMbBoundary(): void
    {
        // Each "ä" is 2 bytes in UTF-8 but 1 character; ensure mb_substr is used
        $sInput = str_repeat('ä', 50);   // 50 mb-chars, 100 bytes
        $sResult = cStringValidations::truncate($sInput, 'username'); // limit = 30 mb-chars
        $this->assertSame(30, mb_strlen($sResult));
        $this->assertSame(str_repeat('ä', 30), $sResult);
    }

    public function test_truncate_withMultiByteChars_doesNotSplitSequence(): void
    {
        // Ensure no garbled bytes at the cut point
        $sInput = str_repeat('€', 40);   // 40 mb-chars, 120 bytes
        $sResult = cStringValidations::truncate($sInput, 'username'); // limit = 30
        $this->assertSame(str_repeat('€', 30), $sResult);
        $this->assertTrue(mb_check_encoding($sResult, 'UTF-8'));
    }

    // -------------------------------------------------------------------------
    // isAlpha()
    // -------------------------------------------------------------------------

    public function test_isAlpha_withPureAlphaString_returnsTrue(): void
    {
        $this->assertTrue(cStringValidations::isAlpha('MySql'));
    }

    public function test_isAlpha_withDigits_returnsFalse(): void
    {
        $this->assertFalse(cStringValidations::isAlpha('MySql123'));
    }

    public function test_isAlpha_withSpecialChars_returnsFalse(): void
    {
        $this->assertFalse(cStringValidations::isAlpha('My-Sql'));
    }

    public function test_isAlpha_withEmptyString_returnsFalse(): void
    {
        $this->assertFalse(cStringValidations::isAlpha(''));
    }

    public function test_isAlpha_withUppercaseOnly_returnsTrue(): void
    {
        $this->assertTrue(cStringValidations::isAlpha('MYSQL'));
    }

    public function test_isAlpha_withLowercaseOnly_returnsTrue(): void
    {
        $this->assertTrue(cStringValidations::isAlpha('mysql'));
    }
}
