<?php
require_once(SRCDIR . '/Controller/Ajax/cActionAjax.php');
require_once(SRCDIR . '/Model/cBoardMessage.php');
/**
 * Ajax-Action: Delete a message
 *
 * @author Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright Torsten Rentsch 2001 - 2026
 */
class cActionAjaxMessagedelete extends cActionAjax {

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
	 * Perform action - delete a message via Ajax
	 *
	 * @return void
	 */
	public function performAction(): void {
		$objActiveBoard = $this->getActiveBoard();
		$iBoardId = $objActiveBoard->getId();

		// Input-Validierung
		$iMessageId = $this->m_objInputHandler->getIntFormVar("msgid", false, true);
		if($iMessageId <= 0){
			$this->_setJsonError(eError::INVALID_MESSAGE_ID, 400);
			return;
		}

		// Business Logic - Load message
		$objBoardMessage = new cBoardMessage();
		if(!$objBoardMessage->loadDataById($iMessageId, $iBoardId)){
			$this->_setJsonError(eError::INVALID_MESSAGE_ID, 404);
			return;
		}

		// Delete message
		if(!$objBoardMessage->deleteData()){
			$this->_setJsonError(eError::COULD_NOT_DELETE_DATA, 500);
			return;
		}

		// Success response
		$this->_setJsonSuccess(eSuccessMessage::MESSAGE_DELETED);
	}
}
?>
