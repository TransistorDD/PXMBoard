<?php
/**
 * Integration test for basic action infrastructure
 * Tests superglobal helpers and cInputHandler integration
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
declare(strict_types=1);

namespace PXMBoard\Tests\Integration\Action;

use PXMBoard\Tests\TestCase\PxmTestCase;

class ActionBaseTest extends PxmTestCase
{
    /**
     * Test that POST data can be set via helper
     *
     * @return void
     */
    public function test_setPostData_setsSuperglobal(): void
    {
        $this->setPostData(['test' => 'value']);
        $this->assertSame('value', $_POST['test']);
    }

    /**
     * Test that GET data can be set via helper
     *
     * @return void
     */
    public function test_setGetData_setsSuperglobal(): void
    {
        $this->setGetData(['test' => 'value']);
        $this->assertSame('value', $_GET['test']);
    }

    /**
     * Test that cInputHandler reads POST data from the superglobal
     *
     * @return void
     */
    public function test_inputHandler_readsPostData(): void
    {
        $this->setPostData(['username' => 'testuser']);

        $objInputHandler = new \cInputHandler();
        $sUsername = $objInputHandler->getStringFormVar('username', 'username', TRUE, FALSE);

        $this->assertSame('testuser', $sUsername);
    }

    /**
     * Test that cInputHandler reads GET data from the superglobal
     *
     * @return void
     */
    public function test_inputHandler_readsGetData(): void
    {
        $this->setGetData(['page' => '5']);

        $objInputHandler = new \cInputHandler();
        $iPage = $objInputHandler->getIntFormVar('page', FALSE, TRUE);

        $this->assertSame(5, $iPage);
    }
}
