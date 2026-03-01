<?php
/**
 * Integration test for cUser class
 * Tests user data loading and password validation against the real test database
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
declare(strict_types=1);

namespace PXMBoard\Tests\Integration\Model;

use PXMBoard\Tests\TestCase\IntegrationTestCase;

class cUserTest extends IntegrationTestCase
{
    /**
     * Test loading user by ID
     *
     * @return void
     */
    public function test_loadDataById_withValidId_loadsUser(): void
    {
        $iUserId = $this->insertUser([
            'u_username'  => 'testuser_load',
            'u_firstname' => 'Test',
            'u_lastname'  => 'User',
        ]);

        $objUser = new \cUser();
        $bResult = $objUser->loadDataById($iUserId);

        $this->assertTrue($bResult);
        $this->assertSame($iUserId, $objUser->getId());
        $this->assertSame('testuser_load', $objUser->getUserName());
    }

    /**
     * Test loading user with non-existent ID returns false
     *
     * @return void
     */
    public function test_loadDataById_withInvalidId_returnsFalse(): void
    {
        $objUser = new \cUser();
        $bResult = $objUser->loadDataById(999999);

        $this->assertFalse($bResult);
    }

    /**
     * Test password validation with correct bcrypt hash
     *
     * @return void
     */
    public function test_validatePassword_withCorrectPassword_returnsTrue(): void
    {
        $sPassword = 'testpassword123';
        $iUserId = $this->insertUser([
            'u_username' => 'testuser_pwd',
            'u_password' => password_hash($sPassword, PASSWORD_DEFAULT),
        ]);

        $objUser = new \cUser();
        $objUser->loadDataById($iUserId);

        $this->assertTrue($objUser->validatePassword($sPassword));
    }

    /**
     * Test password validation with wrong password returns false
     *
     * @return void
     */
    public function test_validatePassword_withWrongPassword_returnsFalse(): void
    {
        $iUserId = $this->insertUser([
            'u_username' => 'testuser_wrongpwd',
            'u_password' => password_hash('correctpassword', PASSWORD_DEFAULT),
        ]);

        $objUser = new \cUser();
        $objUser->loadDataById($iUserId);

        $this->assertFalse($objUser->validatePassword('wrongpassword'));
    }
}
