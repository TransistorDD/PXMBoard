<?php

declare(strict_types=1);

namespace PXMBoard\Tests\Unit\Enum;

use PHPUnit\Framework\TestCase;
use PXMBoard\Enum\eBoardStatus;
use PXMBoard\I18n\cTranslator;

/**
 * Unit tests for BoardStatus Enum
 * Tests board status values and permission methods
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class BoardStatusTest extends TestCase
{
    /**
     * Load translations before each test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        cTranslator::load('de');
    }


    /**
     * Test isPublicReadable returns true for PUBLIC
     *
     * @return void
     */
    public function test_isPublicReadable_forPublic_returnsTrue(): void
    {
        $this->assertTrue(eBoardStatus::PUBLIC->isPublicReadable());
    }

    /**
     * Test isPublicReadable returns false for MEMBERS_ONLY
     *
     * @return void
     */
    public function test_isPublicReadable_forMembersOnly_returnsFalse(): void
    {
        $this->assertFalse(eBoardStatus::MEMBERS_ONLY->isPublicReadable());
    }

    /**
     * Test isPublicReadable returns true for READONLY_PUBLIC
     *
     * @return void
     */
    public function test_isPublicReadable_forReadonlyPublic_returnsTrue(): void
    {
        $this->assertTrue(eBoardStatus::READONLY_PUBLIC->isPublicReadable());
    }

    /**
     * Test isWritable returns true for PUBLIC
     *
     * @return void
     */
    public function test_isWritable_forPublic_returnsTrue(): void
    {
        $this->assertTrue(eBoardStatus::PUBLIC->isWritable());
    }

    /**
     * Test isWritable returns false for READONLY_PUBLIC
     *
     * @return void
     */
    public function test_isWritable_forReadonlyPublic_returnsFalse(): void
    {
        $this->assertFalse(eBoardStatus::READONLY_PUBLIC->isWritable());
    }

    /**
     * Test isWritable returns true for MEMBERS_ONLY
     *
     * @return void
     */
    public function test_isWritable_forMembersOnly_returnsTrue(): void
    {
        $this->assertTrue(eBoardStatus::MEMBERS_ONLY->isWritable());
    }

    /**
     * Test isClosed returns true for CLOSED
     *
     * @return void
     */
    public function test_isClosed_forClosed_returnsTrue(): void
    {
        $this->assertTrue(eBoardStatus::CLOSED->isClosed());
    }

    /**
     * Test isClosed returns false for PUBLIC
     *
     * @return void
     */
    public function test_isClosed_forPublic_returnsFalse(): void
    {
        $this->assertFalse(eBoardStatus::PUBLIC->isClosed());
    }

    /**
     * Test requiresAuthentication returns true for MEMBERS_ONLY
     *
     * @return void
     */
    public function test_requiresAuthentication_forMembersOnly_returnsTrue(): void
    {
        $this->assertTrue(eBoardStatus::MEMBERS_ONLY->requiresAuthentication());
    }

    /**
     * Test requiresAuthentication returns false for PUBLIC
     *
     * @return void
     */
    public function test_requiresAuthentication_forPublic_returnsFalse(): void
    {
        $this->assertFalse(eBoardStatus::PUBLIC->requiresAuthentication());
    }

    /**
     * Test requiresAuthentication returns true for CLOSED
     *
     * @return void
     */
    public function test_requiresAuthentication_forClosed_returnsTrue(): void
    {
        $this->assertTrue(eBoardStatus::CLOSED->requiresAuthentication());
    }

    /**
     * Test getLabel returns German labels
     *
     * @return void
     */
    public function test_getLabel_returnsGermanLabels(): void
    {
        $this->assertSame('Öffentlich', eBoardStatus::PUBLIC->getLabel());
        $this->assertSame('Nur Mitglieder', eBoardStatus::MEMBERS_ONLY->getLabel());
        $this->assertSame('Nur Lesen (Öffentlich)', eBoardStatus::READONLY_PUBLIC->getLabel());
        $this->assertSame('Nur Lesen (Mitglieder)', eBoardStatus::READONLY_MEMBERS->getLabel());
        $this->assertSame('Geschlossen', eBoardStatus::CLOSED->getLabel());
    }
}
