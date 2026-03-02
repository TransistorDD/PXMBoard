<?php
require_once(SRCDIR . '/Controller/cAction.php');
require_once(SRCDIR . '/Model/cThreadList.php');
require_once(SRCDIR . '/Model/cBoardList.php');
require_once(SRCDIR . '/Parser/cParser.php');
/**
 * show the thread list for a board
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
 class cActionThreadlist extends cAction{

	/**
	 * Validate permissions for this action
	 *
	 * @return bool true if all permissions granted
	 */
	public function validateBasePermissionsAndConditions(): bool {
		return $this->_requireReadableBoard();
	}

	/**
	 * perform the action
	 *
	 * @return void
	 */
	public function performAction(): void{
		$objActiveBoard = $this->getActiveBoard();
		$objActiveUser = $this->getActiveUser();

		$this->m_objTemplate = $this->_getTemplateObject("threadlist");

		$sSortMode = $this->m_objInputHandler->getStringFormVar("sort","sortmode",true,true,"trim");
		if(!empty($sSortMode)){
			$this->setThreadListSortMode($sSortMode);
		}
		$iTimeSpan = $this->m_objInputHandler->getIntFormVar("date",true,true,true);
		if($iTimeSpan>0){
			$objActiveBoard->setThreadListTimeSpan($iTimeSpan);
		}

		$iUserId = $objActiveUser ? $objActiveUser->getId() : 0;
		$iLastOnline = $objActiveUser ? $objActiveUser->getLastOnlineTimestamp() : 0;

		$objThreadList = new cThreadList($objActiveBoard->getId(),$this->_getThreadListSortMode(),$this->m_objConfig->getAccessTimestamp() - $objActiveBoard->getThreadListTimeSpan()*86400 + $this->m_objConfig->getTimeOffset()*3600,$iUserId);
		$objThreadList->loadData($this->m_objInputHandler->getIntFormVar("page",true,true,true),$this->m_objConfig->getThreadsPerPage());

		$this->m_objTemplate->addData($this->getContextDataArray(array("previd"=>$objThreadList->getPrevPageId(),
																				"nextid"=>$objThreadList->getNextPageId())));
		$this->m_objTemplate->addData(array("thread"=>$objThreadList->getDataArray($this->m_objConfig->getTimeOffset()*3600,
																						$this->m_objConfig->getDateFormat(),
																						$iLastOnline)));
	}

	/**
	 * Get thread list sort mode
	 *
	 * @return string sort mode
	 */
	protected function _getThreadListSortMode(): string{
		$sSortMode = "";
		if(is_object($this->m_objActiveUser)){
			$sSortMode = $this->m_objActiveUser->getThreadListSortMode();
		}
		if(empty($sSortMode) && is_object($this->m_objActiveBoard)){
			$sSortMode = $this->m_objActiveBoard->getThreadListSortMode();
		}
		return $sSortMode;
	}

	/**
	 * Set thread list sort mode
	 *
	 * @param string $sSortMode sort mode
	 * @return void
	 */
	protected function setThreadListSortMode(string $sSortMode): void{

		if(is_object($this->m_objActiveUser)){
			$this->m_objActiveUser->setThreadListSortMode($sSortMode);
		}
		else if(is_object($this->m_objActiveBoard)){
			$this->m_objActiveBoard->setThreadListSortMode($sSortMode);
		}
	}

	/**
	 * Get context data array for templates
	 *
	 * @param array $arrAdditionalData additional data
	 * @return array context data
	 */
	protected function getContextDataArray(array $arrAdditionalData = array()):array {
		$arrContext = array("sort" => $this->_getThreadListSortMode());
		return PARENT::getContextDataArray(array_merge_recursive($arrContext,$arrAdditionalData));
	}
}
?>