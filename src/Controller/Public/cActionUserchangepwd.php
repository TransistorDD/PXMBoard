<?php

require_once(SRCDIR . '/Controller/Public/cPublicAction.php');
require_once(SRCDIR . '/Model/cSession.php');
require_once(SRCDIR . '/Enum/eSuccessKeys.php');
/**
 * change the password of an user
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cActionUserchangepwd extends cPublicAction
{
    /**
     * Validate permissions for this action
     *
     * @return bool true if all permissions granted
     */
    public function validateBasePermissionsAndConditions(): bool
    {
        return $this->_requireAuthentication();
    }

    /**
     * perform the action
     *
     * @return void
     */
    public function performAction(): void
    {

        $sPassword1 = $this->m_objInputHandler->getStringFormVar('pwd', 'password', true, true, 'trim');
        $sPassword2 = $this->m_objInputHandler->getStringFormVar('pwdc', 'password', true, true, 'trim');
        if (!empty($sPassword1) && !empty($sPassword2)) {
            if ($this->getActiveUser()->changePassword($sPassword1, $sPassword2)) {

                // Delete ticket cookie if exists (password changed)
                if (cSession::getCookieVar('ticket')) {
                    cSession::setCookieVar('ticket', '', time() - 3600);
                }

                $this->m_objTemplate = $this->_getTemplateObject('confirm');
                $this->m_objTemplate->addData($this->getContextDataArray());
                $this->m_objTemplate->addData(['message' => eSuccessKeys::USER_PASSWORD_CHANGED->t()]);
            } else {
                $this->m_objTemplate = $this->_getErrorTemplateObject(eErrorKeys::COULD_NOT_UPDATE_PASSWORD);
            }// pwd not valid
        } else {
            $this->m_objTemplate = $this->_getTemplateObject('userchangepwdform');
            $this->m_objTemplate->addData($this->getContextDataArray());
        }
    }
}
