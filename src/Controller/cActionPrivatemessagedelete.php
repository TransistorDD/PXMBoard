<?php
require_once(SRCDIR . '/Controller/cActionPrivatemessagelist.php');
/**
 * delete private messages
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cActionPrivatemessagedelete extends cActionPrivatemessagelist{

	/**
	 * Validate permissions for this action
	 *
	 * @return bool true if all permissions granted
	 */
	public function validateBasePermissionsAndConditions(): bool {
		return $this->_requireValidCsrfToken() && $this->_requireAuthentication();
	}

	/**
	 * perform the action
	 *
	 * @return void
	 */
	public function performAction(): void{

		$objActiveUser = $this->getActiveUser();

		$bSuccess = false;

		$sType = $this->m_objInputHandler->getStringFormVar("type","type",true,true);

		if(($iMessageId = $this->m_objInputHandler->getIntFormVar("msgid",true,true)) > 0){
			require_once(SRCDIR . '/Model/cPrivateMessage.php');
			$objPrivateMessage = new cPrivateMessage();
			$objPrivateMessage->setAuthorId($objActiveUser->getId());
			$objPrivateMessage->setDestinationUserId($objActiveUser->getId());
			$objPrivateMessage->setId($iMessageId);
			if($objPrivateMessage->deleteData()){
				$bSuccess = true;
			}
		}
		else{
			if($sType === "inbox"){
				$objPrivateMessageList = new cPrivateInboxList($objActiveUser->getId());
			}
			else{
				$objPrivateMessageList = new cPrivateOutboxList($objActiveUser->getId());
			}
			if($objPrivateMessageList->deleteData()){
				$bSuccess = true;
			}
		}
		if($bSuccess){
			parent::performAction();
		}
		else{
			$this->m_objTemplate = $this->_getErrorTemplateObject(eError::COULD_NOT_DELETE_DATA);
		}
	}
}
?>
