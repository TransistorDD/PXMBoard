<?php

require_once(SRCDIR . '/Controller/Ajax/cAjaxAction.php');
require_once(SRCDIR . '/Model/cThread.php');
/**
 * Ajax-Action: Toggle thread fixed status
 *
 * @author Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright Torsten Rentsch 2001 - 2026
 */
class cAjaxActionThreadchangefixed extends cAjaxAction
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
     * Perform action - toggle thread fixed status via Ajax
     *
     * @return void
     */
    public function performAction(): void
    {
        $objActiveBoard = $this->getActiveBoard();
        $iBoardId = $objActiveBoard->getId();

        // Input-Validierung
        $iThreadId = $this->m_objInputHandler->getIntFormVar('id', true, true, true);
        if ($iThreadId <= 0) {
            $this->_setJsonError(eError::INVALID_THREAD_ID, 400);
            return;
        }

        // Load thread
        $objThread = new cThread();
        if (!$objThread->loadDataById($iThreadId, $iBoardId)) {
            $this->_setJsonError(eError::INVALID_THREAD_ID, 404);
            return;
        }

        // Toggle fixed status
        $bNewFixed = !$objThread->isFixed();
        $objThread->updateIsFixed($bNewFixed);

        // Success response
        $eMessage = $bNewFixed ? eSuccessMessage::THREAD_FIXED : eSuccessMessage::THREAD_UNFIXED;
        $this->_setJsonSuccess($eMessage, ['isFixed' => $bNewFixed]);
    }
}
