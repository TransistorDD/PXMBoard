<?php

require_once(SRCDIR . '/Controller/cAction.php');
require_once(SRCDIR . '/Model/cBoardMessage.php');
require_once(SRCDIR . '/Model/cBadwordList.php');
/**
 * saves a message
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cActionMessageeditsave extends cAction
{
    /**
     * Validate permissions for this action
     *
     * @return bool true if all permissions granted
     */
    public function validateBasePermissionsAndConditions(): bool
    {
        return $this->_requireValidCsrfToken() && $this->_requireWritableBoard() && $this->_requireAuthentication();
    }

    /**
     * perform the action
     *
     * @return void
     */
    public function performAction(): void
    {
        $objActiveBoard = $this->getActiveBoard();
        $objActiveUser = $this->getActiveUser();

        $bAdminMode = ($objActiveUser->isAdmin() || $objActiveUser->isModerator($objActiveBoard->getId()));

        if ($bAdminMode || $objActiveBoard->isWritable()) {

            if ($objActiveUser->isEditAllowed()) {

                $iMessageId = $this->m_objInputHandler->getIntFormVar('msgid', true, true, true);
                $sSubject = $this->m_objInputHandler->getStringFormVar('subject', 'subject', true, false, 'trim');
                $sBody = $this->m_objInputHandler->getStringFormVar('body', 'body', true, false, 'rtrim');

                if (empty($sSubject)) {							// missing subject
                    $objError = eError::SUBJECT_MISSING;
                    $this->m_objTemplate = $this->_getTemplateObject('messageeditform');
                    $this->m_objTemplate->addData($this->getContextDataArray());
                    $this->m_objTemplate->addData(['error' => ['text' => $objError->value]]);
                    $this->m_objTemplate->addData(['msg' => ['subject'	=> $sSubject,
                                                                                '_body'	=> htmlspecialchars($sBody)]]);
                } else {
                    if ($iMessageId > 0) {

                        $objBoardMessage = new cBoardMessage();

                        if ($objBoardMessage->loadDataById($iMessageId, $objActiveBoard->getId())) {
                            if ($bAdminMode || $objBoardMessage->isThreadActive()) {
                                if ($bAdminMode || ($objActiveUser->getId() == $objBoardMessage->getAuthorId())) {
                                    if ($bAdminMode || $objBoardMessage->getReplyQuantity() < 1) {

                                        // Determine action based on button clicked
                                        require_once(SRCDIR . '/Enum/eMessage.php');
                                        $bDeleteDraft = (strlen($this->m_objInputHandler->getStringFormVar('delete_draft', 'character', true, true)) > 0);
                                        $bSaveAsDraft = (strlen($this->m_objInputHandler->getStringFormVar('save_draft', 'character', true, true)) > 0);
                                        $bPublish = (strlen($this->m_objInputHandler->getStringFormVar('publish', 'character', true, true)) > 0) || (!$bDeleteDraft && !$bSaveAsDraft);

                                        // Handle draft deletion
                                        if ($bDeleteDraft) {
                                            if ($objBoardMessage->isDraft() && ($objActiveUser->getId() == $objBoardMessage->getAuthorId())) {
                                                if ($objBoardMessage->deleteData()) {
                                                    $this->m_objTemplate = $this->_getTemplateObject('redirect');
                                                    $this->m_objTemplate->addData([
                                                        'redirect_url' => 'pxmboard.php?mode=threadlist&brdid='.$objActiveBoard->getId(),
                                                        'message' => 'Der Entwurf wurde gelöscht.'
                                                    ]);
                                                    return;
                                                } else {
                                                    $this->m_objTemplate = $this->_getErrorTemplateObject(eError::COULD_NOT_INSERT_DATA);
                                                    return;
                                                }
                                            } else {
                                                $this->m_objTemplate = $this->_getErrorTemplateObject(eError::NOT_AUTHORIZED);
                                                return;
                                            }
                                        }
                                        require_once(SRCDIR . '/Model/cTemplate.php');
                                        $objTemplate = new cTemplate();
                                        $objTemplate->loadDataById(10);

                                        // replace badwords
                                        $objBadwordList = new cBadwordList();
                                        $arrBadwords = $objBadwordList->getList();
                                        $sSubject = str_replace($arrBadwords['search'], $arrBadwords['replace'], $sSubject);
                                        $sBody = str_replace($arrBadwords['search'], $arrBadwords['replace'], $sBody);

                                        $objBoardMessage->setSubject($sSubject);
                                        // Only append edit signature when publishing, not when saving draft
                                        if ($bPublish) {
                                            $objBoardMessage->setBody($sBody.' '.str_replace(['%date%','%username%'], [date($this->m_objConfig->getDateFormat(), $this->m_objConfig->getAccessTimestamp()),$objActiveUser->getUserName()], $objTemplate->getMessage()));
                                        } else {
                                            $objBoardMessage->setBody($sBody);
                                        }
                                        // Set status based on button
                                        if ($bSaveAsDraft) {
                                            $objBoardMessage->setStatus(MessageStatus::DRAFT);
                                        } elseif ($bPublish) {
                                            // Update timestamp when publishing a draft
                                            if ($objBoardMessage->isDraft()) {
                                                $objBoardMessage->setMessageTimestamp($this->m_objConfig->getAccessTimestamp());
                                            }
                                            $objBoardMessage->setStatus(MessageStatus::PUBLISHED);
                                        }
                                        $iReturn = $objBoardMessage->updateData();
                                        if ($iReturn == 0) {
                                            // Use consolidated confirm template (Story 18)
                                            $this->m_objTemplate = $this->_getTemplateObject('confirm');
                                            $this->m_objTemplate->addData($this->getContextDataArray());
                                            $this->m_objTemplate->addData([
                                                'message' => 'Ihre Nachricht wurde erfolgreich aktualisiert.',
                                                'msg' => [
                                                    'id' => $iMessageId,
                                                    'thread' => ['id' => $objBoardMessage->getThreadId()],
                                                    'board' => ['id' => $objBoardMessage->getBoardId()]
                                                ]
                                            ]);
                                        } else {
                                            // Map old error IDs to enum
                                            $error = match($iReturn) {
                                                6 => eError::INVALID_MESSAGE_ID,
                                                7 => eError::SUBJECT_MISSING,
                                                14 => eError::MESSAGE_ALREADY_EXISTS,
                                                default => eError::COULD_NOT_INSERT_DATA,
                                            };
                                            $this->m_objTemplate = $this->_getErrorTemplateObject($error);
                                        }
                                    } else {
                                        $this->m_objTemplate = $this->_getErrorTemplateObject(eError::MESSAGE_HAS_REPLY);
                                    } // replies exist
                                } else {
                                    $this->m_objTemplate = $this->_getErrorTemplateObject(eError::NOT_AUTHORIZED);
                                } // forbidden
                            } else {
                                $this->m_objTemplate = $this->_getErrorTemplateObject(eError::THREAD_CLOSED);
                            } // thread closed
                        } else {
                            $this->m_objTemplate = $this->_getErrorTemplateObject(eError::INVALID_MESSAGE_ID);
                        } // invalid msg id
                    } else {
                        $this->m_objTemplate = $this->_getErrorTemplateObject(eError::INVALID_MESSAGE_ID);
                    } // invalid msg id
                }
            } else {
                $this->m_objTemplate = $this->_getErrorTemplateObject(eError::NOT_AUTHORIZED);
            } // forbidden
        } else {
            $this->m_objTemplate = $this->_getErrorTemplateObject(eError::BOARD_CLOSED);
        } // board closed
    }
}
