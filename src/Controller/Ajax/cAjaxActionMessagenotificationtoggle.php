<?php

require_once(SRCDIR . '/Controller/Ajax/cAjaxAction.php');
require_once(SRCDIR . '/Model/cBoardMessage.php');
/**
 * AJAX endpoint for toggling message notification subscription
 *
 * Toggles the notification status for the current user on a specific message.
 * Returns the new active state after toggling.
 *
 * @author Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright Torsten Rentsch 2001 - 2026
 */
class cAjaxActionMessagenotificationtoggle extends cAjaxAction
{
    /**
     * Validate base permissions - requires authentication only
     * Board is loaded from request parameters in performAction
     *
     * @return bool true if authenticated, false otherwise
     */
    public function validateBasePermissionsAndConditions(): bool
    {
        return $this->_requireValidCsrfToken() && $this->_requireAuthentication();
    }

    /**
     * Toggle notification subscription for a message
     *
     * @return void
     */
    public function performAction(): void
    {
        $objActiveUser = $this->getActiveUser();

        // Get and validate message ID and board ID
        $iMessageId = $this->m_objInputHandler->getIntFormVar('msgid', false, true);
        if ($iMessageId <= 0) {
            $this->_setJsonResponse(['error' => 'Invalid message ID'], 400);
            return;
        }

        $iBoardId = $this->m_objInputHandler->getIntFormVar('brdid', false, true);
        if ($iBoardId <= 0) {
            $this->_setJsonResponse(['error' => 'Invalid board ID'], 400);
            return;
        }

        // Load message to verify it exists
        $objMessage = new cBoardMessage();
        if (!$objMessage->loadDataById($iMessageId, $iBoardId)) {
            $this->_setJsonResponse(['error' => 'Message not found'], 404);
            return;
        }

        // Get current notification status
        $iUserId = $objActiveUser->getId();
        $bCurrentlyActive = $objMessage->isNotificationActiveForUser($iUserId);

        // Toggle the status
        $bNewActive = !$bCurrentlyActive;
        $objMessage->setNotificationForUser($iUserId, $bNewActive);

        // Return new status
        $this->_setJsonResponse([
            'success' => true,
            'active' => $bNewActive
        ], 200);
    }
}
