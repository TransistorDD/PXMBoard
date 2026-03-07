<?php

namespace PXMBoard\Controller\Board;

use PXMBoard\Enum\eErrorKeys;
use PXMBoard\Enum\eNotificationKeys;
use PXMBoard\Enum\eNotificationType;
use PXMBoard\Enum\eSuccessKeys;
use PXMBoard\Model\cBadwordList;
use PXMBoard\Model\cNotification;
use PXMBoard\Model\cPrivateMessage;
use PXMBoard\Model\cTemplate;
use PXMBoard\Model\cUserConfig;

/**
 * saves a private message
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cActionPrivatemessagesave extends cPublicAction
{
    /**
     * Validate permissions for this action
     *
     * @return bool true if all permissions granted
     */
    public function validateBasePermissionsAndConditions(): bool
    {
        return $this->_requireValidCsrfToken() && $this->_requirePostPermission();
    }

    /**
     * perform the action
     *
     * @return void
     */
    public function performAction(): void
    {
        $objActiveUser = $this->getActiveUser();

        $iDestinationId = $this->m_objInputHandler->getIntFormVar('toid', true, true, true);
        if ($iDestinationId > 0) {

            $objDestinationUser = new cUserConfig();
            if ($objDestinationUser->loadDataById($iDestinationId)) {

                $sSubject = $this->m_objInputHandler->getStringFormVar('subject', 'subject', true, false, 'trim');
                $sBody = $this->m_objInputHandler->getStringFormVar('body', 'body', true, false, 'rtrim');

                if (empty($sSubject)) {
                    // parse the message body
                    $objPxmParser = $this->_getPredefinedPxmParser(true);

                    $this->m_objTemplate = $this->_getTemplateObject('privatemessageform');
                    $this->m_objTemplate->addData($this->getContextDataArray(['type' => 'outbox']));
                    $this->m_objTemplate->addData(['error' => ['text'   => eErrorKeys::SUBJECT_MISSING->t()]]);
                    $this->m_objTemplate->addData(['touser'	=> ['id'        => $objDestinationUser->getId(),
                                                                'username'	=> $objDestinationUser->getUserName()]]);
                    $this->m_objTemplate->addData(['msg'	=> ['subject'	=> $sSubject,
                                                                '_body'		=> $objPxmParser->parse($sBody)]]);
                } else {
                    // replace badwords
                    $objBadwordList = new cBadwordList();
                    $arrBadwords = $objBadwordList->getList();
                    $sSubject = str_replace($arrBadwords['search'], $arrBadwords['replace'], $sSubject);
                    $sBody = str_replace($arrBadwords['search'], $arrBadwords['replace'], $sBody);

                    $objPrivateMessage = new cPrivateMessage();
                    $objPrivateMessage->setDestinationUserId($objDestinationUser->getId());
                    $objPrivateMessage->setAuthor($objActiveUser);
                    $objPrivateMessage->setSubject($sSubject);
                    $objPrivateMessage->setBody($sBody);
                    $objPrivateMessage->setMessageTimestamp($this->m_objConfig->getAccessTimestamp());
                    $objPrivateMessage->setIp($this->m_objServerHandler->getRemoteAddr());

                    $eError = $objPrivateMessage->insertData();
                    if ($eError === null) {

                        // Create in-app notification
                        $sNotificationTitle = eNotificationKeys::PRIVATE_MESSAGE_TITLE->t();
                        $sNotificationMessage = eNotificationKeys::PRIVATE_MESSAGE_MESSAGE->t([
                            'username' => $objActiveUser->getUserName(),
                            'subject'  => $sSubject,
                        ]);
                        $sNotificationLink = 'pxmboard.php?mode=privatemessage&type=inbox&msgid='.$objPrivateMessage->getId();

                        cNotification::createNotification(
                            $objDestinationUser->getId(),
                            eNotificationType::PRIVATE_MESSAGE,
                            $sNotificationTitle,
                            $sNotificationMessage,
                            $sNotificationLink,
                            0,
                            $objPrivateMessage->getId()
                        );

                        // Send email notification
                        if ($objDestinationUser->sendPrivateMessageNotification() && ($sMail = $objDestinationUser->getPrivateMail())) {

                            $objPrivateMessageMailSubject = new cTemplate();
                            $objPrivateMessageMailSubject->loadDataById(11);
                            $objPrivateMessageMailBody = new cTemplate();
                            $objPrivateMessageMailBody->loadDataById(12);

                            @mail(
                                $sMail,
                                $objPrivateMessageMailSubject->getMessage(),
                                str_replace('%username%', $objActiveUser->getUserName(), $objPrivateMessageMailBody->getMessage()),
                                'From: '.$this->m_objConfig->getMailWebmaster()."\nReply-To: ".$this->m_objConfig->getMailWebmaster()
                            );
                        }
                        $this->m_objTemplate = $this->_getTemplateObject('confirm');
                        $this->m_objTemplate->addData($this->getContextDataArray([
                            'show_pm_tabs' => true,
                            'type' => 'outbox'
                        ]));
                        $this->m_objTemplate->addData([
                            'message' => eSuccessKeys::PRIVATE_MESSAGE_SENT->t(),
                        ]);
                    } else {
                        $this->m_objTemplate = $this->_getErrorTemplateObject($eError);
                    }
                }
            } else {
                $this->m_objTemplate = $this->_getErrorTemplateObject(eErrorKeys::INVALID_USER_ID);
            }// invalid user id
        } else {
            $this->m_objTemplate = $this->_getErrorTemplateObject(eErrorKeys::INVALID_USER_ID);
        }	// invalid user id
    }
}
