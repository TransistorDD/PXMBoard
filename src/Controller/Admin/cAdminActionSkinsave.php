<?php
require_once(SRCDIR . '/Controller/Admin/cAdminAction.php');
require_once(SRCDIR . '/Model/cSkin.php');
/**
 * save a skin
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cAdminActionSkinsave extends cAdminAction{

	/**
	 * perform the action
	 *
	 * @return void
	 */
	public function performAction(): void{

		$this->m_sOutput .= $this->_getHead();

		$this->m_sOutput .= "<h4>edit skin configuration</h4>\n";

		$objSkin = new cSkin();

		if($objSkin->loadDataById($this->m_objInputHandler->getIntFormVar("id",true,true,true))){

			$objSkin->setName($this->m_objInputHandler->getStringFormVar("name","skinvalue",true,true,"trim"));
			$objSkin->setDirectory($this->m_objInputHandler->getStringFormVar("dir","skinvalue",true,true,"trim"));
			$objSkin->setAdditionalSkinValues($this->m_objInputHandler->getArrFormVar("additionalvalues",true,true,false,"trim","skinvalue"));

			if($objSkin->updateData()){
				$this->m_sOutput .= $this->_getAlert('skin saved', 'success');
			}
			else{
				$this->m_sOutput .= $this->_getAlert('could not update skin data');
			}
		}
		else $this->m_sOutput .= $this->_getAlert("couldn't find skin");

		$this->m_sOutput .= $this->_getFooter();
	}
}
?>