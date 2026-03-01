<?php
require_once(SRCDIR . '/Controller/Ajax/cActionAjax.php');
require_once(SRCDIR . '/Model/cBoardMessage.php');
/**
 * Ajax-Action: Toggle reply notification flag for a message
 *
 * @author Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright Torsten Rentsch 2001 - 2026
 */
class cActionAjaxMessagenotifyonreply extends cActionAjax {

	/**
	 * Validate base permissions - requires authentication and board
	 * Complex authorization (author OR moderator/admin) is checked in performAction
	 *
	 * @return bool true if authenticated and board exists, false otherwise
	 */
	public function validateBasePermissionsAndConditions(): bool {
		return $this->_requireAuthentication()
			&& $this->_requireBoard();
	}

	/**
	 * Perform action - toggle reply notification via Ajax
	 *
	 * @return void
	 */
	public function performAction(): void {
		$objActiveUser = $this->getActiveUser();
		$objActiveBoard = $this->getActiveBoard();

		// Input-Validierung
		$iMessageId = $this->m_objInputHandler->getIntFormVar("msgid", true, true, true);
		if($iMessageId <= 0){
			$this->_setJsonError(eError::INVALID_MESSAGE_ID, 400);
			return;
		}

		// Load message
		$objBoardMessage = new cBoardMessage();
		if(!$objBoardMessage->loadDataById($iMessageId, $objActiveBoard->getId())){
			$this->_setJsonError(eError::INVALID_MESSAGE_ID, 404);
			return;
		}

		// Permission-Check (Author, Admin oder Moderator)
		$bIsAuthor = ($objActiveUser->getId() == $objBoardMessage->getAuthorId());
		$bIsAdmin = $objActiveUser->isAdmin();
		$bIsModerator = $objActiveUser->isModerator($objActiveBoard->getId());

		if(!$bIsAuthor && !$bIsAdmin && !$bIsModerator){
			$this->_setJsonError(eError::NOT_AUTHORIZED, 403);
			return;
		}

		// Toggle notification
		$bNewNotify = !$objBoardMessage->shouldNotifyOnReply();
		if(!$objBoardMessage->updateNotifyOnReply($bNewNotify)){
			$this->_setJsonError(eError::COULD_NOT_DELETE_DATA, 500);
			return;
		}

		// Success response
		$eMessage = $bNewNotify ? eSuccessMessage::NOTIFICATION_ENABLED : eSuccessMessage::NOTIFICATION_DISABLED;
		$this->_setJsonSuccess($eMessage, ['active' => $bNewNotify]);
	}
}
?>
