<?php

namespace PXMBoard\Controller\Ajax;

use PXMBoard\Enum\eErrorKeys;
use PXMBoard\Enum\eSuccessKeys;
use PXMBoard\Model\cThread;

/**
 * Ajax-Action: Delete a message tree (automatically handles thread vs subthread)
 *
 * @author Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright Torsten Rentsch 2001 - 2026
 */
class cAjaxActionMessagetreedelete extends cAjaxAction
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
     * Perform action - delete a message tree via Ajax
     * Automatically detects if it's a root message (delete thread) or subthread
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
            $this->_setJsonError(eErrorKeys::INVALID_MESSAGE_ID, 400);
            return;
        }

        $iThreadId = $this->m_objInputHandler->getIntFormVar('thrdid', false, true);
        if ($iThreadId <= 0) {
            $this->_setJsonError(eErrorKeys::INVALID_THREAD_ID, 400);
            return;
        }

        // Load thread directly (thread ID is provided by frontend)
        $objThread = new cThread();
        if (!$objThread->loadDataById($iThreadId, $iBoardId)) {
            $this->_setJsonError(eErrorKeys::INVALID_THREAD_ID, 404);
            return;
        }

        // Delete message tree (automatically handles thread vs subthread)
        if (!$objThread->deleteMessageTree($iMessageId)) {
            $this->_setJsonError(eErrorKeys::COULD_NOT_DELETE_DATA, 500);
            return;
        }

        // Success response
        $this->_setJsonSuccess(eSuccessKeys::MESSAGE_TREE_DELETED);
    }
}
