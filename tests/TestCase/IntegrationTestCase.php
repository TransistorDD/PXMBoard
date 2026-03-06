<?php

declare(strict_types=1);

namespace PXMBoard\Tests\TestCase;

use PXMBoard\Database\cDB;

/**
 * Integration test base class with real database and transaction rollback.
 * 
 * Provides transaction-based test isolation and fixture helper methods.
 * Each test runs inside a transaction that is rolled back in tearDown(),
 * leaving the test database in a clean state without TRUNCATE or DELETE.
 *
 * Hierarchy:
 *   PxmTestCase (superglobal helpers, no DB)
 *     └── IntegrationTestCase (real DB, transaction rollback, fixture helpers)
 *           └── ActionTestCase (cConfig configured for real test skins)
 * 
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
abstract class IntegrationTestCase extends PxmTestCase
{
    /**
     * Start a database transaction before each test.
     * The transaction spans the full test execution and is rolled back in tearDown.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        cDB::getInstance()->executeQuery('START TRANSACTION');
    }

    /**
     * Roll back the transaction after each test, undoing all fixture inserts.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        cDB::getInstance()->executeQuery('ROLLBACK');
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // Fixture helpers
    // -------------------------------------------------------------------------

    /**
     * Insert a board row and return the generated ID.
     *
     * @param array<string,mixed> $arrData Column overrides (merged with defaults)
     * @return int Inserted board ID
     */
    protected function insertBoard(array $arrData = []): int
    {
        $objDb = cDB::getInstance();
        $arrDefaults = [
            'b_name'           => 'Test Board',
            'b_description'    => 'Test board description',
            'b_position'       => 1,
            'b_status'         => 1,
            'b_lastmsgtstmp'   => 0,
            'b_skinid'         => 1,
            'b_timespan'       => 100,
            'b_threadlistsort' => '',
            'b_embed_external' => 1,
            'b_replacetext'    => 0,
        ];
        $arrRow = array_merge($arrDefaults, $arrData);

        $objDb->executeQuery(
            "INSERT INTO pxm_board (b_name, b_description, b_position, b_status, b_lastmsgtstmp,
                b_skinid, b_timespan, b_threadlistsort, b_embed_external, b_replacetext) VALUES ("
            . $objDb->quote($arrRow['b_name']) . ','
            . $objDb->quote($arrRow['b_description']) . ','
            . (int)$arrRow['b_position'] . ','
            . (int)$arrRow['b_status'] . ','
            . (int)$arrRow['b_lastmsgtstmp'] . ','
            . (int)$arrRow['b_skinid'] . ','
            . (int)$arrRow['b_timespan'] . ','
            . $objDb->quote($arrRow['b_threadlistsort']) . ','
            . (int)$arrRow['b_embed_external'] . ','
            . (int)$arrRow['b_replacetext'] . ')'
        );

        return (int)$objDb->getInsertId('pxm_board', 'b_id');
    }

    /**
     * Insert a user row and return the generated ID.
     *
     * @param array<string,mixed> $arrData Column overrides (merged with defaults)
     * @return int Inserted user ID
     */
    protected function insertUser(array $arrData = []): int
    {
        $objDb = cDB::getInstance();
        $arrDefaults = [
            'u_username'             => 'testuser_' . uniqid(),
            'u_password'             => password_hash('testpassword', PASSWORD_DEFAULT),
            'u_passwordkey'          => bin2hex(random_bytes(16)),
            'u_firstname'            => 'Test',
            'u_lastname'             => 'User',
            'u_city'                 => 'Berlin',
            'u_publicmail'           => '',
            'u_privatemail'          => '',
            'u_registrationmail'     => 'test@example.com',
            'u_registrationtstmp'    => time(),
            'u_lastonlinetstmp'      => time(),
            'u_msgquantity'          => 0,
            'u_status'               => 1,
            'u_post'                 => 1,
            'u_edit'                 => 1,
            'u_admin'                => 0,
            'u_visible'              => 1,
            'u_skinid'               => 1,
            'u_highlight'            => 0,
        ];
        $arrRow = array_merge($arrDefaults, $arrData);

        $objDb->executeQuery(
            "INSERT INTO pxm_user (u_username, u_password, u_passwordkey, u_firstname, u_lastname,
                u_city, u_publicmail, u_privatemail, u_registrationmail, u_registrationtstmp,
                u_lastonlinetstmp, u_msgquantity, u_status, u_post, u_edit, u_admin, u_visible,
                u_skinid, u_highlight) VALUES ("
            . $objDb->quote($arrRow['u_username']) . ','
            . $objDb->quote($arrRow['u_password']) . ','
            . $objDb->quote($arrRow['u_passwordkey']) . ','
            . $objDb->quote($arrRow['u_firstname']) . ','
            . $objDb->quote($arrRow['u_lastname']) . ','
            . $objDb->quote($arrRow['u_city']) . ','
            . $objDb->quote($arrRow['u_publicmail']) . ','
            . $objDb->quote($arrRow['u_privatemail']) . ','
            . $objDb->quote($arrRow['u_registrationmail']) . ','
            . (int)$arrRow['u_registrationtstmp'] . ','
            . (int)$arrRow['u_lastonlinetstmp'] . ','
            . (int)$arrRow['u_msgquantity'] . ','
            . (int)$arrRow['u_status'] . ','
            . (int)$arrRow['u_post'] . ','
            . (int)$arrRow['u_edit'] . ','
            . (int)$arrRow['u_admin'] . ','
            . (int)$arrRow['u_visible'] . ','
            . (int)$arrRow['u_skinid'] . ','
            . (int)$arrRow['u_highlight'] . ')'
        );

        return (int)$objDb->getInsertId('pxm_user', 'u_id');
    }

    /**
     * Insert a thread row and return the generated ID.
     *
     * @param int $iBoardId Board the thread belongs to
     * @param array<string,mixed> $arrData Column overrides (merged with defaults)
     * @return int Inserted thread ID
     */
    protected function insertThread(int $iBoardId, array $arrData = []): int
    {
        $objDb = cDB::getInstance();
        $arrDefaults = [
            't_boardid'      => $iBoardId,
            't_active'       => 1,
            't_fixed'        => 0,
            't_lastmsgtstmp' => time(),
            't_lastmsgid'    => 0,
            't_msgquantity'  => 0,
            't_views'        => 0,
        ];
        $arrRow = array_merge($arrDefaults, $arrData);

        $objDb->executeQuery(
            "INSERT INTO pxm_thread (t_boardid, t_active, t_fixed, t_lastmsgtstmp,
                t_lastmsgid, t_msgquantity, t_views) VALUES ("
            . (int)$arrRow['t_boardid'] . ','
            . (int)$arrRow['t_active'] . ','
            . (int)$arrRow['t_fixed'] . ','
            . (int)$arrRow['t_lastmsgtstmp'] . ','
            . (int)$arrRow['t_lastmsgid'] . ','
            . (int)$arrRow['t_msgquantity'] . ','
            . (int)$arrRow['t_views'] . ')'
        );

        return (int)$objDb->getInsertId('pxm_thread', 't_id');
    }

    /**
     * Insert a message row and return the generated ID.
     *
     * @param int $iThreadId Thread the message belongs to
     * @param array<string,mixed> $arrData Column overrides (merged with defaults)
     * @return int Inserted message ID
     */
    protected function insertMessage(int $iThreadId, array $arrData = []): int
    {
        $objDb = cDB::getInstance();
        $arrDefaults = [
            'm_threadid'        => $iThreadId,
            'm_parentid'        => 0,
            'm_userid'          => 0,
            'm_username'        => 'testuser',
            'm_usermail'        => '',
            'm_userhighlight'   => 0,
            'm_subject'         => 'Test Subject',
            'm_body'            => 'Test message body.',
            'm_tstmp'           => time(),
            'm_ip'              => '127.0.0.1',
            'm_notify_on_reply' => 0,
            'm_status'          => 1,
        ];
        $arrRow = array_merge($arrDefaults, $arrData);

        $objDb->executeQuery(
            "INSERT INTO pxm_message (m_threadid, m_parentid, m_userid, m_username, m_usermail,
                m_userhighlight, m_subject, m_body, m_tstmp, m_ip, m_notify_on_reply, m_status) VALUES ("
            . (int)$arrRow['m_threadid'] . ','
            . (int)$arrRow['m_parentid'] . ','
            . (int)$arrRow['m_userid'] . ','
            . $objDb->quote($arrRow['m_username']) . ','
            . $objDb->quote($arrRow['m_usermail']) . ','
            . (int)$arrRow['m_userhighlight'] . ','
            . $objDb->quote($arrRow['m_subject']) . ','
            . $objDb->quote($arrRow['m_body']) . ','
            . (int)$arrRow['m_tstmp'] . ','
            . $objDb->quote($arrRow['m_ip']) . ','
            . (int)$arrRow['m_notify_on_reply'] . ','
            . (int)$arrRow['m_status'] . ')'
        );

        return (int)$objDb->getInsertId('pxm_message', 'm_id');
    }
}
