<?php
require_once(SRCDIR . '/Controller/cAction.php');
require_once(SRCDIR . '/Model/cBoardMessage.php');
require_once(SRCDIR . '/Enum/eError.php');
/**
 * move message subtree to different parent
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cActionMessagemove extends cAction{

	/**
	 * Validate permissions for this action
	 *
	 * @return bool true if all permissions granted
	 */
	public function validateBasePermissionsAndConditions(): bool {
		return $this->_requireModeratorPermission();
	}

	/**
	 * perform the action
	 *
	 * @return void
	 */
	public function performAction(): void{

		$objActiveBoard = $this->getActiveBoard();
		$iBoardId = $objActiveBoard->getId();

		// Get source and target message IDs
		$iSourceMessageId = $this->m_objInputHandler->getIntFormVar("sourcemsgid", true, true, true);
		$iTargetMessageId = $this->m_objInputHandler->getIntFormVar("targetmsgid", true, true, true);

		if($iSourceMessageId <= 0 || $iTargetMessageId <= 0){
			$this->m_objTemplate = $this->_getErrorTemplateObject(eError::INVALID_MESSAGE_ID); // invalid msg id
			return;
		}

		// Load source message
		$objSourceMessage = new cBoardMessage();
		if(!$objSourceMessage->loadDataById($iSourceMessageId, $iBoardId)){
			$this->m_objTemplate = $this->_getErrorTemplateObject(eError::INVALID_MESSAGE_ID); // invalid msg id
			return;
		}

		// Perform the move - all validation is done inside moveToParent()
		try {
			if($objSourceMessage->moveToParent($iTargetMessageId)){
				// Success: Redirect to moved message via template
				$this->m_objTemplate = $this->_getTemplateObject("redirect");
				$this->m_objTemplate->addData(array("redirect_url"=> "pxmboard.php?mode=message&brdid=".$iBoardId."&msgid=".$iSourceMessageId."#msg".$iSourceMessageId,
													"message"=> "Die Nachricht wurde erfolgreich verschoben."));
			}
			else {
				$this->m_objTemplate = $this->_getErrorTemplateObject(eError::COULD_NOT_DELETE_DATA); // could not update data
			}
		}
		catch(cSelfReferenceException $e){
			$this->m_objTemplate = $this->_getErrorTemplateObject(eError::CANNOT_MOVE_TO_SELF);
		}
		catch(cCircularReferenceException $e){
			$this->m_objTemplate = $this->_getErrorTemplateObject(eError::CANNOT_MOVE_TO_SUBTREE);
		}
		catch(cInvalidBoardException $e){
			$this->m_objTemplate = $this->_getErrorTemplateObject(eError::CANNOT_MOVE_ACROSS_BOARDS);
		}
		catch(cInvalidParentException $e){
			$this->m_objTemplate = $this->_getErrorTemplateObject(eError::INVALID_MESSAGE_ID); // invalid msg id
		}
		catch(cMessageMoveException $e){
			$this->m_objTemplate = $this->_getErrorTemplateObject(eError::MESSAGE_MOVE_ERROR);
		}
	}
}
?>
