<?php
require_once(SRCDIR . '/Controller/Ajax/cActionAjax.php');
require_once(SRCDIR . '/Model/cUserPermissions.php');
require_once(SRCDIR . '/Enum/eUser.php');
/**
 * Ajax-Action: Toggle user status (active <-> disabled)
 *
 * @author Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright Torsten Rentsch 2001 - 2026
 */
class cActionAjaxUserchangestatus extends cActionAjax {

	/**
	 * Validate base permissions - requires authentication, board, and moderator rights
	 *
	 * @return bool true if all permissions granted, false otherwise
	 */
	public function validateBasePermissionsAndConditions(): bool {
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
	public function performAction(): void {
		$objActiveBoard = $this->getActiveBoard();

		// Input-Validierung
		$iUserId = $this->m_objInputHandler->getIntFormVar("usrid", true, true, true);
		if($iUserId <= 0){
			$this->_setJsonError(eError::INVALID_USER_ID, 400);
			return;
		}

		// Load user
		$objUserPermission = new cUserPermissions();
		if(!$objUserPermission->loadDataById($iUserId)){
			$this->_setJsonError(eError::INVALID_USER_ID, 404);
			return;
		}

		// Security: Cannot disable admins
		if($objUserPermission->isAdmin()){
			$this->_setJsonError(eError::NOT_AUTHORIZED, 403);
			return;
		}

		// Toggle status
		$newStatus = null;
		switch($objUserPermission->getStatus()){
			case UserStatus::ACTIVE:
				$newStatus = UserStatus::DISABLED;
				break;
			case UserStatus::DISABLED:
				$newStatus = UserStatus::ACTIVE;
				break;
			default:
				$this->_setJsonError(eError::NOT_AUTHORIZED, 403);
				return;
		}

		$objUserPermission->setStatus($newStatus);
		$objUserPermission->updateData();

		// Success response
		$eMessage = $newStatus === UserStatus::ACTIVE ? eSuccessMessage::USER_ACTIVATED : eSuccessMessage::USER_DEACTIVATED;
		$this->_setJsonSuccess($eMessage, ['status' => $newStatus->value]);
	}
}
?>
