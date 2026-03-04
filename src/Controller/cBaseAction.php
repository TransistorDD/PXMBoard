<?php

declare(strict_types=1);

require_once(SRCDIR . '/Model/cUserConfig.php');
require_once(SRCDIR . '/Enum/eUser.php');
require_once(SRCDIR . '/Enum/eError.php');
require_once(SRCDIR . '/Validation/cInputHandler.php');
require_once(SRCDIR . '/Validation/cServerHandler.php');

/**
 * Abstract base class for all board actions.
 *
 * Provides the action lifecycle, user/board loading, permission helpers,
 * and CSRF token validation. Template rendering and JSON output are handled
 * by the concrete subclasses (cPublicAction, cAjaxAction, cAdminAction).
 * Skin initialization is exclusively the responsibility of cPublicAction.
 *
 * @author Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright Torsten Rentsch 2001 - 2026
 */
abstract class cBaseAction
{
    protected mixed $m_objConfig;
    protected mixed $m_objInputHandler;
    protected cServerHandler $m_objServerHandler;
    protected ?cUserConfig $m_objActiveUser;
    protected mixed $m_objActiveBoard;
    protected mixed $m_objActiveSkin;
    protected ?string $m_sCsrfToken = null;

    /**
     * Constructor
     *
     * @param cConfig $objConfig configuration data of the board
     * @param int $iUserId user id from session (0 = guest)
     * @param int $iBoardId board id from request (0 = no board)
     */
    public function __construct(cConfig $objConfig, int $iUserId = 0, int $iBoardId = 0)
    {
        $this->m_objConfig = $objConfig;
        $this->m_objInputHandler = new cInputHandler();
        $this->m_objServerHandler = new cServerHandler();
        $this->m_objActiveUser = null;
        $this->m_objActiveBoard = null;
        $this->m_objActiveSkin = null;

        if ($iUserId > 0) {
            $this->_loadActiveUser($iUserId);
        }

        if ($iBoardId > 0) {
            $this->_loadActiveBoard($iBoardId);
        }
    }

    /**
     * Validate all base permissions and conditions required for this action.
     *
     * Must be implemented by every concrete action class.
     * Called by pxmboard.php before the action lifecycle begins.
     *
     * @return bool true if all permissions and conditions are met, false otherwise
     */
    abstract public function validateBasePermissionsAndConditions(): bool;

    /**
     * Called when a permission check fails.
     *
     * Subclasses render the error in their own output format
     * (template, JSON, HTML string).
     *
     * @param eError $error the error that caused the permission failure
     * @return void
     */
    abstract protected function _handlePermissionError(eError $error): void;

    /**
     * Validate the CSRF token submitted with the current request.
     *
     * Checks the X-CSRF-Token HTTP header first; falls back to POST field.
     * Can be called by concrete actions in validateBasePermissionsAndConditions()
     * or performAction() when the action processes state-changing requests.
     *
     * @return bool true if the token is valid, false otherwise
     */
    protected function _requireValidCsrfToken(): bool
    {
        $sToken = $this->m_objServerHandler->getHttpCsrfToken();
        if (empty($sToken)) {
            $sToken = $this->m_objInputHandler->getStringFormVar(
                'csrf_token',
                'csrf_token',
                true,
                false,
                'trim'
            );
        }
        if (empty($sToken) || empty($this->m_sCsrfToken)
            || !hash_equals($this->m_sCsrfToken, $sToken)
        ) {
            $this->_handlePermissionError(eError::CSRF_TOKEN_INVALID);
            return false;
        }
        return true;
    }

    /**
     * Get the output of this action.
     *
     * @return string rendered output (HTML, JSON, or empty string)
     */
    abstract public function getOutput(): string;

    /**
     * Set the CSRF token that was issued for the current session.
     * Called by pxmboard.php after instantiating the action.
     *
     * @param string $sToken token from the session
     * @return void
     */
    public function setCsrfToken(string $sToken): void
    {
        $this->m_sCsrfToken = $sToken;
    }

    /**
     * Execute pre-action hook (manipulate GET and POST data etc.)
     *
     * @return void
     */
    public function doPreActions(): void
    {
    }

    /**
     * Perform the action (main business logic).
     *
     * @return void
     */
    public function performAction(): void
    {
    }

    /**
     * Execute post-action hook (notifications, cleanup).
     *
     * @return void
     */
    public function doPostActions(): void
    {
    }

    /**
     * Require user to be authenticated (logged in).
     *
     * @return bool true if user is authenticated, false otherwise
     */
    protected function _requireAuthentication(): bool
    {
        if (!is_object($this->m_objActiveUser)) {
            $this->_handlePermissionError(eError::NOT_LOGGED_IN);
            return false;
        }
        return true;
    }

    /**
     * Require user to NOT be authenticated (for registration, password reset).
     *
     * @return bool true if user is not authenticated, false otherwise
     */
    protected function _requireNotAuthenticated(): bool
    {
        if (is_object($this->m_objActiveUser)) {
            $this->_handlePermissionError(eError::ALREADY_LOGGED_IN);
            return false;
        }
        return true;
    }

    /**
     * Require board to be readable (checks authentication if needed).
     *
     * PUBLIC and READONLY_PUBLIC: always readable
     * MEMBERS_ONLY and READONLY_MEMBERS: requires authentication
     * CLOSED: requires moderator/admin
     *
     * @return bool true if board is readable, false otherwise
     */
    protected function _requireReadableBoard(): bool
    {
        if (!is_object($this->m_objActiveBoard)) {
            $this->_handlePermissionError(eError::BOARD_ID_MISSING);
            return false;
        }

        $eStatus = $this->m_objActiveBoard->getStatus();

        if ($eStatus === BoardStatus::CLOSED) {
            if (!$this->m_objActiveUser?->isAdmin() && !$this->m_objActiveUser?->isModerator($this->m_objActiveBoard->getId())) {
                $this->_handlePermissionError(eError::BOARD_CLOSED);
                return false;
            }
        }

        if ($eStatus->requiresAuthentication()) {
            if (!$this->_requireAuthentication()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Require board to be writable (for posting messages).
     *
     * PUBLIC and MEMBERS_ONLY: writable for users with post permission
     * READONLY_*: only mods/admins can write
     * CLOSED: only mods/admins can write
     *
     * @return bool true if board is writable, false otherwise
     */
    protected function _requireWritableBoard(): bool
    {
        if (!$this->_requireReadableBoard()) {
            return false;
        }

        $eStatus = $this->m_objActiveBoard->getStatus();

        if (!$eStatus->isWritable()) {
            if (!$this->m_objActiveUser?->isAdmin() && !$this->m_objActiveUser?->isModerator($this->m_objActiveBoard->getId())) {
                $this->_handlePermissionError(eError::BOARD_READONLY);
                return false;
            }
        }

        return true;
    }

    /**
     * Require an active board to be set (exists and is active).
     *
     * @deprecated Use _requireReadableBoard() instead
     * @return bool true if board is set and readable, false otherwise
     */
    protected function _requireActiveBoard(): bool
    {
        return $this->_requireReadableBoard();
    }

    /**
     * Require board to be set (regardless of status).
     *
     * @return bool true if board is set, false otherwise
     */
    protected function _requireBoard(): bool
    {
        if (!is_object($this->m_objActiveBoard)) {
            $this->_handlePermissionError(eError::BOARD_ID_MISSING);
            return false;
        }
        return true;
    }

    /**
     * Require user to have posting permission.
     * Automatically checks authentication first.
     *
     * @return bool true if user can post, false otherwise
     */
    protected function _requirePostPermission(): bool
    {
        if (!$this->_requireAuthentication()) {
            return false;
        }
        if (!$this->m_objActiveUser?->isPostAllowed()) {
            $this->_handlePermissionError(eError::NOT_AUTHORIZED);
            return false;
        }
        return true;
    }

    /**
     * Require user to be moderator of current board or admin.
     * Automatically checks authentication and board first.
     *
     * @return bool true if user is moderator or admin, false otherwise
     */
    protected function _requireModeratorPermission(): bool
    {
        if (!$this->_requireAuthentication() || !$this->_requireBoard()) {
            return false;
        }
        if (!$this->m_objActiveUser?->isAdmin() && !$this->m_objActiveUser?->isModerator($this->m_objActiveBoard->getId())) {
            $this->_handlePermissionError(eError::NOT_AUTHORIZED);
            return false;
        }
        return true;
    }

    /**
     * Require user to be admin.
     * Automatically checks authentication first.
     *
     * @return bool true if user is admin, false otherwise
     */
    protected function _requireAdminPermission(): bool
    {
        if (!$this->_requireAuthentication()) {
            return false;
        }
        if (!$this->m_objActiveUser?->isAdmin()) {
            $this->_handlePermissionError(eError::NOT_AUTHORIZED);
            return false;
        }
        return true;
    }

    /**
     * Get board list array for templates.
     *
     * @return array board list data array
     */
    protected function _getBoardListArray(): array
    {
        require_once(SRCDIR . '/Model/cBoardList.php');
        require_once(SRCDIR . '/Parser/cParser.php');

        $objParser = new cParser();
        $objBoardList = new cBoardList();
        $objBoardList->loadBasicData();

        return $objBoardList->getDataArray(
            $this->m_objConfig->getTimeOffset() * 3600,
            $this->m_objConfig->getDateFormat(),
            0,
            $objParser
        );
    }

    /**
     * Load active user by ID.
     *
     * @param int $iUserId user id
     * @return void
     */
    protected function _loadActiveUser(int $iUserId): void
    {
        $objUser = new cUserConfig();
        if ($objUser->loadDataById($iUserId)) {
            if ($objUser->getStatus() === UserStatus::ACTIVE) {
                $this->m_objActiveUser = $objUser;

                if ($this->m_objConfig->getOnlineTime() > 0) {
                    $this->m_objActiveUser->updateLastOnlineTimestamp($this->m_objConfig->getAccessTimestamp());
                }
            }
        }
    }

    /**
     * Load active board by ID.
     *
     * @param int $iBoardId board id
     * @return void
     */
    protected function _loadActiveBoard(int $iBoardId): void
    {
        require_once(SRCDIR . '/Model/cBoard.php');
        $objBoard = new cBoard();
        if ($objBoard->loadDataById($iBoardId)) {
            $this->m_objActiveBoard = $objBoard;
        }
    }

    /**
     * Get active user.
     *
     * @return cUserConfig|null active user object or null
     */
    public function getActiveUser(): ?cUserConfig
    {
        return $this->m_objActiveUser;
    }

    /**
     * Get active board.
     *
     * @return cBoard|null active board object or null
     */
    public function getActiveBoard(): ?cBoard
    {
        return $this->m_objActiveBoard;
    }

    /**
     * Get active skin.
     *
     * @return cSkin|null active skin object or null
     */
    public function getActiveSkin(): ?cSkin
    {
        return $this->m_objActiveSkin;
    }
}
