<?php

require_once(SRCDIR . '/Controller/Ajax/cAjaxAction.php');
require_once(SRCDIR . '/Model/cUserLoginTicket.php');
require_once(SRCDIR . '/Model/cSession.php');
/**
 * Ajax-Action: Logout a specific device/session
 *
 * @author Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright Torsten Rentsch 2001 - 2026
 */
class cAjaxActionUserdevicelogout extends cAjaxAction
{
    /**
     * Validate base permissions - requires authentication only
     *
     * @return bool true if authenticated, false otherwise
     */
    public function validateBasePermissionsAndConditions(): bool
    {
        return $this->_requireValidCsrfToken() && $this->_requireAuthentication();
    }

    /**
     * Perform action - logout a specific device via Ajax
     *
     * @return void
     */
    public function performAction(): void
    {
        $objActiveUser = $this->getActiveUser();

        // Input-Validierung
        $iTicketId = $this->m_objInputHandler->getIntFormVar('ticketid', true, true, true);
        if ($iTicketId <= 0) {
            $this->_setJsonError(eErrorKeys::INVALID_MODE, 400);
            return;
        }

        // Load ticket
        $objTicket = new cUserLoginTicket();
        if (!$objTicket->loadDataById($iTicketId)) {
            $this->_setJsonError(eErrorKeys::INVALID_MODE, 404);
            return;
        }

        // Security check: Ticket belongs to current user
        if ($objTicket->getUserId() != $objActiveUser->getId()) {
            $this->_setJsonError(eErrorKeys::NOT_AUTHORIZED, 403);
            return;
        }

        // Check if this is the current device - if so, delete the cookie
        $sCurrentTicket = cSession::getCookieVar('ticket');
        $bIsCurrentDevice = false;
        if (!empty($sCurrentTicket) && $objTicket->getToken() == $sCurrentTicket) {
            cSession::setCookieVar('ticket', '', time() - 3600);
            $bIsCurrentDevice = true;
        }

        // Delete ticket
        $objTicket->deleteTicket();

        // Success response
        $this->_setJsonSuccess(eSuccessKeys::DEVICE_LOGGED_OUT, ['is_current_device' => $bIsCurrentDevice]);
    }
}
