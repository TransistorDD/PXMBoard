<?php
require_once(SRCDIR . '/Controller/Admin/cAdminAction.php');
require_once(SRCDIR . '/Model/cTemplate.php');
/**
 * displays the template form
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cAdminActionTemplateform extends cAdminAction{

	/**
	 * perform the action
	 *
	 * @return void
	 */
	public function performAction(): void{

		$this->m_sOutput .= $this->_getHead();

		$objTemplate = new cTemplate();

		if($objTemplate->loadDataById($this->m_objInputHandler->getIntFormVar("id",true,true,true))){

			$this->m_sOutput .= "<div class=\"pxm-admin-card\">\n<div class=\"pxm-admin-card__header\">template configuration</div>\n<div class=\"pxm-admin-card__body\">\n";
			$this->m_sOutput .= "<form action=\"pxmboard.php\" method=\"post\" onsubmit=\"return confirm('update configuration?')\">\n";
			$this->m_sOutput .= $this->_getHiddenField("mode","admtemplatesave").$this->_getHiddenField("id",(string)$objTemplate->getId());
			$this->m_sOutput .= "<h2>".htmlspecialchars($objTemplate->getName())."</h2>\n";
			$this->m_sOutput .= "<p>".nl2br(htmlspecialchars($objTemplate->getDescription()))."</p>\n";
			$this->m_sOutput .= "<textarea cols=\"50\" rows=\"15\" name=\"message\">";
			$this->m_sOutput .= htmlspecialchars($objTemplate->getMessage())."</textarea>\n";
			$this->m_sOutput .= "<div class=\"pxm-btn-row\">";
			$this->m_sOutput .= "<button type=\"submit\" class=\"pxm-btn pxm-btn--primary\">update data</button>&nbsp;";
			$this->m_sOutput .= "<input type=\"reset\" value=\"reset data\"></div>\n";
			$this->m_sOutput .= "</form>\n</div>\n</div>\n";
			$this->m_sOutput .= "note: ".$this->m_objInputHandler->getInputSize("template")." chars allowed\n";
		}
		else $this->m_sOutput .= $this->_getAlert('template not found');

		$this->m_sOutput .= $this->_getFooter();
	}
}
?>
