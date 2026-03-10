<?php

declare(strict_types=1);

namespace PXMBoard\Tests\TestCase;

use PXMBoard\I18n\cTranslator;
use PXMBoard\Model\cConfig;
use PXMBoard\Model\cUserConfig;

/**
 * Base class for Action integration tests.
 *
 * Provides a real cConfig loaded from the test database.
 * The default skin ID is overridden to 1 (the skin present in the test schema)
 * so that initSkin() succeeds without inserting additional fixtures.
 *
 * Tests that need user or board fixtures can use the fixture helpers from
 * IntegrationTestCase (insertBoard, insertUser, insertThread, insertMessage).
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
abstract class ActionTestCase extends IntegrationTestCase
{
    /**
     * @var cConfig Real configuration loaded from the test database
     */
    protected cConfig $objConfig;

    /**
     * Create a real cConfig and ensure a usable skin exists in the test DB.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Load translations so that i18n keys resolve to German strings in templates.
        cTranslator::load('de');

        // Load real configuration from the test database.
        // cConfig::__construct() queries pxm_configuration via the real DB connection.
        $this->objConfig = new cConfig(['Smarty'], 'TestAgent/1.0', '127.0.0.1');

        // The schema inserts pxm_configuration with c_skinid=2, but pxm_skin only
        // contains skin id=1. Override the default skin id so that initSkin() can
        // successfully load skin 1 ("pxm" / Smarty).
        $this->objConfig->setDefaultSkinId(1);
    }

    /**
     * Create a stub user with specific permissions.
     *
     * @param bool $bIsAdmin  Is admin?
     * @param bool $bCanPost  Can post?
     * @param int  $iUserId   User ID
     * @return cUserConfig Stub user
     */
    protected function createMockUser(bool $bIsAdmin = false, bool $bCanPost = true, int $iUserId = 1): cUserConfig
    {
        $mockUser = $this->createStub(cUserConfig::class);
        $mockUser->method('getId')->willReturn($iUserId);
        $mockUser->method('isAdmin')->willReturn($bIsAdmin);
        $mockUser->method('canPost')->willReturn($bCanPost);
        $mockUser->method('getLastOnlineTimestamp')->willReturn(time() - 3600);

        return $mockUser;
    }
}
