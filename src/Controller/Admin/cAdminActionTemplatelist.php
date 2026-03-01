<?php
require_once(SRCDIR . '/Controller/Admin/cAdminAction.php');
require_once(SRCDIR . '/Model/cTemplateList.php');
/**
 * displays the template configuration tool
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cAdminActionTemplatelist extends cAdminAction{

	/**
	 * perform the action
	 *
	 * @return void
	 */
	public function performAction(): void{

		$this->m_sOutput .= $this->_getHead();

		$this->m_sOutput .= "<h4>template configuration</h4>\n";
		$this->m_sOutput .= "<table class=\"pxm-table\">\n";
		$this->m_sOutput .= "<thead><tr><th>templates</th></tr></thead><tbody>\n";

		$objTemplateList = new cTemplateList();

		foreach($objTemplateList->getList() as $objTemplate){
			$this->m_sOutput .= "<tr><td><a href=\"pxmboard.php?mode=admtemplateform&id=".$objTemplate->getId()."\">".htmlspecialchars($objTemplate->getName())."</a></td></tr>\n";
		}
		$this->m_sOutput .= "</tbody></table>";

		$this->m_sOutput .= $this->_getFooter();
	}
}
?>
