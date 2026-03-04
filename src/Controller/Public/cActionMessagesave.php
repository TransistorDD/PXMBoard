<?php

require_once(SRCDIR . '/Controller/Public/cPublicAction.php');
require_once(SRCDIR . '/Model/cUserConfig.php');
require_once(SRCDIR . '/Model/cBoardMessage.php');
require_once(SRCDIR . '/Model/cMessage.php');
require_once(SRCDIR . '/Enum/eMessage.php');
require_once(SRCDIR . '/Model/cNotification.php');
require_once(SRCDIR . '/Enum/eNotification.php');
/**
 * saves a message
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cActionMessagesave extends cPublicAction
{
    /**
     * Validate permissions for this action
     *
     * @return bool true if all permissions granted
     */
    public function validateBasePermissionsAndConditions(): bool
    {
        return ($this->_requireWritableBoard() && (!$this->_requireAuthentication() || $this->_requireValidCsrfToken()));
    }

    /**
     * perform the action
     *
     * @return void
     */
    public function performAction(): void
    {
        $objActiveBoard = $this->getActiveBoard();
        $iBoardId = $objActiveBoard->getId();
        $arrErrors = [];

        $iMessageId = $this->m_objInputHandler->getIntFormVar('msgid', true, true, true);
        $sSubject = $this->m_objInputHandler->getStringFormVar('subject', 'subject', true, false, 'trim');
        $sBody = $this->m_objInputHandler->getStringFormVar('body', 'body', true, false, 'rtrim');
        $bEditMode = (strlen($this->m_objInputHandler->getStringFormVar('btn_edit', 'character', true, true)) > 0);
        $bSaveAsDraft = (strlen($this->m_objInputHandler->getStringFormVar('btn_draft', 'character', true, true)) > 0);
        $eMessageStatus = $bSaveAsDraft ? MessageStatus::DRAFT : MessageStatus::PUBLISHED;

        if (!$bEditMode) {

            $objActiveUser = $this->getActiveUser();

            if (!is_object($objActiveUser)) {

                unset($objActiveUser);		// TODO: destroy reference Check
                $objActiveUser = new cUserConfig();

                if ($this->m_objConfig->useQuickPost()) {

                    $sUserName = $this->m_objInputHandler->getStringFormVar('nick', 'username', true, true, 'trim');

                    if (!empty($sUserName)) {
                        if ($objActiveUser->loadDataByUserName($sUserName)) {
                            if (!$objActiveUser->validatePassword($this->m_objInputHandler->getStringFormVar('pass', 'password', true, true, 'trim'))) {
                                $arrErrors[] = eError::INVALID_PASSWORD;	// invalid password
                            } elseif ($objActiveUser->getStatus() != UserStatus::ACTIVE) {
                                $arrErrors[] = eError::NOT_AUTHORIZED;	// not allowed
                            } elseif ($this->m_objConfig->getOnlineTime() > 0) {
                                $objActiveUser->updateLastOnlineTimestamp($this->m_objConfig->getAccessTimestamp());
                            }
                        } else {
                            $arrErrors[] = eError::USERNAME_UNKNOWN;		// user unknown
                        }
                    } else {
                        $arrErrors[] = eError::USERNAME_REQUIRED;
                    }				// empty username
                } else {
                    $arrErrors[] = eError::NOT_LOGGED_IN;
                }						// not loged in
            }
        }

        if (empty($sSubject)) {
            $arrErrors[] = eError::SUBJECT_MISSING;								// missing subject
        }

        if (!empty($arrErrors) || ($bEditMode)) {
            $this->m_objTemplate = $this->_getTemplateObject('messageform');

            $this->m_objTemplate->addData($this->getContextDataArray(['quickpost' => $this->m_objConfig->useQuickPost()]));

            if (!empty($arrErrors)) {
                $arrErrorTexts = [];
                foreach ($arrErrors as $objError) {
                    $arrErrorTexts[] = ['text' => $objError->value];
                }
                $this->m_objTemplate->addData(['error' => $arrErrorTexts]);
            }

            // parse the message body
            $objPxmParser = $this->_getPredefinedPxmParser(true);

            $this->m_objTemplate->addData(['msg' => ['id'		=> $iMessageId,
                                                                'subject'	=> $sSubject,
                                                                '_body'		=> $objPxmParser->parse($sBody)]]);
        } else {
            if ($objActiveUser->isPostAllowed()) {

                // replace badwords
                require_once(SRCDIR . '/Model/cBadwordList.php');
                $objBadwordList = new cBadwordList();
                $arrBadwords = $objBadwordList->getList();
                $sSubject = str_replace($arrBadwords['search'], $arrBadwords['replace'], $sSubject);
                $sBody = str_replace($arrBadwords['search'], $arrBadwords['replace'], $sBody);

                $objBoardMessage = new cBoardMessage();

                $objBoardMessage->setBoardId($iBoardId);
                $objBoardMessage->setAuthor($objActiveUser);
                $objBoardMessage->setSubject($sSubject);
                $objBoardMessage->setBody($sBody);
                $objBoardMessage->setMessageTimestamp($this->m_objConfig->getAccessTimestamp());
                $objBoardMessage->setIp($this->m_objServerHandler->getRemoteAddr());
                $objBoardMessage->setNotifyOnReply($this->m_objInputHandler->getIntFormVar('notify_on_reply', true, true, true));

                $objBoardMessage->setStatus($eMessageStatus);
                $iReturn = $objBoardMessage->insertData(
                    $iMessageId,
                    $this->m_objConfig->getThreadSizeLimit()
                );
                if ($iReturn === null) {

                    // count message for the author
                    if ($objActiveUser->getId() > 0) {
                        $objActiveUser->incrementMessageQuantity();
                    }

                    // Auto-activate notification for author (only for published messages)
                    if ($eMessageStatus === MessageStatus::PUBLISHED && $objActiveUser->getId() > 0) {
                        $objBoardMessage->setNotificationForUser($objActiveUser->getId(), true);
                    }

                    // reply notification - notify all subscribed users
                    $objReplyMessage = new cBoardMessage();
                    if ($objReplyMessage->loadDataById($iMessageId, $iBoardId)) {
                        // Get all users who are subscribed to the parent message
                        $arrSubscriberIds = $objReplyMessage->getNotificationUserIds();

                        foreach ($arrSubscriberIds as $iSubscriberId) {
                            // Don't notify the author of the reply
                            if ($iSubscriberId == $objActiveUser->getId()) {
                                continue;
                            }

                            // Create in-app notification
                            $sNotificationTitle = 'Neue Antwort auf einen Beitrag';
                            $sNotificationMessage = $objActiveUser->getUserName().' hat auf "'.$objReplyMessage->getSubject().'" geantwortet';
                            $sNotificationLink = 'pxmboard.php?mode=board&brdid='.$objBoardMessage->getBoardId().'&thrdid='.$objBoardMessage->getThreadId().'&msgid='.$objBoardMessage->getId().'#msg'.$objBoardMessage->getId();

                            cNotification::createNotification(
                                $iSubscriberId,
                                NotificationType::REPLY,
                                $sNotificationTitle,
                                $sNotificationMessage,
                                $sNotificationLink,
                                $objBoardMessage->getId(),
                                0
                            );
                        }

                        // Send email notify_on_reply (only to original author if enabled)
                        $objReplyAuthor = $objReplyMessage->getAuthor();
                        if ($objActiveUser->getId() != $objReplyAuthor->getId()) {
                            if ($objReplyMessage->shouldNotifyOnReply()) {
                                if ($objReplyAuthor->loadDataById($objReplyAuthor->getId()) && ($sMail = $objReplyAuthor->getPrivateMail())) {
                                    require_once(SRCDIR . '/Model/cTemplate.php');
                                    $objReplyNotificationMailSubject = new cTemplate();
                                    $objReplyNotificationMailSubject->loadDataById(13);
                                    $objReplyNotificationMailBody = new cTemplate();
                                    $objReplyNotificationMailBody->loadDataById(14);

                                    @mail(
                                        $sMail,
                                        $objReplyNotificationMailSubject->getMessage(),
                                        str_replace(
                                            ['%username%',
                                                            '%subject%',
                                                            '%id%',
                                                            '%replysubject%',
                                                            '%replyid%',
                                                            '%boardid%',
                                                            '%threadid%'],
                                            [$objActiveUser->getUserName(),
                                                            $objReplyMessage->getSubject(),
                                                            (string)$objReplyMessage->getId(),
                                                            $sSubject,
                                                            (string)$objBoardMessage->getId(),
                                                            (string)$iBoardId,
                                                            (string)$objReplyMessage->getThreadId()],
                                            $objReplyNotificationMailBody->getMessage()
                                        ),
                                        'From: '.$this->m_objConfig->getMailWebmaster()."\nReply-To: ".$this->m_objConfig->getMailWebmaster()
                                    );
                                }
                            }
                        }
                    }

                    // Mention notifications (only for published messages, not drafts)
                    if ($eMessageStatus === MessageStatus::PUBLISHED) {
                        $this->_createMentionNotifications($objBoardMessage);
                    }

                    // Use consolidated confirm template (Story 18)
                    $this->m_objTemplate = $this->_getTemplateObject('confirm');

                    // Pass structured data instead of HTML (separation of concerns)
                    $this->m_objTemplate->addData($this->getContextDataArray());
                    $this->m_objTemplate->addData([
                        'message' => 'Ihre Nachricht wurde erfolgreich gespeichert.',
                        'msg' => [
                            'id' => $objBoardMessage->getId(),
                            'thread' => ['id' => $objBoardMessage->getThreadId()],
                            'board' => ['id' => $objBoardMessage->getBoardId()]
                        ]
                    ]);
                } else {
                    $this->m_objTemplate = $this->_getTemplateObject('messageform');

                    // parse the message body
                    $objPxmParser = $this->_getPredefinedPxmParser(true);

                    $this->m_objTemplate->addData($this->getContextDataArray(['quickpost' => $this->m_objConfig->useQuickPost()]));
                    $this->m_objTemplate->addData(['error' => [['text' => $iReturn->value]]]);
                    $this->m_objTemplate->addData(['msg' => ['id'		=> $iMessageId,
                                                                                'subject'	=> $sSubject,
                                                                                '_body'	=> $objPxmParser->parse($sBody)]]);
                }
            } else {
                $this->m_objTemplate = $this->_getErrorTemplateObject(eError::NOT_AUTHORIZED);
            }	// forbidden
        }
    }

    /**
     * Extract mentions from message body and create notifications
     *
     * @param cBoardMessage $objMessage The board message containing mentions
     * @return void
     */
    private function _createMentionNotifications(cBoardMessage $objMessage): void
    {
        $objAuthor = $objMessage->getAuthor();
        $sBody = $objMessage->getBody();
        $iMessageId = $objMessage->getId();
        $iAuthorId = $objAuthor->getId();
        $iBoardId = $objMessage->getBoardId();
        $iThreadId = $objMessage->getThreadId();
        $sAuthorUsername = $objAuthor->getUserName();

        // Extract all user IDs from [user:id] tags
        if (preg_match_all('/\[user:(\d+)\]/', $sBody, $arrMatches)) {
            $arrUserIds = array_unique(array_map('intval', $arrMatches[1]));

            // Limit to max 10 mentions per message
            $arrUserIds = array_slice($arrUserIds, 0, 10);

            // Remove author from mention list (no self-notification)
            $arrUserIds = array_filter($arrUserIds, function ($iUserId) use ($iAuthorId) {
                return $iUserId !== $iAuthorId;
            });

            if (!empty($arrUserIds)) {
                // Create notification for each mentioned user
                foreach ($arrUserIds as $iUserId) {
                    $sNotificationTitle = 'Du wurdest erwähnt';
                    $sNotificationMessage = $sAuthorUsername.' hat dich in einem Beitrag erwähnt';
                    $sNotificationLink = 'pxmboard.php?mode=board&brdid='.$iBoardId.'&thrdid='.$iThreadId.'&msgid='.$iMessageId.'#msg'.$iMessageId;

                    cNotification::createNotification(
                        $iUserId,
                        NotificationType::MENTION,
                        $sNotificationTitle,
                        $sNotificationMessage,
                        $sNotificationLink,
                        $iMessageId,
                        0
                    );
                }
            }
        }
    }
}
