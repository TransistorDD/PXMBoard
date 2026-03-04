<?php

require_once(SRCDIR . '/Controller/Ajax/cAjaxAction.php');
require_once(SRCDIR . '/Model/cBoardMessage.php');
require_once(SRCDIR . '/Model/cThread.php');
/**
 * Ajax-Action: Extract a message subtree as a new thread
 *
 * @author Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright Torsten Rentsch 2001 - 2026
 */
class cAjaxActionMessagetreeextract extends cAjaxAction
{
    /**
     * Validate base permissions - requires authentication, board, and moderator rights
     *
     * @return bool true if all permissions granted, false otherwise
     */
    public function validateBasePermissionsAndConditions(): bool
    {
        return $this->_requireValidCsrfToken()
            && $this->_requireAuthentication()
            && $this->_requireBoard()
            && $this->_requireModeratorPermission();
    }

    /**
     * Perform action - extract a message subtree as a new thread via Ajax
     *
     * @return void
     */
    public function performAction(): void
    {
        $objActiveBoard = $this->getActiveBoard();
        $iBoardId = $objActiveBoard->getId();

        // Input-Validierung
        $iMessageId = $this->m_objInputHandler->getIntFormVar('msgid', false, true);
        if ($iMessageId <= 0) {
            $this->_setJsonError(eError::INVALID_MESSAGE_ID, 400);
            return;
        }

        // Business Logic - Load message
        $objBoardMessage = new cBoardMessage();
        if (!$objBoardMessage->loadDataById($iMessageId, $iBoardId)) {
            $this->_setJsonError(eError::INVALID_MESSAGE_ID, 404);
            return;
        }

        // Load thread
        $objThread = new cThread();
        if (!$objThread->loadDataById($objBoardMessage->getThreadId(), $iBoardId)) {
            $this->_setJsonError(eError::INVALID_THREAD_ID, 404);
            return;
        }

        // Extract subthread
        if (!$objThread->extractSubThread($objBoardMessage->getId())) {
            $this->_setJsonError(eError::COULD_NOT_INSERT_DATA, 500);
            return;
        }

        // Success response
        $this->_setJsonSuccess(eSuccessMessage::MESSAGE_TREE_EXTRACTED);
    }
}
