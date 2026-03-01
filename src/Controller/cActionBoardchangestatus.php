<?php
require_once(SRCDIR . '/Controller/cActionBoardlist.php');
require_once(SRCDIR . '/Model/cBoard.php');
require_once(SRCDIR . '/Enum/eBoardStatus.php');
/**
 * change the status of a board
 *
 * @deprecated Use cActionAjaxBoardchangestatus instead for full status control
 * This action now toggles between PUBLIC and CLOSED for backwards compatibility
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cActionBoardchangestatus extends cActionBoardlist{

	/**
	 * Validate permissions for this action
	 *
	 * @return bool true if all permissions granted
	 */
	public function validateBasePermissionsAndConditions(): bool {
		return $this->_requireAdminPermission();
	}

	/**
	 * perform the action
	 * Toggles between PUBLIC and CLOSED status (backwards compatibility)
	 *
	 * @return void
	 */
	public function performAction(): void{

		$objBoard = new cBoard();
		if($objBoard->loadDataById($this->m_objInputHandler->getIntFormVar("id",true,true,true))){
			// Toggle between PUBLIC and CLOSED (backwards compatibility)
			$eNewStatus = ($objBoard->getStatus() === BoardStatus::CLOSED) ? BoardStatus::PUBLIC : BoardStatus::CLOSED;
			$objBoard->updateStatus($eNewStatus);
		}

		cActionBoardlist::performAction();
	}
}
?>