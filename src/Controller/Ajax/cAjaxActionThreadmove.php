<?php

require_once(SRCDIR . '/Controller/Ajax/cAjaxAction.php');
require_once(SRCDIR . '/Model/cThread.php');
/**
 * AJAX action: Move a thread to another board
 *
 * Two-step protocol:
 *   - Without destid (or destid=0): returns JSON board list for selection dialog
 *   - With destid > 0: validates destination board, moves the thread, returns success/error
 *
 * Permission: Admin or moderator of the source board.
 *
 * @author Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright Torsten Rentsch 2001 - 2026
 */
class cAjaxActionThreadmove extends cAjaxAction
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
     * Perform action - return board list or move the thread
     *
     * @return void
     */
    public function performAction(): void
    {
        $objActiveBoard = $this->getActiveBoard();
        $iBoardId = $objActiveBoard->getId();

        $iThreadId = $this->m_objInputHandler->getIntFormVar('id', false, true);
        if ($iThreadId <= 0) {
            $this->_setJsonError(eErrorKeys::INVALID_THREAD_ID, 400);
            return;
        }

        $iDestId = $this->m_objInputHandler->getIntFormVar('destid', true, true);

        if ($iDestId <= 0) {
            // Step 1: return board list for selection
            $arrBoards = $this->_getBoardListArray();
            $arrResult = [];
            foreach ($arrBoards as $arrBoard) {
                $arrResult[] = ['id' => (int)$arrBoard['id'], 'name' => $arrBoard['name']];
            }
            $this->_setJsonResponse(['boards' => $arrResult]);
            return;
        }

        // Step 2: validate destination board and move thread
        $arrBoards = $this->_getBoardListArray();
        $bIsValidBoard = false;
        foreach ($arrBoards as $arrBoard) {
            if ((int)$arrBoard['id'] === $iDestId) {
                $bIsValidBoard = true;
                break;
            }
        }

        if (!$bIsValidBoard) {
            $this->_setJsonError(eErrorKeys::INVALID_BOARD_ID, 400);
            return;
        }

        $objThread = new cThread();
        if (!$objThread->loadDataById($iThreadId, $iBoardId) || !$objThread->moveThread($iDestId)) {
            $this->_setJsonError(eErrorKeys::MESSAGE_MOVE_ERROR, 500);
            return;
        }

        $this->_setJsonSuccess(eSuccessKeys::THREAD_MOVED, ['destBoardId' => $iDestId]);
    }
}
