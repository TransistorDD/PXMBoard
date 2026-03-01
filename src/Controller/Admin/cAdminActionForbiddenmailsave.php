<?php
require_once(SRCDIR . '/Controller/Admin/cAdminAction.php');
require_once(SRCDIR . '/Model/cForbiddenMailList.php');
/**
 * save the forbidden mail adr.
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cAdminActionForbiddenmailsave extends cAdminAction{

	/**
	 * perform the action
	 *
	 * @return void
	 */
	public function performAction(): void{

		$this->m_sOutput .= $this->_getHead();

		$this->m_sOutput .= "<h4>save forbidden mails</h4>\n";

		$iMailLength = $this->m_objInputHandler->getInputSize("email");
		$arrForbiddenMails = array();
		foreach(explode("\n",$this->m_objInputHandler->getStringFormVar("forbmail","",true,true,"trim")) as $sForbiddenMail){
			$iForbiddenMailLength = strlen($sForbiddenMail);
			if($iForbiddenMailLength>0 && $iForbiddenMailLength<=$iMailLength){
				$arrForbiddenMails[] = $sForbiddenMail;
			}
		}

		$objForbiddenMail = new cForbiddenMailList();
		if($objForbiddenMail->updateList($arrForbiddenMails)){
			$this->m_sOutput .= $this->_getAlert('forbidden mail adr. saved', 'success');
		}
		else{
			$this->m_sOutput .= $this->_getAlert('could not save forbidden mail adr. data');
		}
		$this->m_sOutput .= $this->_getFooter();
	}
}
?>