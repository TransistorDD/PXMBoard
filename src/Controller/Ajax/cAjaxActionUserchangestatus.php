<?php

namespace PXMBoard\Controller\Ajax;

use PXMBoard\Enum\eErrorKeys;
use PXMBoard\Enum\eSuccessKeys;
use PXMBoard\Enum\eUserStatus;
use PXMBoard\Model\cUserPermissions;

/**
 * Ajax-Action: Toggle user status (active <-> disabled)
 *
 * @author Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright Torsten Rentsch 2001 - 2026
 */
class cAjaxActionUserchangestatus extends cAjaxAction
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
     * Perform action - toggle user status via Ajax
     *
     * @return void
     */
    public function performAction(): void
    {
        $objActiveBoard = $this->getActiveBoard();

        // Input-Validierung
        $iUserId = $this->m_objInputHandler->getIntFormVar('usrid', true, true, true);
        if ($iUserId <= 0) {
            $this->_setJsonError(eErrorKeys::INVALID_USER_ID, 400);
            return;
        }

        // Load user
        $objUserPermission = new cUserPermissions();
        if (!$objUserPermission->loadDataById($iUserId)) {
            $this->_setJsonError(eErrorKeys::INVALID_USER_ID, 404);
            return;
        }

        // Security: Cannot disable admins
        if ($objUserPermission->isAdmin()) {
            $this->_setJsonError(eErrorKeys::NOT_AUTHORIZED, 403);
            return;
        }

        // Toggle status
        $newStatus = null;
        switch ($objUserPermission->getStatus()) {
            case eUserStatus::ACTIVE:
                $newStatus = eUserStatus::DISABLED;
                break;
            case eUserStatus::DISABLED:
                $newStatus = eUserStatus::ACTIVE;
                break;
            default:
                $this->_setJsonError(eErrorKeys::NOT_AUTHORIZED, 403);
                return;
        }

        $objUserPermission->setStatus($newStatus);
        $objUserPermission->updateData();

        // Success response
        $eMessage = $newStatus === eUserStatus::ACTIVE ? eSuccessKeys::USER_ACTIVATED : eSuccessKeys::USER_DEACTIVATED;
        $this->_setJsonSuccess($eMessage, ['status' => $newStatus->value]);
    }
}
