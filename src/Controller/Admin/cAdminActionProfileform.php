<?php
require_once(SRCDIR . '/Controller/Admin/cAdminAction.php');
require_once(SRCDIR . '/Model/cProfileConfig.php');
/**
 * displays the profile edit form
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cAdminActionProfileform extends cAdminAction{

	/**
	 * perform the action
	 *
	 * @return void
	 */
	public function performAction(): void{

		$this->m_sOutput .= $this->_getHead();

		$this->m_sOutput .= $this->_getAlert("Table structure will be altered! Backup data before using this tool!!!", "warning");

		$this->m_sOutput .= "<div class=\"pxm-admin-card\"><div class=\"pxm-admin-card__header\">delete profile fields</div><div class=\"pxm-admin-card__body\">\n";
		$this->m_sOutput .= "<form action=\"pxmboard.php\" method=\"post\" onsubmit=\"return confirm('delete profile fields?')\">\n";
		$this->m_sOutput .= $this->_getHiddenCsrfField();
		$this->m_sOutput .= "<input type=\"hidden\" name=\"mode\" value=\"admprofiledelete\">";
		$this->m_sOutput .= "<table class=\"pxm-table\"><thead><tr><th>name</th><th>type</th><th>length</th><th>del</th></tr></thead>\n";
		$this->m_sOutput .= "<tbody>";

		$objProfileConfig = new cProfileConfig();

		foreach($objProfileConfig->getSlotList() as $sKey=>$arrVal){
			$this->m_sOutput .= "<tr><td>".htmlspecialchars($sKey)."</td><td>";
			switch($arrVal[0]){
				case "i"	:	$this->m_sOutput .= "integer";
								break;
				case "s"	:	$this->m_sOutput .= "string";
								break;
				case "a"	:	$this->m_sOutput .= "area";
								break;
				default		:	$this->m_sOutput .= "???";
								break;
			}
			$this->m_sOutput .= "</td><td class=\"pxm-table__num\">".intval($arrVal[1])."</td><td><input type=\"checkbox\" name=\"del[]\" value=\"".htmlspecialchars($sKey)."\"></td></tr>\n";
		}

		$this->m_sOutput .= "</tbody><tfoot><tr><td colspan=\"4\"><div class=\"pxm-btn-row\"><button type=\"submit\" class=\"pxm-btn pxm-btn--danger\">delete</button></div></td></tr></tfoot>";
		$this->m_sOutput .= "</table></form>";
		$this->m_sOutput .= "</div></div>\n";

		$this->m_sOutput .= "<div class=\"pxm-admin-card\"><div class=\"pxm-admin-card__header\">add profile field</div><div class=\"pxm-admin-card__body\">\n";
		$this->m_sOutput .= "<form action=\"pxmboard.php\" method=\"post\" onsubmit=\"return confirm('add profile field?')\">\n";
		$this->m_sOutput .= $this->_getHiddenCsrfField();
		$this->m_sOutput .= "<input type=\"hidden\" name=\"mode\" value=\"admprofileadd\">";
		$this->m_sOutput .= "<div class=\"pxm-form-group\"><label>name</label><div class=\"pxm-field\"><input type=\"text\" name=\"name\" size=\"10\" maxlength=\"10\"></div></div>\n";
		$this->m_sOutput .= "<div class=\"pxm-form-group\"><label>type</label><div class=\"pxm-field\"><select name=\"type\" size=\"1\">\n";
		$this->m_sOutput .= "<option value=\"i\" selected>integer</option>\n<option value=\"s\">string</option>\n<option value=\"a\">area</option>\n";
		$this->m_sOutput .= "</select></div></div>\n";
		$this->m_sOutput .= "<div class=\"pxm-form-group\"><label>length</label><div class=\"pxm-field\"><input type=\"text\" name=\"length\" value=\"20\" size=\"3\"></div></div>\n";
		$this->m_sOutput .= "<div class=\"pxm-btn-row\"><button type=\"submit\" class=\"pxm-btn pxm-btn--primary\">add</button></div>";
		$this->m_sOutput .= "</form>";
		$this->m_sOutput .= "</div></div>\n";

		$this->m_sOutput .= $this->_getFooter();
	}
}
?>