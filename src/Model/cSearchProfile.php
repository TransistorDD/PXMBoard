<?php
/**
 * searchprofile handling
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cSearchProfile{

	protected int $m_iId;							// search id
	protected int $m_iIdUser;						// who started the search?
	protected string $m_sSearchMessage;				// message search string
	protected string $m_sSearchUser;				// user search string
	protected array $m_arrBoardIds;					// search in this boards
	protected int $m_iSearchDays;					// timespan of the search (last x days)
	protected int $m_iSearchTimestamp;				// date of the search
	protected string $m_sIpAddress;					// IP address of the searcher

	/**
	 * Constructor
	 *
	 * @return void
	 */
	public function __construct(){
		$this->m_iId = 0;
		$this->m_iIdUser = 0;
		$this->m_sSearchMessage = "";
		$this->m_sSearchUser = "";
		$this->m_arrBoardIds = array();
		$this->m_iSearchDays = 0;
		$this->m_iSearchTimestamp = 0;
		$this->m_sIpAddress = "";
	}

	/**
	 * get data from database by search id
	 *
	 * @param integer $iSearchId search id
	 * @return boolean success / failure
	 */
	public function loadDataById($iSearchId){

		$bReturn = false;
		$iSearchId = intval($iSearchId);

		if($iSearchId>0){


			if($objResultSet = cDBFactory::getInstance()->executeQuery("SELECT se_id,se_userid,se_message,se_username,se_boardids,se_days,se_tstmp,se_ipaddress FROM pxm_search WHERE se_id=".$iSearchId)){
				if($objResultRow = $objResultSet->getNextResultRowObject()){
					$bReturn = $this->_setDataFromDb($objResultRow);
				}
				$objResultSet->freeResult();
				unset($objResultSet);
			}
		}
		return $bReturn;
	}

	/**
	 * initalize the member variables with the resultset from the db
	 *
	 * @param object $objResultRow resultrow from db query
	 * @return boolean success / failure
	 */
	private function _setDataFromDb($objResultRow){

		$this->m_iId = intval($objResultRow->se_id);
		$this->m_iIdUser = intval($objResultRow->se_userid);
		$this->m_sSearchMessage = $objResultRow->se_message;
		$this->m_sSearchUser = $objResultRow->se_username;
		$this->m_arrBoardIds = explode(",",$objResultRow->se_boardids);
		$this->m_iSearchDays = intval($objResultRow->se_days);
		$this->m_iSearchTimestamp = intval($objResultRow->se_tstmp);
		$this->m_sIpAddress = $objResultRow->se_ipaddress;

		return true;
	}

	/**
	 * insert new data into database
	 *
	 * @return boolean success / failure
	 */
	public function insertData(){


		if($objResultSet = cDBFactory::getInstance()->executeQuery("INSERT INTO pxm_search (se_userid,se_message,se_username,se_boardids,se_days,se_tstmp,se_ipaddress)".
													  " VALUES ($this->m_iIdUser,".
													  			cDBFactory::getInstance()->quote($this->m_sSearchMessage).",".
																cDBFactory::getInstance()->quote($this->m_sSearchUser).",".
																cDBFactory::getInstance()->quote(implode(",",$this->m_arrBoardIds)).",".
																$this->m_iSearchDays.",".
																$this->m_iSearchTimestamp.",".
																cDBFactory::getInstance()->quote($this->m_sIpAddress).")")){
			if($objResultSet->getAffectedRows()>0){
				$this->m_iId = intval(cDBFactory::getInstance()->getInsertId("pxm_search","se_id"));
			}
		}

		// delete searchqueries older than 30 days
		if(mt_rand(1,10) == 5){
			cDBFactory::getInstance()->executeQuery("DELETE FROM pxm_search WHERE se_tstmp<".($this->m_iSearchTimestamp-86400*30));
		}
		return true;
	}

	/**
	 * delete a user from the database
	 *
	 * @return boolean success / failure
	 */
	public function deleteData(){

		$bReturn = false;

		if($objResultSet = cDBFactory::getInstance()->executeQuery("DELETE FROM pxm_search WHERE se_id=".$this->m_iId)){
			if($objResultSet->getAffectedRows()>0){
				$bReturn = true;
			}
		}
		return $bReturn;
	}

	/**
	 * get id
	 *
	 * @return integer id
	 */
	public function getId(){
		return $this->m_iId;
	}

	/**
	 * set id
	 *
	 * @param integer $iId id
	 * @return void
	 */
	public function setId($iId){
		$this->m_iId = intval($iId);
	}

	/**
	 * get user id
	 *
	 * @return integer user id
	 */
	public function getIdUser(){
		return $this->m_iIdUser;
	}

	/**
	 * set user id
	 *
	 * @param integer $iIdUser user id
	 * @return void
	 */
	public function setIdUser($iIdUser){
		$this->m_iIdUser = intval($iIdUser);
	}

	/**
	 * get message search string
	 *
	 * @return string message search string
	 */
	public function getSearchMessage(){
		return $this->m_sSearchMessage;
	}

	/**
	 * set message search string
	 *
	 * @param string $sSearchMessage message search string
	 * @return void
	 */
	public function setSearchMessage($sSearchMessage){
		$this->m_sSearchMessage = $sSearchMessage;
	}

	/**
	 * get user search string
	 *
	 * @return string user search string
	 */
	public function getSearchUser(){
		return $this->m_sSearchUser;
	}

	/**
	 * set user search string
	 *
	 * @param string $sSearchUser user search string
	 * @return void
	 */
	public function setSearchUser($sSearchUser){
		$this->m_sSearchUser = $sSearchUser;
	}

	/**
	 * get the boards to be searched
	 *
	 * @return array the boards to be searched
	 */
	public function getBoardIds(){
		return $this->m_arrBoardIds;
	}

	/**
	 * set the boards to be searched
	 *
	 * @param array $arrBoardIds the boards to be searched
	 * @return void
	 */
	public function setBoardIds($arrBoardIds){
		$this->m_arrBoardIds = $arrBoardIds;
	}

	/**
	 * get the timespan of the search (last x days)
	 *
	 * @return integer timespan of the search (last x days)
	 */
	public function getSearchDays(){
		return $this->m_iSearchDays;
	}

	/**
	 * set the timespan of the search (last x days)
	 *
	 * @param integer $iSearchDays timespan of the search (last x days)
	 * @return void
	 */
	public function setSearchDays($iSearchDays){
		$this->m_iSearchDays = intval($iSearchDays);
	}

	/**
	 * get the date of the search
	 *
	 * @return integer the date of the search
	 */
	public function getTimestamp(){
		return $this->m_iSearchTimestamp;
	}

	/**
	 * set the date of the search
	 *
	 * @param integer $iSearchTimestamp the date of the search
	 * @return void
	 */
	public function setTimestamp($iSearchTimestamp){
		$this->m_iSearchTimestamp = intval($iSearchTimestamp);
	}

	/**
	 * get IP address
	 *
	 * @return string IP address
	 */
	public function getIpAddress(){
		return $this->m_sIpAddress;
	}

	/**
	 * set IP address
	 *
	 * @param string $sIpAddress IP address
	 * @return void
	 */
	public function setIpAddress($sIpAddress){
		$this->m_sIpAddress = $sIpAddress;
	}

	/**
	 * Check if rate limit is exceeded for given IP address
	 *
	 * @param string $sIpAddress IP address to check
	 * @param int $iCurrentTimestamp current timestamp
	 * @return bool true if rate limit is exceeded, false otherwise
	 */
	public static function isRateLimitExceeded($sIpAddress, $iCurrentTimestamp){
		$iOneMinuteAgo = $iCurrentTimestamp - 60;
		$objDb = cDBFactory::getInstance();
		$sQuery = "SELECT COUNT(*) as search_count FROM pxm_search WHERE se_ipaddress=".$objDb->quote($sIpAddress)." AND se_tstmp >= ".$iOneMinuteAgo;

		if($objResultSet = $objDb->executeQuery($sQuery)){
			if($objRow = $objResultSet->getNextResultRowObject()){
				$iSearchCount = intval($objRow->search_count);
				$objResultSet->freeResult();
				return $iSearchCount >= 5;
			}
			$objResultSet->freeResult();
		}
		return false;
	}

	/**
	 * get membervariables as array
	 *
	 * @param integer $iTimeOffset time offset in seconds
	 * @param string $sDateFormat php date format
	 * @return array member variables
	 */
	public function getDataArray($iTimeOffset,$sDateFormat){
		return array("id"			=>	$this->m_iId,
					 "userid"		=>	$this->m_iIdUser,
					 "searchstring"	=>	$this->m_sSearchMessage,
					 "username"		=>	$this->m_sSearchUser,
					 "days"			=>	$this->m_iSearchDays,
					 "date"			=>	(($this->m_iSearchTimestamp>0)?date($sDateFormat,($this->m_iSearchTimestamp+$iTimeOffset)):0));
	}
}
?>