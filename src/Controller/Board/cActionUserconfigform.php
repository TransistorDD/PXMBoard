<?php

namespace PXMBoard\Controller\Board;

use PXMBoard\Model\cSkinList;

/**
 * shows the user config form
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cActionUserconfigform extends cPublicAction
{
    /**
     * Validate permissions for this action
     *
     * @return bool true if all permissions granted
     */
    public function validateBasePermissionsAndConditions(): bool
    {
        return $this->_requireAuthentication();
    }

    /**
     * perform the action
     *
     * @return void
     */
    public function performAction(): void
    {

        $this->m_objTemplate = $this->_getTemplateObject('userconfigform');
        $this->m_objTemplate->addData($this->getContextDataArray());

        $arrSkinList = [];
        $objSkinList = new cSkinList();
        $arrAvailableTemplateEngines = $this->m_objConfig->getAvailableTemplateEngines();
        foreach ($objSkinList->getList() as $objSkin) {
            if (array_intersect($arrAvailableTemplateEngines, $objSkin->getSupportedTemplateEngines())) {
                $arrSkinList[] = ['id' => $objSkin->getId(),'name' => $objSkin->getName()];
            }
        }
        $this->m_objTemplate->addData(['skin' => $arrSkinList]);

        $this->m_objTemplate->addData(['user' => $this->getActiveUser()->getDataArray()]);
    }
}
