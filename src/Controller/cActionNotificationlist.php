<?php
require_once(SRCDIR . '/Controller/cAction.php');
require_once(SRCDIR . '/Model/cNotificationList.php');
require_once(SRCDIR . '/Enum/eNotification.php');
/**
 * list of notifications
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cActionNotificationlist extends cAction{

	/**
	 * Validate permissions for this action
	 *
	 * @return bool true if all permissions granted
	 */
	public function validateBasePermissionsAndConditions(): bool {
		return $this->_requireAuthentication();
	}

	/**
	 * perform the action
	 *
	 * @return void
	 */
	public function performAction(): void{

		$this->m_objTemplate = $this->_getTemplateObject("notificationlist");

		$objNotificationList = new cNotificationList();
		$objNotificationList->setUserId($this->getActiveUser()->getId());

		// Optional filter by status
		$sStatus = $this->m_objInputHandler->getStringFormVar("status","type",true,true);
		if($sStatus === "read" || $sStatus === "unread"){
			$objNotificationList->setStatus($sStatus);
		}

		$objNotificationList->loadData(
			$this->m_objInputHandler->getIntFormVar("page",true,true,true),
			20 // notifications per page
		);

		$arrNotifications = array();
		foreach($objNotificationList->getDataArray() as $arrNotification){
			$arrNotifications[] = array(
				"id" => $arrNotification["id"],
				"type" => $arrNotification["type"],
				"status" => $arrNotification["status"],
				"title" => $arrNotification["title"],
				"message" => $arrNotification["message"],
				"link" => $arrNotification["link"],
				"created_timestamp" => $arrNotification["created_timestamp"],
				"created_date" => date($this->m_objConfig->getDateFormat(),
					$arrNotification["created_timestamp"] + $this->m_objConfig->getTimeOffset()*3600),
				"is_unread" => $arrNotification["is_unread"]
			);
		}

		$this->m_objTemplate->addData($this->getContextDataArray(array(
			"previd" => $objNotificationList->getPrevPageId(),
			"nextid" => $objNotificationList->getNextPageId()
		)));
		$this->m_objTemplate->addData(array("notifications" => $arrNotifications));
	}
}
?>
