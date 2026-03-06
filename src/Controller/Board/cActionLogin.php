<?php

namespace PXMBoard\Controller\Board;

use PXMBoard\Enum\eErrorKeys;
use PXMBoard\Enum\eUserStatus;
use PXMBoard\Model\cSession;
use PXMBoard\Model\cUserConfig;

/**
 * user login
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cActionLogin extends cActionBoardlist
{
    /**
     * perform the action
     *
     * @return void
     */
    public function performAction(): void
    {
        $sUserName = $this->m_objInputHandler->getStringFormVar('username', 'username', true, false, 'trim');
        $sPassword = $this->m_objInputHandler->getStringFormVar('password', 'password', true, false, 'trim');
        $objError = null;

        if (!$this->getActiveUser() && (!empty($sUserName))) {

            $objUser = new cUserConfig();

            if ($objUser->loadDataByUserName($sUserName)) {
                if (!$objUser->validatePassword($sPassword)) {
                    $objError = eErrorKeys::INVALID_PASSWORD;				// invalid password
                }
            } else {
                $objError = eErrorKeys::USERNAME_UNKNOWN;					// user not found
            }
            if (!is_object($objError)) {
                if ($objUser->getStatus() === eUserStatus::ACTIVE) {

                    // Set active user in action context
                    // pxmboard.php will handle session update automatically
                    $this->m_objActiveUser = $objUser;

                    // Handle "stay logged in" checkbox
                    if ($this->m_objInputHandler->getIntFormVar('staylogedin', true, true) > 0) {
                        $sTicket = $objUser->createNewTicket($this->m_objServerHandler->getUserAgent(), $this->m_objServerHandler->getRemoteAddr());
                        cSession::setCookieVar('ticket', $sTicket, $this->m_objConfig->getAccessTimestamp() + 15552000); // 180 days / 6 months
                    }

                    // Re-initialize skin with user preferences
                    $this->initSkin();
                } else {
                    $objError = eErrorKeys::NOT_AUTHORIZED;
                }				// forbidden
            }
        }

        cActionBoardlist::performAction();

        if (is_object($objError)) {
            $this->m_objTemplate->addData(['error' => ['text' => $objError->t()]]);
        }
    }
}
