<?php

require_once(SRCDIR . '/Controller/Public/cPublicAction.php');
require_once(SRCDIR . '/Model/cForbiddenMailList.php');
require_once(SRCDIR . '/Model/cProfileConfig.php');
require_once(SRCDIR . '/Model/cUserProfile.php');
require_once(SRCDIR . '/Enum/eUser.php');
require_once(SRCDIR . '/Enum/eSuccessMessage.php');
/**
 * registers a user
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cActionUserregistration extends cPublicAction
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

        $sUserName = $this->m_objInputHandler->getStringFormVar('nick', 'username', true, true, 'trim');
        $sEmail = $this->m_objInputHandler->getStringFormVar('email', 'email', true, true, 'trim');

        if (empty($sUserName) || empty($sEmail)) {
            $this->m_objTemplate = $this->_getTemplateObject('userregistrationform');
            $this->m_objTemplate->addData($this->getContextDataArray());
        } else {
            $bSuccess = false;

            $objForbiddenMailList = new cForbiddenMailList();
            $objProfileConfig = new cProfileConfig();
            $arrSlotList = $objProfileConfig->getSlotList();

            $objUserProfile = new cUserProfile($arrSlotList);
            if ($objUserProfile->setRegistrationMail($sEmail, $objForbiddenMailList->getList())) {
                $objUserProfile->setUserName($sUserName);
                $objUserProfile->setCity($this->m_objInputHandler->getStringFormVar('city', 'city', true, true, 'trim'));
                $objUserProfile->setPrivateMail($sEmail);
                $objUserProfile->setPublicMail($this->m_objInputHandler->getStringFormVar('pubemail', 'email', true, true, 'trim'));
                $objUserProfile->setRegistrationTimestamp($this->m_objConfig->getAccessTimestamp());
                $objUserProfile->setSignature($this->m_objInputHandler->getStringFormVar('signature', 'signature', true, true, 'rtrim'));
                $objUserProfile->setFirstName($this->m_objInputHandler->getStringFormVar('fname', 'firstname', true, true, 'trim'));
                $objUserProfile->setLastName($this->m_objInputHandler->getStringFormVar('lname', 'lastname', true, true, 'trim'));

                $sPassword = $objUserProfile->generatePassword();

                $objUserProfile->setStatus($this->m_objConfig->useDirectRegistration() ? (UserStatus::ACTIVE) : (UserStatus::NOT_ACTIVATED));

                if ($objUserProfile->insertData($this->m_objConfig->uniqueRegistrationMails())) {	// insert profiledata >>>
                    foreach ($arrSlotList as $sKey => $arrVal) {
                        if ($arrVal[0] == 'i') {
                            $objUserProfile->setAdditionalDataElement($sKey, $this->m_objInputHandler->getIntFormVar($sKey, true, true, false));
                        } else {
                            $sValue = $this->m_objInputHandler->getStringFormVar($sKey, '', true, true, 'trim');
                            if (strlen($sValue) > $arrVal[1]) {
                                $sValue = substr($sValue, 0, $arrVal[1]);
                            }
                            $objUserProfile->setAdditionalDataElement($sKey, $sValue);
                        }
                    }
                    $objUserProfile->setLastUpdateTimestamp($this->m_objConfig->getAccessTimestamp());
                    $objUserProfile->updateData();											// <<< insert profiledata

                    if ($this->m_objConfig->useDirectRegistration()) {

                        require_once(SRCDIR . '/Model/cTemplate.php');
                        $objRegistrationMailSubject = new cTemplate();
                        $objRegistrationMailSubject->loadDataById(1);
                        $objRegistrationMailBody = new cTemplate();
                        $objRegistrationMailBody->loadDataById(2);

                        if (@mail(
                            $objUserProfile->getPrivateMail(),
                            $objRegistrationMailSubject->getMessage(),
                            str_replace(['%password%','%username%'], [$sPassword,$objUserProfile->getUserName()], $objRegistrationMailBody->getMessage()),
                            'From: '.$this->m_objConfig->getMailWebmaster()."\nReply-To: ".$this->m_objConfig->getMailWebmaster()
                        )) {

                            $bSuccess = true;
                        } else {
                            $this->m_objTemplate = $this->_getErrorTemplateObject(eError::COULD_NOT_SEND_EMAIL); // could not send email
                        }
                    } else {
                        $bSuccess = true;
                    }

                    if ($bSuccess) {
                        $this->m_objTemplate = $this->_getTemplateObject('confirm');
                        $this->m_objTemplate->addData(['message' => eSuccessMessage::USER_REGISTERED->value]);
                    }
                } else {
                    $this->m_objTemplate = $this->_getErrorTemplateObject(eError::USERNAME_ALREADY_EXISTS);
                }// user already registered
            } else {
                $this->m_objTemplate = $this->_getErrorTemplateObject(eError::INVALID_EMAIL);
            }	// invalid email
        }
    }
}
