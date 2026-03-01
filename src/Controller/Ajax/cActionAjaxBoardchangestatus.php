<?php
require_once(SRCDIR . '/Controller/Ajax/cActionAjax.php');
require_once(SRCDIR . '/Model/cBoard.php');
require_once(SRCDIR . '/Enum/eBoardStatus.php');
/**
 * Ajax-Action: Change board status
 *
 * @author Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright Torsten Rentsch 2001 - 2026
 */
class cActionAjaxBoardchangestatus extends cActionAjax {

	/**
	 * Validate base permissions - requires authentication and admin rights
	 *
	 * @return bool true if admin, false otherwise
	 */
	public function validateBasePermissionsAndConditions(): bool {
		return $this->_requireAdminPermission();
	}

	/**
	 * Perform action - change board status via Ajax
	 *
	 * @return void
	 */
	public function performAction(): void {
		// Input-Validierung
		$iBoardId = $this->m_objInputHandler->getIntFormVar("boardid", true, true, true);
		if($iBoardId <= 0){
			$this->_setJsonError(eError::BOARD_ID_MISSING, 400);
			return;
		}

		$iNewStatus = $this->m_objInputHandler->getIntFormVar("status", true, true, true);

		// Validate status value
		try {
			$eNewStatus = BoardStatus::from($iNewStatus);
		} catch (ValueError $e) {
			$this->_setJsonError(eError::INVALID_MODE, 400);
			return;
		}

		// Load board
		$objBoard = new cBoard();
		if(!$objBoard->loadDataById($iBoardId)){
			$this->_setJsonError(eError::BOARD_ID_MISSING, 404);
			return;
		}

		// Change status
		if(!$objBoard->updateStatus($eNewStatus)){
			$this->_setJsonError(eError::COULD_NOT_UPDATE_DATA, 500);
			return;
		}

		// Success response
		$this->_setJsonSuccess(eSuccessMessage::BOARD_STATUS_CHANGED, [
			'status' => $eNewStatus->value,
			'label' => $eNewStatus->getLabel()
		]);
	}
}
?>
