<?php

require_once(SRCDIR . '/Controller/Admin/cAdminAction.php');
require_once(SRCDIR . '/Model/cTemplate.php');
require_once(SRCDIR . '/Enum/eUser.php');
require_once(SRCDIR . '/Model/cUser.php');
/**
 * handles the user activation
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cAdminActionActivateusers extends cAdminAction
{
    /**
     * Validate permissions - requires admin rights and valid CSRF token.
     *
     * @return bool true if admin and CSRF valid, false otherwise
     */
    public function validateBasePermissionsAndConditions(): bool
    {
        return $this->_requireValidCsrfToken() && $this->_requireAdminPermission();
    }

    /**
     * perform the action
     *
     * @return void
     */
    public function performAction(): void
    {

        $this->m_sOutput .= $this->_getHead();

        if (!$this->m_objConfig->useDirectRegistration()) {

            $objTemplate = new cTemplate();
            $objTemplate->loadDataById(1);
            $sRegistrationMailSubject = $objTemplate->getMessage();
            $objTemplate->loadDataById(2);
            $sRegistrationMailBody = $objTemplate->getMessage();
            $objTemplate->loadDataById(3);
            $sRegistrationDeclineMailSubject = $objTemplate->getMessage();
            $objTemplate->loadDataById(4);
            $sRegistrationDeclineMailBody = $objTemplate->getMessage();

            $objUser = new cUser();

            $this->m_sOutput .= "<div class=\"pxm-admin-card\"><div class=\"pxm-admin-card__header\">activate / delete users</div><div class=\"pxm-admin-card__body\">\n";
            $this->m_sOutput .= "<p><strong>activated users</strong></p>\n";

            $iUserActiveStatus = UserStatus::ACTIVE;
            $arrDeleteUsers = $this->m_objInputHandler->getArrFormVar('del', true, true, true, 'intval');
            foreach ($this->m_objInputHandler->getArrFormVar('act', true, true, true, 'intval') as $iUserId) {
                if (!in_array($iUserId, $arrDeleteUsers)) {

                    $this->m_sOutput .= "$iUserId -> ";

                    if ($objUser->loadDataById($iUserId)) {
                        $this->m_sOutput .= 'found -> ';
                        $objUser->setStatus($iUserActiveStatus);
                        $sPassword = $objUser->generatePassword();
                        if ($objUser->updateData()) {
                            $this->m_sOutput .= 'activated -> ';

                            if (@mail(
                                $objUser->getPrivateMail(),
                                $sRegistrationMailSubject,
                                str_replace(['%password%','%username%'], [$sPassword,$objUser->getUserName()], $sRegistrationMailBody),
                                'From: '.$this->m_objConfig->getMailWebmaster()."\nReply-To: ".$this->m_objConfig->getMailWebmaster()
                            )) {

                                $this->m_sOutput .= "mail sent<br>\n";
                            } else {
                                $this->m_sOutput .= '<span class="pxm-error"> could not send mail to '.htmlspecialchars($objUser->getPrivateMail())."</span><br>\n";
                            }
                        } else {
                            $this->m_sOutput .= "<span class=\"pxm-error\"> could not activate user</span><br>\n";
                        }
                    } else {
                        $this->m_sOutput .= "<span class=\"pxm-error\"> invalid userid</span><br>\n";
                    }
                }
            }

            $this->m_sOutput .= "<p><strong>deleted users</strong></p>\n";
            foreach ($arrDeleteUsers as $iUserId) {
                if ($objUser->loadDataById($iUserId)) {
                    if ($objUser->deleteData()) {
                        $this->m_sOutput .= "$iUserId -> deleted -> ";
                        if (@mail(
                            $objUser->getPrivateMail(),
                            $sRegistrationDeclineMailSubject,
                            str_replace(['%username%','%reason%'], [$objUser->getUserName(),$this->m_objInputHandler->getStringFormVar("r$iUserId", 'notification', true, true, 'rtrim')], $sRegistrationDeclineMailBody),
                            'From: '.$this->m_objConfig->getMailWebmaster()."\nReply-To: ".$this->m_objConfig->getMailWebmaster()
                        )) {

                            $this->m_sOutput .= "mail sent<br>\n";
                        } else {
                            $this->m_sOutput .= '<span class="pxm-error"> could not send mail to '.htmlspecialchars($objUser->getPrivateMail())."</span><br>\n";
                        }
                    } else {
                        $this->m_sOutput .= "$iUserId -> <span class=\"pxm-error\">could not delete data</span><br>\n";
                    }
                } else {
                    $this->m_sOutput .= "$iUserId -> <span class=\"pxm-error\">user not found</span><br>\n";
                }
            }
            $this->m_sOutput .= "</div></div>\n";
        } else {
            $this->m_sOutput .= $this->_getAlert('forbidden');
        }

        $this->m_sOutput .= $this->_getFooter();
    }
}
