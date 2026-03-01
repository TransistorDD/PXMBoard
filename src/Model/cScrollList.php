<?php
/**
 * scrolllist handling
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cScrollList{

	protected array $m_arrResultList;			// array containing listelements
	protected int $m_iPrevPageId;				// id of previous index page
	protected int $m_iCurPageId;				// id of current index page
	protected int $m_iNextPageId;				// id of next index page

	protected int $m_iItemsPerPage;				// items visible on one page
	protected int $m_iItemCount;				// item count

	/**
	 * Constructor
	 * 
	 * @return void
	 */
	public function __construct(){

		$this->m_arrResultList = array();
		$this->m_iPrevPageId = 0;
		$this->m_iCurPageId = 0;
		$this->m_iNextPageId = 0;

		$this->m_iItemsPerPage = 0;
		$this->m_iItemCount = 0;
	}

	/**
	 * get data from database
	 * 
	 * @param integer $iCurPageId page offset
	 * @param integer $iResultRowLimit quantity of entries that should be loaded
	 * @return boolean success / failure
	 */
	public function loadData($iCurPageId,$iResultRowLimit){

		$iCurPageId = intval($iCurPageId);
		$iResultRowLimit = intval($iResultRowLimit);

		$this->m_iItemsPerPage = $iResultRowLimit;
		$this->m_iCurPageId = $iCurPageId;
		if($iCurPageId>0){
			--$iCurPageId;
		}
		else{
			++$this->m_iCurPageId;
		}

		$this->_doPreQuery();

		$sQuery = $this->_getQuery();

		if(!empty($sQuery) && ($objResultSet = cDBFactory::getInstance()->executeQuery($sQuery,$iResultRowLimit+1,$iCurPageId*$iResultRowLimit))){

			$this->m_arrResultList = array();
			for($x = 0; $x<$iResultRowLimit; $x++){
				if(!($objResultRow = $objResultSet->getNextResultRowObject())){
					break;
				}
				else{
					$this->_setDataFromDb($objResultRow);
				}
			}

			$this->m_iPrevPageId = $iCurPageId;
			$this->m_iNextPageId = (($iResultRowLimit<$objResultSet->getAffectedRows())?($iCurPageId+2):0);

			$objResultSet->freeResult();
			unset($objResultSet);

			$this->_doPostQuery();

			return true;
		}
		$this->_doPostQuery();

		return false;
	}

	/**
	 * do the query initializaton stuff here
	 * 
	 * @return void
	 */
	protected function _doPreQuery(){
	}

	/**
	 * get the query
	 * 
	 * @return string query
	 */
	protected function _getQuery(){
		return "";
	}

	/**
	 * do the query shutdown stuff here
	 * 
	 * @return void
	 */
	protected function _doPostQuery(){
	}

	/**
	 * initalize the member variables with the resultrow from the db
	 * 
	 * @param object $objResultRow resultrow from db query
	 * @return boolean success / failure
	 */
	protected function _setDataFromDb($objResultRow){
		return true;
	}

	/**
	 * get the item count
	 *
	 * @return integer item count
	 */
	public function getItemCount(){
		return $this->m_iItemCount;
	}

	/**
	 * get the page count
	 *
	 * @return integer page count
	 */
	public function getPageCount(): int{
		if($this->m_iItemsPerPage>0){
			return intval(ceil($this->m_iItemCount / $this->m_iItemsPerPage));
		}
		return 0;	
	}

	/**
	 * get previous page id
	 * 
	 * @return integer previous page id
	 */
	public function getPrevPageId(){
		return $this->m_iPrevPageId;
	}

	/**
	 * get next page id
	 * 
	 * @return integer next page id
	 */
	public function getNextPageId(){
		return $this->m_iNextPageId;
	}

	/**
	 * get current page id
	 * 
	 * @return integer current page id
	 */
	public function getCurPageId(){
		return $this->m_iCurPageId;
	}

	/**
	 * get membervariables as array
	 * 
	 * @return array member variables
	 */
	public function getDataArray(){
		return $this->m_arrResultList;
	}
}
?>