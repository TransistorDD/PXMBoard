<?php

namespace PXMBoard\Controller\Board;

use PXMBoard\Enum\eErrorKeys;
use PXMBoard\Enum\eMessageStatus;
use PXMBoard\Enum\eSuccessKeys;
use PXMBoard\Model\cBadwordList;
use PXMBoard\Model\cBoardMessage;
use PXMBoard\Model\cTemplate;

/**
 * saves a message
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cActionMessageeditsave extends cPublicAction
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
                    $objError = eErrorKeys::SUBJECT_MISSING;

                    // parse the message body
                    $objPxmParser = $this->_getPredefinedPxmParser(true);

                    $this->m_objTemplate = $this->_getTemplateObject('messageeditform');
                    $this->m_objTemplate->addData($this->getContextDataArray());
                    $this->m_objTemplate->addData(['error' => ['text'   => $objError->t()]]);
                    $this->m_objTemplate->addData(['msg' => ['subject'  => $sSubject,
                                                             '_body'    => $objPxmParser->parse($sBody)]]);
                } else {
                    if ($iMessageId > 0) {

                        $objBoardMessage = new cBoardMessage();

                        if ($objBoardMessage->loadDataById($iMessageId, $objActiveBoard->getId())) {
                            if ($bAdminMode || $objBoardMessage->isThreadActive()) {
                                if ($bAdminMode || ($objActiveUser->getId() == $objBoardMessage->getAuthorId())) {
                                    if ($bAdminMode || $objBoardMessage->getReplyQuantity() < 1) {

                                        // Determine action based on button clicked
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
                                                        'message' => eSuccessKeys::DRAFT_DELETED->t()
                                                    ]);
                                                    return;
                                                } else {
                                                    $this->m_objTemplate = $this->_getErrorTemplateObject(eErrorKeys::COULD_NOT_INSERT_DATA);
                                                    return;
                                                }
                                            } else {
                                                $this->m_objTemplate = $this->_getErrorTemplateObject(eErrorKeys::NOT_AUTHORIZED);
                                                return;
                                            }
                                        }

                                        // replace badwords
                                        $objBadwordList = new cBadwordList();
                                        $arrBadwords = $objBadwordList->getList();
                                        $sSubject = str_replace($arrBadwords['search'], $arrBadwords['replace'], $sSubject);
                                        $sBody = str_replace($arrBadwords['search'], $arrBadwords['replace'], $sBody);

                                        $objBoardMessage->setSubject($sSubject);
                                        // Only append edit signature when publishing, not when saving draft
                                        if ($bPublish) {
                                            $objTemplate = new cTemplate();
                                            $objTemplate->loadDataById(10); // edit Message
                                            $objBoardMessage->setBody($sBody.' '.str_replace(['%date%','%username%'], [date($this->m_objConfig->getDateFormat(), $this->m_objConfig->getAccessTimestamp()),$objActiveUser->getUserName()], $objTemplate->getMessage()));
                                        } else {
                                            $objBoardMessage->setBody($sBody);
                                        }
                                        // Set status based on button
                                        if ($bSaveAsDraft) {
                                            $objBoardMessage->setStatus(eMessageStatus::DRAFT);
                                        } elseif ($bPublish) {
                                            // Update timestamp when publishing a draft
                                            if ($objBoardMessage->isDraft()) {
                                                $objBoardMessage->setMessageTimestamp($this->m_objConfig->getAccessTimestamp());
                                            }
                                            $objBoardMessage->setStatus(eMessageStatus::PUBLISHED);
                                        }
                                        $iReturn = $objBoardMessage->updateData();
                                        if ($iReturn === null) {
                                            $this->m_objTemplate = $this->_getTemplateObject('confirm');
                                            $this->m_objTemplate->addData($this->getContextDataArray());
                                            $this->m_objTemplate->addData([
                                                'message' => eSuccessKeys::MESSAGE_UPDATED->t(),
                                                'msg' => [
                                                    'id' => $iMessageId,
                                                    'thread' => ['id' => $objBoardMessage->getThreadId()],
                                                    'board' => ['id' => $objBoardMessage->getBoardId()]
                                                ]
                                            ]);
                                        } else {
                                            $this->m_objTemplate = $this->_getErrorTemplateObject($iReturn);
                                        }
                                    } else {
                                        $this->m_objTemplate = $this->_getErrorTemplateObject(eErrorKeys::MESSAGE_HAS_REPLY);
                                    } // replies exist
                                } else {
                                    $this->m_objTemplate = $this->_getErrorTemplateObject(eErrorKeys::NOT_AUTHORIZED);
                                } // forbidden
                            } else {
                                $this->m_objTemplate = $this->_getErrorTemplateObject(eErrorKeys::THREAD_CLOSED);
                            } // thread closed
                        } else {
                            $this->m_objTemplate = $this->_getErrorTemplateObject(eErrorKeys::INVALID_MESSAGE_ID);
                        } // invalid msg id
                    } else {
                        $this->m_objTemplate = $this->_getErrorTemplateObject(eErrorKeys::INVALID_MESSAGE_ID);
                    } // invalid msg id
                }
            } else {
                $this->m_objTemplate = $this->_getErrorTemplateObject(eErrorKeys::NOT_AUTHORIZED);
            } // forbidden
        } else {
            $this->m_objTemplate = $this->_getErrorTemplateObject(eErrorKeys::BOARD_CLOSED);
        } // board closed
    }
}
