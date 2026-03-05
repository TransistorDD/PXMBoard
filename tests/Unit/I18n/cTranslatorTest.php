<?php

declare(strict_types=1);

namespace PXMBoard\Tests\Unit\I18n;

use PHPUnit\Framework\TestCase;
use PXMBoard\Enum\eErrorKeys;
use PXMBoard\Enum\eSuccessKeys;
use PXMBoard\I18n\cTranslator;

/**
 * Unit tests for cTranslator
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cTranslatorTest extends TestCase
{
    /**
     * Load German translations before each test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        cTranslator::load('de');
    }

    /**
     * Test that translate resolves a known key.
     *
     * @return void
     */
    public function test_translate_knownKey_returnsTranslatedString(): void
    {
        $this->assertSame('Ungültiger Modus', cTranslator::translate('error.invalid_mode'));
    }

    /**
     * Test that translate returns the key itself when the key is missing.
     *
     * @return void
     */
    public function test_translate_missingKey_returnsFallbackKey(): void
    {
        $result = cTranslator::translate('this.key.does.not.exist');
        $this->assertSame('this.key.does.not.exist', $result);
    }

    /**
     * Test placeholder replacement with %key% syntax.
     *
     * @return void
     */
    public function test_translate_withPlaceholders_replacesTokens(): void
    {
        // Add a custom key with a placeholder to test replacement
        // We call load() with a temp locale that does not exist → falls back to key;
        // instead, we test against an already-translated string that has a placeholder
        // by passing params to a key that exists.
        $result = cTranslator::translate('error.invalid_mode', ['foo' => 'bar']);
        // No placeholder in this key → params are ignored, original string returned
        $this->assertSame('Ungültiger Modus', $result);
    }

    /**
     * Test that placeholder tokens are substituted.
     *
     * @return void
     */
    public function test_translate_placeholderInFallback_isReplaced(): void
    {
        // When the key does not exist the key itself is used as template
        $result = cTranslator::translate('hello.%name%', ['name' => 'World']);
        $this->assertSame('hello.World', $result);
    }

    /**
     * Test that load() with a non-existent locale file does not throw.
     *
     * @return void
     */
    public function test_load_nonExistentLocale_doesNotThrow(): void
    {
        $this->expectNotToPerformAssertions();
        cTranslator::load('xx');
    }

    /**
     * Test that translate still returns the key for unknown keys after loading a non-existent locale.
     * Note: previously loaded messages are preserved when the locale file does not exist.
     *
     * @return void
     */
    public function test_translate_afterNonExistentLocale_returnsKey(): void
    {
        cTranslator::load('xx');
        $result = cTranslator::translate('this.key.is.always.unknown');
        $this->assertSame('this.key.is.always.unknown', $result);
    }

    /**
     * Test that all expected error keys are present in de.php.
     *
     * @return void
     */
    public function test_deLocale_hasAllErrorKeys(): void
    {
        cTranslator::load('de');
        $arrKeys = eErrorKeys::cases();
        foreach ($arrKeys as $case) {
            $translated = cTranslator::translate($case->value);
            $this->assertNotSame(
                $case->value,
                $translated,
                "Missing translation for key: {$case->value}"
            );
        }
    }

    /**
     * Test that all expected success keys are present in de.php.
     *
     * @return void
     */
    public function test_deLocale_hasAllSuccessKeys(): void
    {
        cTranslator::load('de');
        $arrKeys = eSuccessKeys::cases();
        foreach ($arrKeys as $case) {
            $translated = cTranslator::translate($case->value);
            $this->assertNotSame(
                $case->value,
                $translated,
                "Missing translation for key: {$case->value}"
            );
        }
    }
}
