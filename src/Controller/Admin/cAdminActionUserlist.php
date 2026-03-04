<?php

require_once(SRCDIR . '/Controller/Admin/cAdminAction.php');
require_once(SRCDIR . '/Model/cUserAdminList.php');
require_once(SRCDIR . '/Enum/eUser.php');
/**
 * displays a list of users
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cAdminActionUserlist extends cAdminAction
{
    /**
     * Validate permissions - requires admin rights.
     *
     * @return bool true if user is admin, false otherwise
     */
    public function validateBasePermissionsAndConditions(): bool
    {
        return $this->_requireAdminPermission();
    }

    /**
     * perform the action
     *
     * @return void
     */
    public function performAction(): void
    {


        $this->m_sOutput .= $this->_getHead();

        $arrUserStates = UserStatus::getAll();

        $iUserStateFilter = $this->m_objInputHandler->getIntFormVar('filter', true, true, true);
        $sSortMode = $this->m_objInputHandler->getStringFormVar('sort', 'sortmode', true, true, 'trim');
        if ($this->m_objInputHandler->getStringFormVar('direction', 'sortdirection', true, true, 'trim') == 'ASC') {
            $sSortDirection = 'ASC';
            $sNewSortDirection = 'DESC';
        } else {
            $sSortDirection = 'DESC';
            $sNewSortDirection = 'ASC';
        }

        if (!in_array($iUserStateFilter, array_keys($arrUserStates))) {
            $iUserStateFilter = 0;
        }

        $this->m_sOutput .= '<table class="pxm-table"><thead><tr><th>username</th>';

        // sort modes
        $sSortAttribute = '';
        if ($sSortMode == 'regi') {
            $this->m_sOutput .= "<th><a href=\"pxmboard.php?mode=admuserlist&sort=regi&direction=$sNewSortDirection&filter=$iUserStateFilter\">date of registration <img src=\"images/admintool/".strtolower($sNewSortDirection).'.gif"></a></th>';
            $sSortAttribute = 'u_registrationtstmp';
        } else {
            $this->m_sOutput .= "<th><a href=\"pxmboard.php?mode=admuserlist&sort=regi&direction=$sSortDirection&filter=$iUserStateFilter\">date of registration</a></th>";
        }
        if ($sSortMode == 'profile') {
            $this->m_sOutput .= "<th><a href=\"pxmboard.php?mode=admuserlist&sort=profile&direction=$sNewSortDirection&filter=$iUserStateFilter\">last profile change <img src=\"images/admintool/".strtolower($sNewSortDirection).'.gif"></a></th>';
            $sSortAttribute = 'u_profilechangedtstmp';
        } else {
            $this->m_sOutput .= "<th><a href=\"pxmboard.php?mode=admuserlist&sort=profile&direction=$sSortDirection&filter=$iUserStateFilter\">last profile change</a></th>";
        }
        if ($sSortMode == 'online') {
            $this->m_sOutput .= "<th><a href=\"pxmboard.php?mode=admuserlist&sort=online&direction=$sNewSortDirection&filter=$iUserStateFilter\">last online <img src=\"images/admintool/".strtolower($sNewSortDirection).'.gif"></a></th>';
            $sSortAttribute = 'u_lastonlinetstmp';
        } else {
            $this->m_sOutput .= "<th><a href=\"pxmboard.php?mode=admuserlist&sort=online&direction=$sSortDirection&filter=$iUserStateFilter\">last online</a></th>";
        }

        if ($sSortMode == 'posts') {
            $this->m_sOutput .= "<th><a href=\"pxmboard.php?mode=admuserlist&sort=posts&direction=$sNewSortDirection&filter=$iUserStateFilter\">posts <img src=\"images/admintool/".strtolower($sNewSortDirection).'.gif"></a></th>';
            $sSortAttribute = 'u_msgquantity';
        } else {
            $this->m_sOutput .= "<th><a href=\"pxmboard.php?mode=admuserlist&sort=posts&direction=$sSortDirection&filter=$iUserStateFilter\">posts</a></th>";
        }

        // filter
        $this->m_sOutput .= '<th><form action="pxmboard.php" method="get">';
        $this->m_sOutput .= $this->_getHiddenField('mode', 'admuserlist').$this->_getHiddenField('sort', $sSortMode).$this->_getHiddenField('direction', $sSortDirection);
        $this->m_sOutput .= '<select name="filter" size="1" onchange="this.form.submit();">';
        $this->m_sOutput .= '<option value="-1">state</option>';
        foreach ($arrUserStates as $iKey => $sVal) {
            $this->m_sOutput .= '<option value="'.$iKey.(($iUserStateFilter == $iKey) ? '" selected>' : '">').htmlspecialchars($sVal).'</option>';
        }
        $this->m_sOutput .= "</select></form></th></tr></thead>\n";

        // userlist
        $objUserAdminList = new cUserAdminList($iUserStateFilter, $sSortAttribute, $sSortDirection);
        $objUserAdminList->loadData($this->m_objInputHandler->getIntFormVar('page', true, true, true), $this->m_objConfig->getUserPerPage());

        $sDateFormat = $this->m_objConfig->getDateFormat();
        $iTimeOffset = $this->m_objConfig->getTimeOffset() * 3600;
        $this->m_sOutput .= '<tbody>';
        foreach ($objUserAdminList->getDataArray() as $objUser) {
            $this->m_sOutput .= '<tr><td><a href="pxmboard.php?mode=admuserform&usrid='.$objUser->getId().'">';
            $this->m_sOutput .= htmlspecialchars($objUser->getUserName()).'</a></td>';
            $this->m_sOutput .= '<td class="pxm-table__num">'.(($objUser->getRegistrationTimestamp() > 0) ? date($sDateFormat, ($objUser->getRegistrationTimestamp() + $iTimeOffset)) : 0).'</td>';
            $this->m_sOutput .= '<td class="pxm-table__num">'.(($objUser->getLastUpdateTimestamp() > 0) ? date($sDateFormat, ($objUser->getLastUpdateTimestamp() + $iTimeOffset)) : 0).'</td>';
            $this->m_sOutput .= '<td class="pxm-table__num">'.(($objUser->getLastOnlineTimestamp() > 0) ? date($sDateFormat, ($objUser->getLastOnlineTimestamp() + $iTimeOffset)) : 0).'</td>';
            $this->m_sOutput .= '<td class="pxm-table__num">'.$objUser->getMessageQuantity().'</td>';
            if (in_array($objUser->getStatus()->value, array_keys($arrUserStates))) {
                $this->m_sOutput .= '<td class="pxm-table__num">'.htmlspecialchars($arrUserStates[$objUser->getStatus()->value])."</td></tr>\n";
            } else {
                $this->m_sOutput .= '<td class="pxm-table__num">'.$objUser->getStatus()->value." ???</td></tr>\n";
            }
        }
        $this->m_sOutput .= '</tbody>';
        $this->m_sOutput .= '<tfoot><tr><td>';
        if ($objUserAdminList->getPrevPageId() > 0) {
            $this->m_sOutput .= '<a href="pxmboard.php?mode=admuserlist&sort='.urlencode($sSortMode)."&direction=$sSortDirection&filter=$iUserStateFilter&page=".$objUserAdminList->getPrevPageId().'">&lt;&lt;</a>';
        }
        $this->m_sOutput .= '</td><td colspan="4"></td><td>';
        if ($objUserAdminList->getNextPageId() > 0) {
            $this->m_sOutput .= '<a href="pxmboard.php?mode=admuserlist&sort='.urlencode($sSortMode)."&direction=$sSortDirection&filter=$iUserStateFilter&page=".$objUserAdminList->getNextPageId().'">&gt;&gt;</a>';
        }
        $this->m_sOutput .= "</td></tr></tfoot>\n</table>";

        if ($objResultSet = cDBFactory::getInstance()->executeQuery('SELECT u_status,count(*) AS usercount FROM pxm_user GROUP BY u_status ORDER BY u_status ASC')) {
            $this->m_sOutput .= "<table class=\"pxm-table\">\n";
            $this->m_sOutput .= '<thead><tr><th>user status</th><th>count</th></tr></thead>';
            $iUserCount = 0;
            $this->m_sOutput .= '<tbody>';
            while ($objResultRow = $objResultSet->getNextResultRowObject()) {
                if (isset($arrUserStates[intval($objResultRow->u_status)])) {
                    $iUserCount += intval($objResultRow->usercount);
                    $this->m_sOutput .= '<tr><td>'.htmlspecialchars($arrUserStates[intval($objResultRow->u_status)]).'</td><td>'.intval($objResultRow->usercount)."</td></tr>\n";
                }
            }
            $this->m_sOutput .= '</tbody>';
            $this->m_sOutput .= "<tfoot><tr><td>overall</td><td>$iUserCount</td></tr></tfoot>\n";
            $this->m_sOutput .= '</table>';
        }
        $this->m_sOutput .= $this->_getFooter();
    }
}
