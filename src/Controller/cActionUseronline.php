<?php
require_once(SRCDIR . '/Controller/cAction.php');
require_once(SRCDIR . '/Model/cUserOnlineList.php');
/**
 * which users are online at the moment?
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cActionUseronline extends cAction{

	/**
	 * Validate permissions for this action
	 *
	 * @return bool true if all permissions granted
	 */
	public function validateBasePermissionsAndConditions(): bool {
		return true;
	}

	/**
	 * perform the action
	 *
	 * @return void
	 */
	public function performAction(): void{

		if($objActiveBoard = $this->getActiveBoard()){
			$iIdBoard = $objActiveBoard->getId();
		}
		else{
			$iIdBoard = 0;
		}

		$iLastOnline = 0;
		$bIsAdmin = false;
		if($objActiveUser = $this->getActiveUser()){
			$iLastOnline = $objActiveUser->getLastOnlineTimestamp();
			$bIsAdmin = $objActiveUser->isAdmin();
		}

		$this->m_objTemplate = $this->_getTemplateObject("useronline");

		// userlist
		$objUserOnlineList = new cUserOnlineList($bIsAdmin,$this->m_objConfig->getAccessTimestamp() - $this->m_objConfig->getOnlineTime());
		$objUserOnlineList->loadData($this->m_objInputHandler->getIntFormVar("page",true,true,true),$this->m_objConfig->getUserPerPage());

		$this->m_objTemplate->addData($this->getContextDataArray(array("previd"	=>$objUserOnlineList->getPrevPageId(),
																   		"nextid"	=>$objUserOnlineList->getNextPageId())));

		$this->m_objTemplate->addData(array("user"=>$objUserOnlineList->getDataArray()));

		// load visibility count
		$this->m_objTemplate->addData(array("users"=>$objUserOnlineList->getVisibilityDataArray()));
	}
}
?>