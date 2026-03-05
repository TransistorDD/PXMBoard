<?php
/**
 * Unit tests for MessageStatus Enum
 * Tests message status values and methods
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
declare(strict_types=1);

namespace PXMBoard\Tests\Unit\Enum;

use PHPUnit\Framework\TestCase;

class MessageStatusTest extends TestCase
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
     * Test DRAFT value is 0
     *
     * @return void
     */
    public function test_draftValue_isZero(): void
    {
        $this->assertSame(0, \eMessageStatus::DRAFT->value);
    }

    /**
     * Test PUBLISHED value is 1
     *
     * @return void
     */
    public function test_publishedValue_isOne(): void
    {
        $this->assertSame(1, \eMessageStatus::PUBLISHED->value);
    }

    /**
     * Test DELETED value is 2
     *
     * @return void
     */
    public function test_deletedValue_isTwo(): void
    {
        $this->assertSame(2, \eMessageStatus::DELETED->value);
    }

    /**
     * Test isDraft returns true for DRAFT
     *
     * @return void
     */
    public function test_isDraft_forDraft_returnsTrue(): void
    {
        $this->assertTrue(\eMessageStatus::DRAFT->isDraft());
    }

    /**
     * Test isDraft returns false for PUBLISHED
     *
     * @return void
     */
    public function test_isDraft_forPublished_returnsFalse(): void
    {
        $this->assertFalse(\eMessageStatus::PUBLISHED->isDraft());
    }

    /**
     * Test isPubliclyVisible returns true for PUBLISHED
     *
     * @return void
     */
    public function test_isPubliclyVisible_forPublished_returnsTrue(): void
    {
        $this->assertTrue(\eMessageStatus::PUBLISHED->isPubliclyVisible());
    }

    /**
     * Test isPubliclyVisible returns false for DRAFT
     *
     * @return void
     */
    public function test_isPubliclyVisible_forDraft_returnsFalse(): void
    {
        $this->assertFalse(\eMessageStatus::DRAFT->isPubliclyVisible());
    }

    /**
     * Test isDeleted returns true for DELETED
     *
     * @return void
     */
    public function test_isDeleted_forDeleted_returnsTrue(): void
    {
        $this->assertTrue(\eMessageStatus::DELETED->isDeleted());
    }

    /**
     * Test isDeleted returns false for PUBLISHED
     *
     * @return void
     */
    public function test_isDeleted_forPublished_returnsFalse(): void
    {
        $this->assertFalse(\eMessageStatus::PUBLISHED->isDeleted());
    }

    /**
     * Test from method with valid integer
     *
     * @return void
     */
    public function test_from_withValidInt_returnsEnum(): void
    {
        $status = \eMessageStatus::from(1);
        $this->assertSame(\eMessageStatus::PUBLISHED, $status);
    }

    /**
     * Test label method
     *
     * @return void
     */
    public function test_label_returnGermanLabel(): void
    {
        $this->assertSame('Entwurf', \eMessageStatus::DRAFT->getLabel());
        $this->assertSame('Veröffentlicht', \eMessageStatus::PUBLISHED->getLabel());
        $this->assertSame('Gelöscht', \eMessageStatus::DELETED->getLabel());
    }
}
