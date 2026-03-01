<?php
require_once(SRCDIR . '/Model/cScrollList.php');
/**
 * user search list handling
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cUserSearchList extends cScrollList{

	protected string $m_sUserName;			// username

	/**
	 * Constructor
	 *
	 * @param string $sUserName username
	 * @return void
	 */
	public function __construct($sUserName){

		$this->m_sUserName = $sUserName;

		parent::__construct();
	}

	/**
	 * get the query
	 *
	 * @return string query
	 */
	protected function _getQuery(){
		return "SELECT u_id,u_username,u_highlight,u_status FROM pxm_user WHERE u_username LIKE'".addslashes($this->m_sUserName)."%' ORDER BY u_username ASC";
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
}
?>