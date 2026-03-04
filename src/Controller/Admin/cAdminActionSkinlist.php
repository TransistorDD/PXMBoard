<?php

require_once(SRCDIR . '/Controller/Admin/cAdminAction.php');
require_once(SRCDIR . '/Model/cSkinList.php');
/**
 * displays the list of skins
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cAdminActionSkinlist extends cAdminAction
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

        $this->m_sOutput .= "<h4>skin list</h4>\n";
        $this->m_sOutput .= '<table class="pxm-table"><thead><tr><th>name</th><th>type</th><th>edit</th></tr></thead><tbody>';

        $objSkinList = new cSkinList();

        foreach ($objSkinList->getList() as $objSkin) {
            $bAvailable = (bool)array_intersect($this->m_objConfig->getAvailableTemplateEngines(), $objSkin->getSupportedTemplateEngines());
            $sBadgeClass = $bAvailable ? 'pxm-badge pxm-badge--ok' : 'pxm-badge pxm-badge--warn';
            $this->m_sOutput .= '<tr><td>'.htmlspecialchars($objSkin->getName()).'</td>';
            $this->m_sOutput .= "<td><span class=\"$sBadgeClass\">".htmlspecialchars(implode(',', $objSkin->getSupportedTemplateEngines())).'</span></td>';
            $this->m_sOutput .= '<td><a href="pxmboard.php?mode=admskinform&id='.$objSkin->getId()."\">edit</a></td></tr>\n";
        }
        $this->m_sOutput .= '</tbody></table>';

        $this->m_sOutput .= $this->_getFooter();
    }
}
