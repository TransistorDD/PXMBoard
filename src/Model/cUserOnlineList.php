<?php
require_once(SRCDIR . '/Model/cScrollList.php');
/**
 * user online list handling
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cUserOnlineList extends cScrollList{

	protected bool $m_bAdminMode;			// query in adminmode?
	protected int $m_iOnlineTimestamp;	// online timestamp

	/**
	 * Constructor
	 *
	 * @param boolean $bAdminMode query in adminmode?
	 * @param integer $iOnlineTimestamp online timestamp
	 * @return void
	 */
	public function __construct($bAdminMode,$iOnlineTimestamp){

		parent::__construct();

		$this->m_bAdminMode = $bAdminMode?true:false;
		$this->m_iOnlineTimestamp = intval($iOnlineTimestamp);
	}

	/**
	 * get the query
	 *
	 * @return string query
	 */
	protected function _getQuery(){
		return "SELECT u_id,u_username,u_highlight,u_status FROM pxm_user WHERE ".($this->m_bAdminMode?"":"u_visible=1 AND ")."u_lastonlinetstmp>$this->m_iOnlineTimestamp ORDER BY u_username ASC";
	}

	/**
	 * initalize the member variables with the resultrow from the db
	 *
	 * @param object $objResultRow resultrow from db query
	 * @return boolean success / failure
	 */
	protected function _setDataFromDb($objResultRow){

		$this->m_arrResultList[] = array("id"		=>$objResultRow->u_id,
										 "username"	=>$objResultRow->u_username,
										 "highlight"=>$objResultRow->u_highlight,
										 "status"	=>$objResultRow->u_status);
		return true;
	}

	/**
	 * count visible / invisible
	 *
	 * @return array data
	 */
	public function getVisibilityDataArray(){


		$arrTmp = array("all"=>"0","visible"=>"0","invisible"=>"0");

		if($objResultSet = cDBFactory::getInstance()->executeQuery("SELECT u_visible,count(*) AS anz  FROM pxm_user WHERE u_lastonlinetstmp>$this->m_iOnlineTimestamp GROUP BY u_visible")){
			$iAll = 0;
			while($objResultRow = $objResultSet->getNextResultRowObject()){
				$iAll += intval($objResultRow->anz);
				if($objResultRow->u_visible){
					$arrTmp["visible"] = strval($objResultRow->anz);
				}
				else{
					$arrTmp["invisible"] = strval($objResultRow->anz);
				}
			}
			$arrTmp["all"] = strval($iAll);
		}
		return $arrTmp;
	}
}
?>