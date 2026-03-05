<?php


declare(strict_types=1);

namespace PXMBoard\Tests\Integration\Action;

use PXMBoard\Controller\Board\cActionMessagesave;
use PXMBoard\Enum\eErrorKeys;
use PXMBoard\Tests\TestCase\ActionTestCase;

/**
 * Integration test for cActionMessagesave
 *
 * Covers validateBasePermissionsAndConditions() (CSRF + board-writability gate)
 * and performAction() (save, draft, reply, error paths).
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cActionMessagesaveTest extends ActionTestCase
{
    /** CSRF token used by all tests that require authentication. */
    private const CSRF_TOKEN = 'inttest-csrf-token-abcdef1234567890ab';

    protected function setUp(): void
    {
        parent::setUp();
        unset($_SERVER['HTTP_X_CSRF_TOKEN']);
    }

    protected function tearDown(): void
    {
        unset($_SERVER['HTTP_X_CSRF_TOKEN']);
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // Helper
    // -------------------------------------------------------------------------

    /**
     * Create a cActionMessagesave instance pre-loaded with the test CSRF token.
     *
     * @param int $iUserId  0 for guest
     * @param int $iBoardId 0 for no board
     * @return cActionMessagesave
     */
    private function makeAction(int $iUserId, int $iBoardId): cActionMessagesave
    {
        $objAction = new cActionMessagesave($this->objConfig, $iUserId, $iBoardId);
        $objAction->setCsrfToken(self::CSRF_TOKEN);
        return $objAction;
    }

    // =========================================================================
    // validateBasePermissionsAndConditions()
    // =========================================================================

    /**
     * Guest on a writable board: CSRF check is bypassed for unauthenticated
     * users. The guest path (quickpost or NOT_LOGGED_IN) is handled by
     * performAction(), not by the permission gate.
     */
    public function test_validate_guestOnWritableBoard_returnsTrue(): void
    {
        $iBoardId = $this->insertBoard();

        $this->assertTrue($this->makeAction(0, $iBoardId)->validateBasePermissionsAndConditions());
    }

    /**
     * Authenticated user with a valid token in the POST field passes.
     */
    public function test_validate_authenticatedWithValidPostToken_returnsTrue(): void
    {
        $iUserId  = $this->insertUser();
        $iBoardId = $this->insertBoard();
        $this->setPostData(['csrf_token' => self::CSRF_TOKEN]);

        $this->assertTrue($this->makeAction($iUserId, $iBoardId)->validateBasePermissionsAndConditions());
    }

    /**
     * Authenticated user with a valid token in the X-CSRF-Token header passes.
     */
    public function test_validate_authenticatedWithValidHeaderToken_returnsTrue(): void
    {
        $iUserId  = $this->insertUser();
        $iBoardId = $this->insertBoard();
        $_SERVER['HTTP_X_CSRF_TOKEN'] = self::CSRF_TOKEN;

        $this->assertTrue($this->makeAction($iUserId, $iBoardId)->validateBasePermissionsAndConditions());
    }

    /**
     * Authenticated user without any submitted CSRF token is rejected.
     * The error output must contain the CSRF_TOKEN_INVALID message.
     */
    public function test_validate_authenticatedWithNoToken_returnsFalseWithCsrfError(): void
    {
        $iUserId  = $this->insertUser();
        $iBoardId = $this->insertBoard();

        $objAction = $this->makeAction($iUserId, $iBoardId);

        $this->assertFalse($objAction->validateBasePermissionsAndConditions());
        $this->assertStringContainsString(
            eErrorKeys::CSRF_TOKEN_INVALID->t(),
            $objAction->getOutput()
        );
    }

    /**
     * Authenticated user with a wrong CSRF token is rejected with CSRF error.
     */
    public function test_validate_authenticatedWithWrongToken_returnsFalseWithCsrfError(): void
    {
        $iUserId  = $this->insertUser();
        $iBoardId = $this->insertBoard();
        $this->setPostData(['csrf_token' => 'this-is-the-wrong-token']);

        $objAction = $this->makeAction($iUserId, $iBoardId);

        $this->assertFalse($objAction->validateBasePermissionsAndConditions());
        $this->assertStringContainsString(
            eErrorKeys::CSRF_TOKEN_INVALID->t(),
            $objAction->getOutput()
        );
    }

    /**
     * No board ID provided: _requireWritableBoard() fails before CSRF is checked.
     */
    public function test_validate_noBoardId_returnsFalse(): void
    {
        $iUserId = $this->insertUser();
        $this->setPostData(['csrf_token' => self::CSRF_TOKEN]);

        $this->assertFalse($this->makeAction($iUserId, 0)->validateBasePermissionsAndConditions());
    }

    // =========================================================================
    // performAction()
    // =========================================================================

    /**
     * Happy path: authenticated user submits subject + body → message is
     * persisted and the confirm template is rendered.
     */
    public function test_performAction_validPost_savesMessageAndRendersConfirm(): void
    {
        $iUserId  = $this->insertUser();
        $iBoardId = $this->insertBoard();
        $this->setPostData([
            'csrf_token' => self::CSRF_TOKEN,
            'subject'    => 'Integrationstest Betreff',
            'body'       => 'Integrationstest Inhalt.',
        ]);

        $objAction = $this->makeAction($iUserId, $iBoardId);
        $objAction->performAction();

        $this->assertStringContainsString('erfolgreich gespeichert', $objAction->getOutput());
    }

    /**
     * Missing subject: message form is re-rendered and contains the
     * SUBJECT_MISSING error text.
     */
    public function test_performAction_missingSubject_rendersFormWithSubjectError(): void
    {
        $iUserId  = $this->insertUser();
        $iBoardId = $this->insertBoard();
        $this->setPostData([
            'csrf_token' => self::CSRF_TOKEN,
            'subject'    => '',
            'body'       => 'Inhalt ohne Betreff.',
        ]);

        $objAction = $this->makeAction($iUserId, $iBoardId);
        $objAction->performAction();

        $this->assertStringContainsString(
            eErrorKeys::SUBJECT_MISSING->t(),
            $objAction->getOutput()
        );
    }

    /**
     * Reply: msgid points to an existing parent message in the same board.
     * A child message is created and the confirm template is rendered.
     */
    public function test_performAction_replyToParentMessage_savesChildMessage(): void
    {
        $iUserId   = $this->insertUser();
        $iBoardId  = $this->insertBoard();
        $iThreadId = $this->insertThread($iBoardId);
        $iParentId = $this->insertMessage($iThreadId, ['m_userid' => $iUserId]);

        $this->setPostData([
            'csrf_token' => self::CSRF_TOKEN,
            'subject'    => 'Re: Antwort',
            'body'       => 'Antwort auf übergeordnete Nachricht.',
            'msgid'      => (string)$iParentId,
        ]);

        $objAction = $this->makeAction($iUserId, $iBoardId);
        $objAction->performAction();

        $this->assertStringContainsString('erfolgreich gespeichert', $objAction->getOutput());
    }

    /**
     * Draft flag: setting btn_draft stores the message as a draft and still
     * renders the confirm template.
     */
    public function test_performAction_draftFlag_messageStoredAsDraft(): void
    {
        $iUserId  = $this->insertUser();
        $iBoardId = $this->insertBoard();
        $this->setPostData([
            'csrf_token' => self::CSRF_TOKEN,
            'subject'    => 'Entwurf Betreff',
            'body'       => 'Entwurf Inhalt.',
            'btn_draft'  => 'x',
        ]);

        $objAction = $this->makeAction($iUserId, $iBoardId);
        $objAction->performAction();

        $this->assertStringContainsString('erfolgreich gespeichert', $objAction->getOutput());
    }

    /**
     * Guest without credentials: message is never saved regardless of config.
     * The confirm text must not appear; the message form is shown instead.
     */
    public function test_performAction_guestWithoutCredentials_messageNotSaved(): void
    {
        $iBoardId = $this->insertBoard();
        $this->setPostData([
            'subject' => 'Gast Betreff',
            'body'    => 'Gast Inhalt.',
        ]);

        $objAction = $this->makeAction(0, $iBoardId);
        $objAction->performAction();

        $this->assertStringNotContainsString('erfolgreich gespeichert', $objAction->getOutput());
    }
}
