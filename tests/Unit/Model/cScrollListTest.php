<?php

declare(strict_types=1);

namespace PXMBoard\Tests\Unit\Model;

use PHPUnit\Framework\TestCase;
use PXMBoard\Model\cScrollList;

/**
 * Unit test for cScrollList class
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cScrollListTest extends TestCase
{
    private cScrollList $scrollList;

    protected function setUp(): void
    {
        parent::setUp();
        $this->scrollList = new cScrollList();
    }

    /**
     * Test getPageCount with zero items per page returns zero
     *
     * @return void
     */
    public function test_getPageCount_withZeroItemsPerPage_returnsZero(): void
    {
        $reflection = new \ReflectionClass($this->scrollList);

        $itemsProperty = $reflection->getProperty('m_iItemCount');
        $itemsProperty->setValue($this->scrollList, 100);

        $perPageProperty = $reflection->getProperty('m_iItemsPerPage');
        $perPageProperty->setValue($this->scrollList, 0);

        $this->assertSame(0, $this->scrollList->getPageCount());
    }

    /**
     * Test getPageCount with exact page match
     *
     * @return void
     */
    public function test_getPageCount_withExactPageMatch_returnsCorrectCount(): void
    {
        $reflection = new \ReflectionClass($this->scrollList);

        $itemsProperty = $reflection->getProperty('m_iItemCount');
        $itemsProperty->setValue($this->scrollList, 100);

        $perPageProperty = $reflection->getProperty('m_iItemsPerPage');
        $perPageProperty->setValue($this->scrollList, 10);

        $this->assertSame(10, $this->scrollList->getPageCount());
    }

    /**
     * Test getPageCount rounds up partial pages
     *
     * @return void
     */
    public function test_getPageCount_withPartialPage_roundsUp(): void
    {
        $reflection = new \ReflectionClass($this->scrollList);

        $itemsProperty = $reflection->getProperty('m_iItemCount');
        $itemsProperty->setValue($this->scrollList, 105);

        $perPageProperty = $reflection->getProperty('m_iItemsPerPage');
        $perPageProperty->setValue($this->scrollList, 10);

        // 105 items / 10 per page = 10.5 -> rounds up to 11
        $this->assertSame(11, $this->scrollList->getPageCount());
    }

    /**
     * Test getPageCount with single item
     *
     * @return void
     */
    public function test_getPageCount_withSingleItem_returnsOne(): void
    {
        $reflection = new \ReflectionClass($this->scrollList);

        $itemsProperty = $reflection->getProperty('m_iItemCount');
        $itemsProperty->setValue($this->scrollList, 1);

        $perPageProperty = $reflection->getProperty('m_iItemsPerPage');
        $perPageProperty->setValue($this->scrollList, 10);

        $this->assertSame(1, $this->scrollList->getPageCount());
    }

    /**
     * Test getItemCount returns correct value
     *
     * @return void
     */
    public function test_getItemCount_returnsCorrectValue(): void
    {
        $reflection = new \ReflectionClass($this->scrollList);

        $itemsProperty = $reflection->getProperty('m_iItemCount');
        $itemsProperty->setValue($this->scrollList, 42);

        $this->assertSame(42, $this->scrollList->getItemCount());
    }

    /**
     * Test getPrevPageId returns correct value
     *
     * @return void
     */
    public function test_getPrevPageId_returnsCorrectValue(): void
    {
        $reflection = new \ReflectionClass($this->scrollList);

        $property = $reflection->getProperty('m_iPrevPageId');
        $property->setValue($this->scrollList, 3);

        $this->assertSame(3, $this->scrollList->getPrevPageId());
    }

    /**
     * Test getNextPageId returns correct value
     *
     * @return void
     */
    public function test_getNextPageId_returnsCorrectValue(): void
    {
        $reflection = new \ReflectionClass($this->scrollList);

        $property = $reflection->getProperty('m_iNextPageId');
        $property->setValue($this->scrollList, 5);

        $this->assertSame(5, $this->scrollList->getNextPageId());
    }

    /**
     * Test getCurPageId returns correct value
     *
     * @return void
     */
    public function test_getCurPageId_returnsCorrectValue(): void
    {
        $reflection = new \ReflectionClass($this->scrollList);

        $property = $reflection->getProperty('m_iCurPageId');
        $property->setValue($this->scrollList, 4);

        $this->assertSame(4, $this->scrollList->getCurPageId());
    }

    /**
     * Test getDataArray returns result list
     *
     * @return void
     */
    public function test_getDataArray_returnsResultList(): void
    {
        $reflection = new \ReflectionClass($this->scrollList);

        $arrTestData = ['item1', 'item2', 'item3'];

        $property = $reflection->getProperty('m_arrResultList');
        $property->setValue($this->scrollList, $arrTestData);

        $this->assertSame($arrTestData, $this->scrollList->getDataArray());
    }

    /**
     * Test initial state has zero values
     *
     * @return void
     */
    public function test_initialState_hasZeroValues(): void
    {
        $this->assertSame(0, $this->scrollList->getItemCount());
        $this->assertSame(0, $this->scrollList->getPrevPageId());
        $this->assertSame(0, $this->scrollList->getCurPageId());
        $this->assertSame(0, $this->scrollList->getNextPageId());
        $this->assertSame([], $this->scrollList->getDataArray());
    }
}
