<?php
/**
 * Unit tests for cPxmParser
 * Tests PXM markup to HTML conversion
 *
 * @author Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright Torsten Rentsch 2001 - 2026
 */
declare(strict_types=1);

namespace PXMBoard\Tests\Unit\Parser;

use PHPUnit\Framework\TestCase;

class cPxmParserTest extends TestCase
{
    /**
     * @var \cPxmParser parser instance
     */
    private $parser;

    /**
     * Set up parser before each test
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new \cPxmParser();
    }

    /**
     * Test bold tag conversion
     *
     * @return void
     */
    public function test_parse_withBoldTag_returnsBoldHtml(): void
    {
        $sInput = '[b:bold text]';
        $sOutput = $this->parser->parse($sInput);
        $this->assertStringContainsString('<b>bold text</b>', $sOutput);
    }

    /**
     * Test italic tag conversion
     *
     * @return void
     */
    public function test_parse_withItalicTag_returnsItalicHtml(): void
    {
        $sInput = '[i:italic text]';
        $sOutput = $this->parser->parse($sInput);
        $this->assertStringContainsString('<i>italic text</i>', $sOutput);
    }

    /**
     * Test underline tag conversion
     *
     * @return void
     */
    public function test_parse_withUnderlineTag_returnsUnderlineHtml(): void
    {
        $sInput = '[u:underline text]';
        $sOutput = $this->parser->parse($sInput);
        $this->assertStringContainsString('<u>underline text</u>', $sOutput);
    }

    /**
     * Test strike tag conversion
     *
     * @return void
     */
    public function test_parse_withStrikeTag_returnsStrikeHtml(): void
    {
        $sInput = '[s:strike text]';
        $sOutput = $this->parser->parse($sInput);
        $this->assertStringContainsString('<s>strike text</s>', $sOutput);
    }

    /**
     * Test nested tags with stack handling
     *
     * @return void
     */
    public function test_parse_withNestedTags_handlesStack(): void
    {
        $sInput = '[b:bold [i:italic]]';
        $sOutput = $this->parser->parse($sInput);
        $this->assertStringContainsString('<b>bold <i>italic</i></b>', $sOutput);
    }

    /**
     * Test HTTP URL parsing
     *
     * @return void
     */
    public function test_parse_withHttpUrl_returnsClickableLink(): void
    {
        $sInput = '[https://example.com]';
        $sOutput = $this->parser->parse($sInput);
        $this->assertStringContainsString('<a href="https://example.com" target="_blank">https://example.com</a>', $sOutput);
    }

    /**
     * Test HTML special characters are escaped (XSS protection)
     *
     * @return void
     */
    public function test_parse_withHtmlSpecialChars_escapesOutput(): void
    {
        $sInput = '<script>alert("xss")</script>';
        $sOutput = $this->parser->parse($sInput);
        $this->assertStringNotContainsString('<script>', $sOutput);
        $this->assertStringContainsString('&lt;script&gt;', $sOutput);
    }

    /**
     * Test spoiler/hidden tag
     *
     * @return void
     */
    public function test_parse_withSpoilerTag_returnsHiddenContent(): void
    {
        $sInput = '[h:spoiler content]';
        $sOutput = $this->parser->parse($sInput);
        $this->assertStringContainsString('spoiler-button', $sOutput);
        $this->assertStringContainsString('spoiler-emoji', $sOutput);
        $this->assertStringContainsString('spoiler content', $sOutput);
    }

    /**
     * Test member-only content when not logged in
     *
     * @return void
     */
    public function test_parse_withMemberContent_andNotLoggedIn_hidesContent(): void
    {
        $this->parser->setIsLoggedIn(false);
        $sInput = '[m:secret member content]';
        $sOutput = $this->parser->parse($sInput);
        $this->assertStringContainsString('member-locked', $sOutput);
        $this->assertStringContainsString('nur für eingeloggte Mitglieder', $sOutput);
        $this->assertStringNotContainsString('secret member content', $sOutput);
    }

    /**
     * Test member-only content when logged in
     *
     * @return void
     */
    public function test_parse_withMemberContent_andLoggedIn_showsContent(): void
    {
        $this->parser->setIsLoggedIn(true);
        $sInput = '[m:secret member content]';
        $sOutput = $this->parser->parse($sInput);
        $this->assertStringContainsString('member-content', $sOutput);
        $this->assertStringContainsString('secret member content', $sOutput);
        $this->assertStringNotContainsString('member-locked', $sOutput);
    }

    /**
     * Test quote tag
     *
     * @return void
     */
    public function test_parse_withQuoteTag_returnsBlockquote(): void
    {
        $this->parser->setQuoteTag('blockquote');
        $sInput = '[q:quoted text]';
        $sOutput = $this->parser->parse($sInput);
        $this->assertStringContainsString('<blockquote>quoted text</blockquote>', $sOutput);
    }

    /**
     * Test plain text without markup
     *
     * @return void
     */
    public function test_parse_withPlainText_returnsEscapedText(): void
    {
        $sInput = 'Plain text without any markup';
        $sOutput = $this->parser->parse($sInput);
        $this->assertStringContainsString('Plain text without any markup', $sOutput);
    }

    /**
     * Test empty string
     *
     * @return void
     */
    public function test_parse_withEmptyString_returnsEmptyString(): void
    {
        $sInput = '';
        $sOutput = $this->parser->parse($sInput);
        $this->assertSame('', $sOutput);
    }

    /**
     * Test user mention with valid user
     * Note: This test bypasses database by pre-setting the mention cache
     *
     * @return void
     */
    public function test_parse_withUserMention_returnsClickableLink(): void
    {
        // Use reflection to set the mention cache AFTER constructor but BEFORE parse
        // This simulates the _preloadMentions() behavior without requiring database
        $reflection = new \ReflectionClass($this->parser);
        $property = $reflection->getProperty('m_arrMentionCache');
        $property->setAccessible(true);

        // Create a subclass that overrides _preloadMentions to prevent DB access
        $parser = new class extends \cPxmParser {
            protected function _preloadMentions($sText): void {
                // Override to prevent database access
                // Manually set cache for testing
                $this->m_arrMentionCache = [123 => 'TestUser'];
            }
        };

        $sInput = '[user:123]';
        $sOutput = $parser->parse($sInput);

        // Assert that output contains a link with correct attributes
        $this->assertStringContainsString('<a href="pxmboard.php?mode=userprofile&amp;usrid=123"', $sOutput);
        $this->assertStringContainsString('class="mention"', $sOutput);
        $this->assertStringContainsString('data-user-id="123"', $sOutput);
        $this->assertStringContainsString('onclick="openProfile(this);return false;"', $sOutput);
        $this->assertStringContainsString('@TestUser', $sOutput);
    }

    /**
     * Test user mention with deleted user
     *
     * @return void
     */
    public function test_parse_withDeletedUserMention_returnsDeletedMessage(): void
    {
        // Create a subclass that overrides _preloadMentions to prevent DB access
        $parser = new class extends \cPxmParser {
            protected function _preloadMentions($sText): void {
                // Override to prevent database access
                // Empty cache simulates deleted user
                $this->m_arrMentionCache = [];
            }
        };

        $sInput = '[user:999]';
        $sOutput = $parser->parse($sInput);

        // Assert that output contains deleted user message
        $this->assertStringContainsString('mention-deleted', $sOutput);
        $this->assertStringContainsString('Gel&ouml;schter Nutzer', $sOutput);
    }
}
