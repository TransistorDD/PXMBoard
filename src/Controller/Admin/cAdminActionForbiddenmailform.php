<?php
require_once(SRCDIR . '/Controller/Admin/cAdminAction.php');
require_once(SRCDIR . '/Model/cForbiddenMailList.php');
/**
 * displays the forbidden mail edit form
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cAdminActionForbiddenmailform extends cAdminAction{

	/**
	 * perform the action
	 *
	 * @return void
	 */
	public function performAction(): void{

		$this->m_sOutput .= $this->_getHead();

		$this->m_sOutput .= "<div class=\"pxm-admin-card\">\n<div class=\"pxm-admin-card__header\">forbidden mail</div>\n<div class=\"pxm-admin-card__body\">\n";
		$this->m_sOutput .= "<form action=\"pxmboard.php\" method=\"post\" onsubmit=\"return confirm('update configuration?')\">\n";
		$this->m_sOutput .= "<input type=\"hidden\" name=\"mode\" value=\"admforbiddenmailsave\">\n";
		$this->m_sOutput .= "<p>mail<br>mail2</p><textarea cols=\"30\" rows=\"30\" name=\"forbmail\">";

		$objForbiddenMail = new cForbiddenMailList();

		$this->m_sOutput .= htmlspecialchars(implode("\n",$objForbiddenMail->getList()));

		$this->m_sOutput .= "</textarea>\n";
		$this->m_sOutput .= "<div class=\"pxm-btn-row\"><button type=\"submit\" class=\"pxm-btn pxm-btn--primary\">update data</button>&nbsp;<input type=\"reset\" value=\"reset data\"></div>\n";
		$this->m_sOutput .= "</form>\n</div>\n</div>";

		$this->m_sOutput .= $this->_getFooter();
	}
}
?>