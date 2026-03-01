<?php
require_once(SRCDIR . '/Controller/Admin/cAdminAction.php');
require_once(SRCDIR . '/Model/cTemplate.php');
require_once(SRCDIR . '/Enum/eUser.php');
/**
 * Displays the form for user activation
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
*/
class cAdminActionActivateusersform extends cAdminAction{

	/**
	 * perform the action
	 *
	 * @return void
	 */
	public function performAction(): void{

		$this->m_sOutput .= $this->_getHead();

		if(!$this->m_objConfig->useDirectRegistration()){

			$objTemplate = new cTemplate();
			$objTemplate->loadDataById(5);

			$this->m_sOutput .= "\n<script language=\"JavaScript\">\n";
			$this->m_sOutput .= "  function updtext(id,state)\n  {\n";
			$this->m_sOutput .= "  	document.forms[0].elements['r'+id].value=(state)?\"".str_replace(array("\n","\r","\t"),array("\\n","\\r","\\t"),htmlspecialchars($objTemplate->getMessage()))."\":\"\";\n";
			$this->m_sOutput .= "  }\n</script>\n";

			$this->m_sOutput .= "<div class=\"pxm-admin-card\"><div class=\"pxm-admin-card__header\">activate users</div><div class=\"pxm-admin-card__body\">\n";
			$this->m_sOutput .= "<form action=\"pxmboard.php\" method=\"post\" onsubmit=\"return confirm('activate / delete useres?')\">".$this->_getHiddenField("mode","admactivateusers");
			$this->m_sOutput .= "<table class=\"pxm-table\"><thead><tr><th>username</th><th>first name</th><th>last name</th>";
			$this->m_sOutput .= "<th>private mail</th><th>date of registration</th><th>act</th><th>del</th><th>reason</th></tr></thead>\n";

			if($objResultSet = cDBFactory::getInstance()->executeQuery("SELECT u_id,u_username,u_registrationtstmp,u_firstname,u_lastname,u_privatemail FROM pxm_user WHERE u_status=".UserStatus::NOT_ACTIVATED->value." ORDER BY u_registrationtstmp DESC")){
				$sDateFormat = $this->m_objConfig->getDateFormat();
				$iTimeOffset = $this->m_objConfig->getTimeOffset()*3600;
				$this->m_sOutput .= "<tbody>";
				while($objResultRow = $objResultSet->getNextResultRowObject()){
					$this->m_sOutput .= "<tr><td><a href=\"pxmboard.php?mode=admuserform&usrid=".$objResultRow->u_id."\">".htmlspecialchars($objResultRow->u_username)."</a></td><td>".htmlspecialchars($objResultRow->u_firstname)."</td><td>";
					$this->m_sOutput .= htmlspecialchars($objResultRow->u_lastname)."</td><td>";

					$sPrivateMail = htmlspecialchars($objResultRow->u_privatemail);
					$arrPrivMail = explode("@",$sPrivateMail);
					if(sizeof($arrPrivMail)>1){
						$this->m_sOutput .= $arrPrivMail[0]."@<a href=\"http://www.".$arrPrivMail[1]."\">".$arrPrivMail[1]."</a>";
					}
					else $this->m_sOutput .= $sPrivateMail;

					$this->m_sOutput .= "</td><td class=\"pxm-table__num\">".(($objResultRow->u_registrationtstmp>0)?date($sDateFormat,($objResultRow->u_registrationtstmp+$iTimeOffset)):0)."</td>";
					$this->m_sOutput .= "<td>".$this->_getCheckboxField("act[]",$objResultRow->u_id,"",true);
					$this->m_sOutput .= "</td><td><input type=\"checkbox\" name=\"del[]\" value=\"".$objResultRow->u_id."\" onclick=\"updtext(".$objResultRow->u_id.",this.checked)\"></td><td>";
					$this->m_sOutput .= "<textarea name=\""."r".$objResultRow->u_id."\" rows=\"1\" cols=\"25\"></textarea></td></tr>\n";
				}
				$objResultSet->freeResult();
				$this->m_sOutput .= "</tbody>";
				$this->m_sOutput .= "<tfoot><tr><td colspan=\"8\"><div class=\"pxm-btn-row\"><button type=\"submit\" class=\"pxm-btn pxm-btn--primary\">activate / delete</button></div></td></tr></tfoot>";
			}
			$this->m_sOutput .= "</table></form>";
			$this->m_sOutput .= "</div></div>\n";
		}
		else $this->m_sOutput .= $this->_getAlert('forbidden');

		$this->m_sOutput .= $this->_getFooter();
	}
}
?>