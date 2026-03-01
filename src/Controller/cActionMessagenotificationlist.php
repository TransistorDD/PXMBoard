<?php
require_once(SRCDIR . '/Controller/cAction.php');
require_once(SRCDIR . '/Model/cMessageNotificationList.php');
/**
 * list of message notification subscriptions
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cActionMessagenotificationlist extends cAction{

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

		$this->m_objTemplate = $this->_getTemplateObject("messagenotificationlist");

		$objNotificationList = new cMessageNotificationList($this->getActiveUser()->getId(),$this->m_objConfig->getTimeOffset()*3600,$this->m_objConfig->getDateFormat());
		$objNotificationList->loadData($this->m_objInputHandler->getIntFormVar("page",true,true,true),$this->m_objConfig->getMessagesPerPage());

		$this->m_objTemplate->addData($this->getContextDataArray(array("previd"	=>$objNotificationList->getPrevPageId(),
																				"nextid"	=>$objNotificationList->getNextPageId())));
		$this->m_objTemplate->addData(array("notifications"=>$objNotificationList->getDataArray()));
	}
}
?>
