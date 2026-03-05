<?php

require_once(SRCDIR . '/Controller/Public/cPublicAction.php');
require_once(SRCDIR . '/Model/cUser.php');
require_once(SRCDIR . '/Model/cTemplate.php');
require_once(SRCDIR . '/Enum/eUserStatus.php');
require_once(SRCDIR . '/Enum/eSuccessKeys.php');
/**
 * send the password to the user
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cActionUsersendpwd extends cPublicAction
{
    /**
     * Validate permissions for this action
     *
     * @return bool true if all permissions granted
     */
    public function validateBasePermissionsAndConditions(): bool
    {
        return $this->_requireNotAuthenticated();
    }

    /**
     * perform the action
     *
     * @return void
     */
    public function performAction(): void
    {

        $sPasswordKey = $this->m_objInputHandler->getStringFormVar('key', 'key', true, true, 'trim');
        $sUserName = $this->m_objInputHandler->getStringFormVar('username', 'username', true, true, 'trim');
        $sEmail = $this->m_objInputHandler->getStringFormVar('email', 'email', true, true, 'trim');

        if (!empty($sUserName)) {
            $objUser = new cUser();
            if ($objUser->loadDataByUserName($sUserName)) {
                if ($objUser->getStatus() === eUserStatus::ACTIVE) {
                    if (strcasecmp($sEmail, $objUser->getPrivateMail()) == 0) {

                        $objNotification = new cTemplate();
                        $objNotification->loadDataById(6);
                        $sPasswordMailSubject = $objNotification->getMessage();
                        $objNotification->loadDataById(7);
                        $sPasswordMailBody = $objNotification->getMessage();

                        if (@mail(
                            $objUser->getPrivateMail(),
                            $sPasswordMailSubject,
                            str_replace(['%key%','%username%'], [$objUser->createNewPasswordKey(),$objUser->getUserName()], $sPasswordMailBody),
                            'From: '.$this->m_objConfig->getMailWebmaster()."\nReply-To: ".$this->m_objConfig->getMailWebmaster()
                        )) {

                            $this->m_objTemplate = $this->_getTemplateObject('confirm');
                            $this->m_objTemplate->addData($this->getContextDataArray());
                            $this->m_objTemplate->addData(['message' => eSuccessKeys::USER_PASSWORD_RESET_REQUESTED->t()]);
                        } else {
                            $this->m_objTemplate = $this->_getErrorTemplateObject(eErrorKeys::COULD_NOT_SEND_EMAIL);
                        }// could not send email
                    } else {
                        $this->m_objTemplate = $this->_getErrorTemplateObject(eErrorKeys::DATA_MISMATCH);
                    }	// data does not match
                } else {
                    $this->m_objTemplate = $this->_getErrorTemplateObject(eErrorKeys::NOT_AUTHORIZED);
                }		// forbidden
            } else {
                $this->m_objTemplate = $this->_getErrorTemplateObject(eErrorKeys::USERNAME_UNKNOWN);
            }				// invalid username
        } elseif (!empty($sPasswordKey)) {
            $objUser = new cUser();
            if ($objUser->loadDataByPasswordKey($sPasswordKey)) {
                if ($objUser->getStatus() === eUserStatus::ACTIVE) {

                    $objNotification = new cTemplate();
                    $objNotification->loadDataById(8);
                    $sPasswordMailSubject = $objNotification->getMessage();
                    $objNotification->loadDataById(9);
                    $sPasswordMailBody = $objNotification->getMessage();

                    $sPassword = $objUser->generatePassword();
                    if ($objUser->updateData()) {
                        if (@mail(
                            $objUser->getPrivateMail(),
                            $sPasswordMailSubject,
                            str_replace(['%password%','%username%'], [$sPassword,$objUser->getUserName()], $sPasswordMailBody),
                            'From: '.$this->m_objConfig->getMailWebmaster()."\nReply-To: ".$this->m_objConfig->getMailWebmaster()
                        )) {

                            $this->m_objTemplate = $this->_getTemplateObject('confirm');
                            $this->m_objTemplate->addData($this->getContextDataArray());
                            $this->m_objTemplate->addData(['message' => eSuccessKeys::USER_PASSWORD_SENT->t()]);
                        } else {
                            $this->m_objTemplate = $this->_getErrorTemplateObject(eErrorKeys::COULD_NOT_SEND_EMAIL);
                        }// could not send email
                    } else {
                        $this->m_objTemplate = $this->_getErrorTemplateObject(eErrorKeys::COULD_NOT_INSERT_DATA);
                    }	// could not insert data
                } else {
                    $this->m_objTemplate = $this->_getErrorTemplateObject(eErrorKeys::NOT_AUTHORIZED);
                }	// forbidden
            } else {
                $this->m_objTemplate = $this->_getErrorTemplateObject(eErrorKeys::INVALID_USER_ID);
            }		// invalid userid
        } else {
            $this->m_objTemplate = $this->_getTemplateObject('usersendpwdform');
            $this->m_objTemplate->addData($this->getContextDataArray());
        }
    }
}
