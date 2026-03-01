<?php
require_once(SRCDIR . '/Controller/Admin/cAdminAction.php');
require_once(SRCDIR . '/Model/cBoardList.php');
require_once(SRCDIR . '/Parser/cParser.php');
/**
 * displays the message tool
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cAdminActionMessageform extends cAdminAction{

	/**
	 * perform the action
	 *
	 * @return void
	 */
	public function performAction(): void{

		$this->m_sOutput .= $this->_getHead();

		$arrBoards = $this->_getBoardListArray();

		$this->m_sOutput .= "<div class=\"pxm-admin-card\">\n<div class=\"pxm-admin-card__header\">delete threads / messages</div>\n<div class=\"pxm-admin-card__body\">\n";
		$this->m_sOutput .= "<form action=\"pxmboard.php\" method=\"post\" onsubmit=\"return confirm('delete messages?')\">\n";
		$this->m_sOutput .= "<input type=\"hidden\" name=\"mode\" value=\"admmessagesdelete\">\n";
		$this->m_sOutput .= "<p>delete threads &amp; messages in </p>";

		foreach ($arrBoards as $arrVal) {
			$this->m_sOutput .= "<input type=\"checkbox\" name=\"brds[]\" value=\"".$arrVal["id"]."\" checked> ".htmlspecialchars($arrVal["name"])."<br>\n";
		}

		$this->m_sOutput .= "<input type=\"checkbox\" name=\"priv\" value=\"1\" checked> private messages<br>\n";

		$this->m_sOutput .= "not in use for <input type=\"text\" name=\"date\" value=\"3650\" size=\"5\" class=\"pxm-input--inline\"> day(s)<br>\n";
		$this->m_sOutput .= "<div class=\"pxm-btn-row\"><button type=\"submit\" class=\"pxm-btn pxm-btn--primary\">delete</button></div>\n";
		$this->m_sOutput .= "</form>\n</div>\n</div>\n";
		$this->m_sOutput .= "<table class=\"pxm-table\">\n";
		$this->m_sOutput .= "<thead><tr><th>board</th><th>first message</th><th>last message</th><th>count</th><th>per day</th></tr></thead><tbody>";

		$sDateFormat = $this->m_objConfig->getDateFormat();
		$iTimeOffset = $this->m_objConfig->getTimeOffset()*3600;

		$iMsgCount = 0;
		$iAverage = 0;
		$iTmpAverage = 0;
		$iMsgFirst = -1;
		$iMsgLast = -1;

		// public messages
		if($objResultSet = cDBFactory::getInstance()->executeQuery("SELECT b_name,count(*) AS msgcount,min(m_tstmp) AS minmsg,max(m_tstmp) AS maxmsg FROM pxm_message,pxm_thread,pxm_board WHERE m_threadid=t_id AND t_boardid=b_id AND t_fixed=0 GROUP BY b_id ORDER BY b_name")){
			while($objResultRow = $objResultSet->getNextResultRowObject()){
				$iTmpMsgCount = intval($objResultRow->msgcount);
				$iTmpMsgFirst = intval($objResultRow->minmsg);
				$iTmpMsgLast = intval($objResultRow->maxmsg);
				$iTmpTimeSpan = $iTmpMsgLast-$iTmpMsgFirst;
				$iTmpAverage = (($iTmpTimeSpan>=86400)?round(($iTmpMsgCount*86400)/$iTmpTimeSpan,3):$iTmpMsgCount);
				$iAverage += $iTmpAverage;
				$iMsgCount += $iTmpMsgCount;
				if($iMsgFirst<0 || $iTmpMsgFirst<$iMsgFirst){
					$iMsgFirst = $iTmpMsgFirst;
				}
				if($iMsgLast<0 || $iTmpMsgLast>$iMsgLast){
					$iMsgLast = $iTmpMsgLast;
				}
				$this->m_sOutput .= "<tr><td>".htmlspecialchars($objResultRow->b_name)."</td>";
				$this->m_sOutput .= "<td>".(($iTmpMsgFirst>0)?date($sDateFormat,($iTmpMsgFirst+$iTimeOffset)):0)."</td>";
				$this->m_sOutput .= "<td>".(($iTmpMsgLast>0)?date($sDateFormat,($iTmpMsgLast+$iTimeOffset)):0)."</td>";
				$this->m_sOutput .= "<td>".$iTmpMsgCount."</td><td>".$iTmpAverage."</td></tr>\n";
			}
		}

		// private messages
		if($objResultSet = cDBFactory::getInstance()->executeQuery("SELECT count(*) AS msgcount,min(p_tstmp) AS minmsg,max(p_tstmp) AS maxmsg FROM pxm_priv_message")){
			if($objResultRow = $objResultSet->getNextResultRowObject()){
				$iTmpMsgCount = intval($objResultRow->msgcount);
				$iTmpMsgFirst = intval($objResultRow->minmsg);
				$iTmpMsgLast = intval($objResultRow->maxmsg);
				$iTmpTimeSpan = $iTmpMsgLast-$iTmpMsgFirst;
				$iTmpAverage = (($iTmpTimeSpan>=86400)?round(($iTmpMsgCount*86400)/$iTmpTimeSpan,3):$iTmpMsgCount);
				$iAverage += $iTmpAverage;
				$iMsgCount += $iTmpMsgCount;
				$this->m_sOutput .= "<tr class=\"pxm-table__special\"><td>private messages</td>";
				$this->m_sOutput .= "<td>".(($iTmpMsgFirst>0)?date($sDateFormat,($iTmpMsgFirst+$iTimeOffset)):0)."</td>";
				$this->m_sOutput .= "<td>".(($iTmpMsgLast>0)?date($sDateFormat,($iTmpMsgLast+$iTimeOffset)):0)."</td>";
				$this->m_sOutput .= "<td>".$iTmpMsgCount."</td><td>".$iTmpAverage."</td></tr>\n";
			}
		}
		$this->m_sOutput .= "<tr class=\"pxm-table__footer\"><td>overall</td>";
		$this->m_sOutput .= "<td>".(($iMsgFirst>0)?date($sDateFormat,($iMsgFirst+$iTimeOffset)):0)."</td>";
		$this->m_sOutput .= "<td>".(($iMsgLast>0)?date($sDateFormat,($iMsgLast+$iTimeOffset)):0)."</td>";
		$this->m_sOutput .= "<td>".$iMsgCount."</td><td>".$iAverage."</td></tr>\n";
		$this->m_sOutput .= "</tbody></table>\nnote: fixed threads are ignored\n";

		$this->m_sOutput .= $this->_getFooter();
	}
}
?>