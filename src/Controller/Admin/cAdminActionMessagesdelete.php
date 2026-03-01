<?php
require_once(SRCDIR . '/Controller/Admin/cAdminAction.php');
require_once(SRCDIR . '/Model/cBoardList.php');
/**
 * delete messages in the selected boards for the selected timespan
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cAdminActionMessagesdelete extends cAdminAction{

	/**
	 * perform the action
	 *
	 * @return void
	 */
	public function performAction(): void{

		$this->m_sOutput .= $this->_getHead();

		$this->m_sOutput .= "<h4>messagetool</h4>\n";

		$arrBoardIds = $this->m_objInputHandler->getArrFormVar("brds",true,true,true,"intval");
		$iTimespan = $this->m_objInputHandler->getIntFormVar("date",true,true,true)*86400;

		if($iTimespan>0){
			if(sizeof($arrBoardIds)>0){

				$objBoardList = new cBoardList();
				$arrClosedBoardIds = $objBoardList->closeAllBoards(); 	// close boards
				if($objResultSet = cDBFactory::getInstance()->executeQuery("SELECT t_id FROM pxm_thread WHERE t_boardid IN (".implode(",",$arrBoardIds).") AND t_fixed=0 AND t_lastmsgtstmp<".($this->m_objConfig->getAccessTimestamp()-$iTimespan))){
					$arrThreadIds = array();
					while($objResultRow = $objResultSet->getNextResultRowObject()){
						$arrThreadIds[] = intval($objResultRow->t_id);
					}
					if(sizeof($arrThreadIds)>0){
						cDBFactory::getInstance()->executeQuery("DELETE FROM pxm_message WHERE m_threadid IN (".implode(",",$arrThreadIds).")");
						cDBFactory::getInstance()->executeQuery("DELETE FROM pxm_thread WHERE t_id IN (".implode(",",$arrThreadIds).")");
						$this->m_sOutput .= $this->_getAlert('threads and messages deleted', 'success');
					}
					else{
						$this->m_sOutput .= "<h2>no threads found</h2>";
					}
				}
				$objBoardList->openBoards($arrClosedBoardIds);			// open boards
			}

			if($this->m_objInputHandler->getIntFormVar("priv",true,true)>0){
				cDBFactory::getInstance()->executeQuery("DELETE FROM pxm_priv_message WHERE p_tstmp<".($this->m_objConfig->getAccessTimestamp()-$iTimespan));
				$this->m_sOutput .= $this->_getAlert('private messages deleted', 'success');
			}
		}
		else $this->m_sOutput .= $this->_getAlert('no timespan given');

		$this->m_sOutput .= $this->_getFooter();
	}
}
?>