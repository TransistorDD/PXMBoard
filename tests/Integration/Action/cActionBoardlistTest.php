<?php

declare(strict_types=1);

namespace PXMBoard\Tests\Integration\Action;

use PXMBoard\Controller\Board\cActionBoardlist;
use PXMBoard\Tests\TestCase\ActionTestCase;

/**
 * Integration test for cActionBoardlist
 *
 * Tests the full action lifecycle against the real test database and real Smarty templates.
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cActionBoardlistTest extends ActionTestCase
{
    /**
     * Test that validateBasePermissionsAndConditions returns true without a logged-in user.
     * The board list is publicly accessible.
     *
     * @return void
     */
    public function test_validateBasePermissionsAndConditions_returnsTrue(): void
    {
        $objAction = new cActionBoardlist($this->objConfig);

        $this->assertTrue($objAction->validateBasePermissionsAndConditions());
    }

    /**
     * Test that getOutput returns a non-empty string after performAction.
     *
     * @return void
     */
    public function test_getOutput_afterPerformAction_returnsNonEmptyString(): void
    {
        $this->insertBoard(['b_name' => 'Integration Test Board']);

        $objAction = new cActionBoardlist($this->objConfig);
        $objAction->performAction();

        $this->assertNotEmpty($objAction->getOutput());
    }

    /**
     * Test that output contains the board name inserted as fixture.
     *
     * @return void
     */
    public function test_getOutput_containsBoardNameFromFixture(): void
    {
        $this->insertBoard(['b_name' => 'Unique Board ABCXYZ123']);

        $objAction = new cActionBoardlist($this->objConfig);
        $objAction->performAction();
        $sOutput = $objAction->getOutput();

        $this->assertStringContainsString('Unique Board ABCXYZ123', $sOutput);
    }

    /**
     * Test that the newest-messages section heading is present in output.
     *
     * @return void
     */
    public function test_getOutput_containsNewestMessagesSection(): void
    {
        $objAction = new cActionBoardlist($this->objConfig);
        $objAction->performAction();
        $sOutput = $objAction->getOutput();

        $this->assertStringContainsString('Neueste Beitr', $sOutput);
    }

    /**
     * Test that the newest-member section is rendered when a user exists in the DB.
     *
     * @return void
     */
    public function test_getOutput_containsNewestMember_whenUserExists(): void
    {
        $this->insertUser(['u_username' => 'newest_member_test']);

        $objAction = new cActionBoardlist($this->objConfig);
        $objAction->performAction();
        $sOutput = $objAction->getOutput();

        $this->assertStringContainsString('Neuestes Mitglied', $sOutput);
    }

    /**
     * Test that the login form is rendered for unauthenticated visitors (iUserId = 0).
     *
     * @return void
     */
    public function test_getOutput_withoutActiveUser_rendersLoginForm(): void
    {
        $objAction = new cActionBoardlist($this->objConfig, 0);
        $objAction->performAction();
        $sOutput = $objAction->getOutput();

        // Login form is only shown when config.logedin == 0 (unauthenticated visitor)
        $this->assertStringContainsString('name="username"', $sOutput);
    }
}
