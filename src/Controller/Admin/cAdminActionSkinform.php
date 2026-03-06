<?php

namespace PXMBoard\Controller\Admin;

use PXMBoard\Model\cSkin;

/**
 * displays the skin edit form
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cAdminActionSkinform extends cAdminAction
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

        $objSkin = new cSkin();

        if ($objSkin->loadDataById($this->m_objInputHandler->getIntFormVar('id', true, true, true))) {

            $this->m_sOutput .= '<form action="pxmboard.php" method="post">'.$this->_getHiddenField('mode', 'admskinsave').$this->_getHiddenField('id', (string)$objSkin->getId());
            $this->m_sOutput .= $this->_getHiddenCsrfField();

            $this->m_sOutput .= "<div class=\"pxm-admin-card\"><div class=\"pxm-admin-card__header\">general configuration</div><div class=\"pxm-admin-card__body\">\n";

            $this->m_sOutput .= '<div class="pxm-form-group"><label>supported template engines</label><div class="pxm-field">'.htmlspecialchars(implode(',', $objSkin->getSupportedTemplateEngines()))."</div></div>\n";
            $this->m_sOutput .= $this->_getTextField('name', 255, $objSkin->getName(), 'name');
            $this->m_sOutput .= $this->_getTextField('dir', 255, $objSkin->getDirectory(), 'directory');
            $this->m_sOutput .= "</div></div>\n";

            $this->m_sOutput .= "<div class=\"pxm-admin-card\"><div class=\"pxm-admin-card__header\">additional configuration</div><div class=\"pxm-admin-card__body\">\n";
            foreach ($objSkin->getAdditionalSkinValues() as $sKey => $sValue) {
                $this->m_sOutput .= $this->_getTextField('additionalvalues['.$sKey.']', 255, $sValue, $sKey);
            }
            $this->m_sOutput .= "</div></div>\n";

            $this->m_sOutput .= '<div class="pxm-btn-row"><button type="submit" class="pxm-btn pxm-btn--primary">update data</button> <button type="reset" class="pxm-btn">reset data</button></div>';
            $this->m_sOutput .= '</form>';
        } else {
            $this->m_sOutput .= $this->_getAlert("couldn't find skin");
        }

        $this->m_sOutput .= $this->_getFooter();
    }
}
