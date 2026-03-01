<?php
require_once(SRCDIR . '/Controller/Admin/cAdminAction.php');
require_once(SRCDIR . '/Model/cTextreplacementList.php');
/**
 * save the textreplacement
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cAdminActionReplacementsave extends cAdminAction{

	/**
	 * perform the action
	 *
	 * @return void
	 */
	public function performAction(): void{

		$this->m_sOutput .= $this->_getHead();

		$this->m_sOutput .= "<h4>save textreplacement</h4>\n";

		$iAllowedLengthSearch = $this->m_objInputHandler->getInputSize("textsearch");
		$iAllowedLengthReplace = $this->m_objInputHandler->getInputSize("textreplace");
		$arrReplacements = array();
		foreach(explode("\n",$this->m_objInputHandler->getStringFormVar("repl","",true,true,"trim")) as $sVal){
			$arrReplacement = explode("=>",trim($sVal),2);
			if(sizeof($arrReplacement)>1){
				$iLengthSearch = strlen($arrReplacement[0]);
				$iLengthReplace = strlen($arrReplacement[1]);
				if($iLengthSearch>0 && $iLengthSearch<=$iAllowedLengthSearch && $iLengthReplace>0 && $iLengthReplace<=$iAllowedLengthReplace){
					$arrReplacements["search"][] = $arrReplacement[0];
					$arrReplacements["replace"][] = $arrReplacement[1];
				}
			}
		}

		$objTextreplacementList = new cTextreplacementList();

		if($objTextreplacementList->updateList($arrReplacements)){
			$this->m_sOutput .= $this->_getAlert('textreplacement saved', 'success');
		}
		else{
			$this->m_sOutput .= $this->_getAlert('could not save textreplacement data');
		}
		$this->m_sOutput .= $this->_getFooter();
	}
}
?>