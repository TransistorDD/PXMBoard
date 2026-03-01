<?php
require_once(SRCDIR . '/Controller/Ajax/cActionAjax.php');
require_once(SRCDIR . '/Model/cNotificationList.php');
/**
 * Ajax-Action: Mark all notifications as read
 *
 * @author Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright Torsten Rentsch 2001 - 2026
 */
class cActionAjaxNotificationmarkallread extends cActionAjax {

	/**
	 * Validate base permissions - requires authentication only
	 *
	 * @return bool true if authenticated, false otherwise
	 */
	public function validateBasePermissionsAndConditions(): bool {
		return $this->_requireAuthentication();
	}

	/**
	 * Perform action - mark all notifications as read via Ajax
	 *
	 * @return void
	 */
	public function performAction(): void {
		$objActiveUser = $this->getActiveUser();

		// Mark all as read
		cNotificationList::markAllAsRead($objActiveUser->getId());

		// Reload user to refresh counter in session
		$objActiveUser->loadDataById($objActiveUser->getId());

		// Success response
		$this->_setJsonSuccess(eSuccessMessage::ALL_NOTIFICATIONS_READ, ['count' => 0]);
	}
}
?>
