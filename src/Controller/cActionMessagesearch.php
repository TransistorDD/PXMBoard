<?php
require_once(SRCDIR . '/Controller/cAction.php');
require_once(SRCDIR . '/Model/cSearchProfile.php');
require_once(SRCDIR . '/Model/cSearchProfileList.php');
require_once(SRCDIR . '/Model/cMessageSearchList.php');
require_once(SRCDIR . '/Model/cBoardList.php');
require_once(SRCDIR . '/Parser/cParser.php');
require_once(SRCDIR . '/Enum/eError.php');
/**
 * search messages
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cActionMessagesearch extends cAction{

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

		$iIdBoard = 0;
		if($objActiveBoard = $this->getActiveBoard()){
			$iIdBoard = $objActiveBoard->getId();
		}

		$iIdUser = 0;
		$iLastOnline = 0;
		if($objActiveUser = $this->getActiveUser()){
			$iIdUser = $objActiveUser->getId();
			$iLastOnline = $objActiveUser->getLastOnlineTimestamp();
		}

		// init search data
		$objSearch = new cSearchProfile();
		if(!$objSearch->loadDataById($this->m_objInputHandler->getIntFormVar("searchid",true,true,true))){
			$objSearch->setIdUser($iIdUser);
			$objSearch->setSearchMessage($this->m_objInputHandler->getStringFormVar("smsg","searchstring",true,true,"trim"));
			$objSearch->setSearchUser($this->m_objInputHandler->getStringFormVar("susr","username",true,true,"trim"));
			$objSearch->setBoardIds($this->m_objInputHandler->getArrFormVar("sbrdid",true,true,true,"intval"));
			$objSearch->setSearchDays($this->m_objInputHandler->getIntFormVar("days",true,true,true));
			$objSearch->setTimestamp($this->m_objConfig->getAccessTimestamp());
		}

		// Read group_by_thread parameter
		$bGroupByThread = $this->m_objInputHandler->getIntFormVar("group_by_thread",true,true,true) == 1;

		$objSearchProfileList = new cSearchProfileList();

		if(strlen($objSearch->getSearchMessage())<1 && strlen($objSearch->getSearchUser())<1){

			// display the search form
			$this->_initSearchForm($iIdBoard,$objSearchProfileList);
		}
		else{
			// Check rate limiting (applies to all searches, including saved profiles)
			$sIpAddress = $this->m_objServerHandler->getRemoteAddr();
			$iCurrentTime = $this->m_objConfig->getAccessTimestamp();

			if(cSearchProfile::isRateLimitExceeded($sIpAddress, $iCurrentTime)){
				// Rate limit exceeded
				$this->_initSearchForm($iIdBoard,$objSearchProfileList);
				$this->m_objTemplate->addData(array("error"=>array("text" => eError::RATE_LIMIT_EXCEEDED->value)));
				return;
			}

			$objError = null;

			$this->m_objTemplate = $this->_getTemplateObject("messagelist");

			// messagelist
			$objMessageSearchList = new cMessageSearchList($objSearch,$this->m_objConfig->getTimeOffset()*3600,$this->m_objConfig->getDateFormat(),$iIdUser);

			// execute search
			$objMessageSearchList->loadData($this->m_objInputHandler->getIntFormVar("page",true,true,true),$this->m_objConfig->getMessageHeaderPerPage(),$bGroupByThread);

			if($objMessageSearchList->getItemCount()<=500) {
				// always insert a new profile into search table
				$objSearch->setId(0);
				$objSearch->setIpAddress($sIpAddress);
				$objSearch->insertData();
			}
			else {
				$objError = eError::RESULT_SET_TOO_LARGE;				// too many results
			}
			if(is_object($objError)){
				// display the search form
				$this->_initSearchForm($iIdBoard,$objSearchProfileList);
				$this->m_objTemplate->addData(array("error"=>array("text" => $objError->value)));
			}
			else{
				// display the result
				$this->m_objTemplate->addData($this->getContextDataArray(array("previd"		=>$objMessageSearchList->getPrevPageId(),
																		   		"nextid"		=>$objMessageSearchList->getNextPageId(),
																				"curid"		=>$objMessageSearchList->getCurPageId(),
																				"count"		=>$objMessageSearchList->getPageCount(),
																				"items"		=>$objMessageSearchList->getItemCount(),
																				"group_by_thread" => $bGroupByThread,
																				"searchprofile"=>$objSearch->getDataArray($this->m_objConfig->getTimeOffset(),
																				 										   $this->m_objConfig->getDateFormat()))));
				$this->m_objTemplate->addData(array("msg"=>$objMessageSearchList->getDataArray()));
			}
		}

		// installed boards
		$this->m_objTemplate->addData(array("boards"=>array("board"=>$this->_getBoardListArray())));
	}

	/**
	 * init the search form
	 *
	 * @return void
	 */
	private function _initSearchForm($iIdBoard,$objSearchProfileList){

		// load recent searchprofiles
		$objSearchProfileList->loadData();

		$this->m_objTemplate = $this->_getTemplateObject("messagesearch");
		$this->m_objTemplate->addData($this->getContextDataArray());

		$this->m_objTemplate->addData(array("searchprofiles"=>array("searchprofile"=>$objSearchProfileList->getDataArray($this->m_objConfig->getTimeOffset()*3600,
																																  $this->m_objConfig->getDateFormat()))));
	}
}
?>