<?php

/**
 * Unit test for cServerHandler class
 *
 * @author Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright Torsten Rentsch 2001 - 2026
 */
declare(strict_types=1);

namespace PXMBoard\Tests\Unit\Validation;

use PHPUnit\Framework\TestCase;

class cServerHandlerTest extends TestCase
{
    private \cServerHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->handler = new \cServerHandler();
    }

    protected function tearDown(): void
    {
        unset($_SERVER['HTTP_HX_REQUEST']);
        parent::tearDown();
    }

    /**
     * Test isHtmxRequest returns true when HX-Request header is 'true'
     */
    public function test_isHtmxRequest_withHtmxHeader_returnsTrue(): void
    {
        $_SERVER['HTTP_HX_REQUEST'] = 'true';
        $this->assertTrue($this->handler->isHtmxRequest());
    }

    /**
     * Test isHtmxRequest returns false when HX-Request header is absent
     */
    public function test_isHtmxRequest_withoutHeader_returnsFalse(): void
    {
        unset($_SERVER['HTTP_HX_REQUEST']);
        $this->assertFalse($this->handler->isHtmxRequest());
    }

    /**
     * Test isHtmxRequest returns false when HX-Request header has a non-'true' value
     */
    public function test_isHtmxRequest_withWrongHeaderValue_returnsFalse(): void
    {
        $_SERVER['HTTP_HX_REQUEST'] = '1';
        $this->assertFalse($this->handler->isHtmxRequest());
    }

    /**
     * Test isHtmxRequest returns false when HX-Request header is empty string
     */
    public function test_isHtmxRequest_withEmptyHeaderValue_returnsFalse(): void
    {
        $_SERVER['HTTP_HX_REQUEST'] = '';
        $this->assertFalse($this->handler->isHtmxRequest());
    }
}
