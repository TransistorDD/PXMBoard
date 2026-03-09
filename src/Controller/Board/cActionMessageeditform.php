<?php

namespace PXMBoard\Controller\Board;

use PXMBoard\Enum\eErrorKeys;
use PXMBoard\Model\cBoardMessage;

/**
 * display the message edit form
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cActionMessageeditform extends cPublicAction
{
    /**
     * Validate permissions for this action
     *
     * @return bool true if all permissions granted
     */
    public function validateBasePermissionsAndConditions(): bool
    {
        return $this->_requireWritableBoard() && $this->_requireAuthentication();
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

            $iLastLogin = $this->_getLastLoginTimestamp();
            if ($objActiveUser->isEditAllowed()) {

                $iMessageId = $this->m_objInputHandler->getIntFormVar('msgid', true, true, true);

                if ($iMessageId > 0) {

                    $objBoardMessage = new cBoardMessage();
                    if ($objBoardMessage->loadDataById($iMessageId, $objActiveBoard->getId())) {

                        if ($bAdminMode || $objBoardMessage->isThreadActive()) {
                            if ($bAdminMode || ($objActiveUser->getId() == $objBoardMessage->getAuthorId())) {
                                if ($bAdminMode || $objBoardMessage->getReplyQuantity() < 1) {
                                    $this->m_objTemplate = $this->_getTemplateObject('messageeditform');

                                    // Determine message context (draft vs published)
                                    $bIsDraft = $objBoardMessage->isDraft();
                                    $bIsPublished = $objBoardMessage->isPublished();

                                    $this->m_objTemplate->addData($this->getContextDataArray([
                                        'is_draft' => $bIsDraft,
                                        'is_published' => $bIsPublished,
                                        'is_new_message' => false
                                    ]));

                                    $objPxmParser = $this->_getPredefinedPxmParser(true);

                                    $this->m_objTemplate->addData(['msg' => $objBoardMessage->getDataArray(
                                        $this->m_objConfig->getTimeOffset() * 3600,
                                        $this->m_objConfig->getDateFormat(),
                                        $iLastLogin,
                                        '',
                                        $objPxmParser
                                    )]);
                                } else {
                                    $this->m_objTemplate = $this->_getErrorTemplateObject(eErrorKeys::MESSAGE_HAS_REPLY);
                                }// replies exist
                            } else {
                                $this->m_objTemplate = $this->_getErrorTemplateObject(eErrorKeys::NOT_AUTHORIZED);
                            }	// forbidden
                        } else {
                            $this->m_objTemplate = $this->_getErrorTemplateObject(eErrorKeys::THREAD_CLOSED);
                        }			// thread closed
                    } else {
                        $this->m_objTemplate = $this->_getErrorTemplateObject(eErrorKeys::INVALID_MESSAGE_ID);
                    }	// invalid msg id
                } else {
                    $this->m_objTemplate = $this->_getErrorTemplateObject(eErrorKeys::INVALID_MESSAGE_ID);
                }		// invalid msg id
            } else {
                $this->m_objTemplate = $this->_getErrorTemplateObject(eErrorKeys::NOT_AUTHORIZED);
            }		// forbidden
        } else {
            $this->m_objTemplate = $this->_getErrorTemplateObject(eErrorKeys::BOARD_CLOSED);
        }			// board closed
    }
}
