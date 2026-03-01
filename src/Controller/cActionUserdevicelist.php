<?php
require_once(SRCDIR . '/Controller/cAction.php');
require_once(SRCDIR . '/Model/cUserLoginTicketList.php');
require_once(SRCDIR . '/Model/cSession.php');
/**
 * Display active login sessions / devices
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cActionUserdevicelist extends cAction{

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

		// Load tickets for user and convert to array data
		$arrTicketObjects = cUserLoginTicketList::getTicketsForUser($this->getActiveUser()->getId());
		$arrTickets = array();
		foreach($arrTicketObjects as $objTicket){
			$arrTickets[] = array(
				"id" => $objTicket->getId(),
				"token" => $objTicket->getToken(),
				"device_info" => $objTicket->getDeviceInfo(),
				"ipaddress" => $objTicket->getIpAddress(),
				"created_timestamp" => $objTicket->getCreatedTimestamp(),
				"last_used_timestamp" => $objTicket->getLastUsedTimestamp()
			);
		}

		// Get current ticket (this device)
		$sCurrentTicket = cSession::getCookieVar("ticket");

		// Template
		$this->m_objTemplate = $this->_getTemplateObject("userdevicelist");
		$this->m_objTemplate->addData(array(
			"tickets" => $arrTickets,
			"current_ticket" => $sCurrentTicket
		));
		$this->m_objTemplate->addData($this->getContextDataArray());
	}
}
?>
