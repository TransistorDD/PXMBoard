<?php

namespace PXMBoard\Controller\Ajax;

use PXMBoard\Enum\eErrorKeys;
use PXMBoard\Enum\eSuccessKeys;
use PXMBoard\Model\cNotification;

/**
 * Ajax-Action: Mark notification as read
 *
 * @author Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright Torsten Rentsch 2001 - 2026
 */
class cAjaxActionNotificationmarkread extends cAjaxAction
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
     * Perform action - mark notification as read via Ajax
     *
     * @return void
     */
    public function performAction(): void
    {
        $objActiveUser = $this->getActiveUser();

        // Input-Validierung
        $iNotificationId = $this->m_objInputHandler->getIntFormVar('nid', true, true, true);
        if ($iNotificationId <= 0) {
            $this->_setJsonError(eErrorKeys::INVALID_MODE, 400);
            return;
        }

        // Load notification
        $objNotification = new cNotification();
        if (!$objNotification->loadDataById($iNotificationId)) {
            $this->_setJsonError(eErrorKeys::INVALID_MODE, 404);
            return;
        }

        // Security check: notification belongs to current user
        if ($objNotification->getUserId() != $objActiveUser->getId()) {
            $this->_setJsonError(eErrorKeys::NOT_AUTHORIZED, 403);
            return;
        }

        // Mark as read
        $objNotification->markAsRead();

        // Reload user to refresh counter in session
        $objActiveUser->loadDataById($objActiveUser->getId());

        // Success response with link
        $sLink = $objNotification->getLink();
        $this->_setJsonSuccess(eSuccessKeys::NOTIFICATION_MARKED_READ, [
            'redirect_url' => !empty($sLink) ? $sLink : 'pxmboard.php?mode=notificationlist',
            'count' => $objActiveUser->getUnreadNotificationCount()
        ]);
    }
}
