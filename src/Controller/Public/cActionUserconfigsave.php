<?php

require_once(SRCDIR . '/Controller/Public/cPublicAction.php');
require_once(SRCDIR . '/Model/cSkin.php');
require_once(SRCDIR . '/Enum/eSuccessKeys.php');
/**
 * saves a user configuration
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cActionUserconfigsave extends cPublicAction
{
    /**
     * Validate permissions for this action
     *
     * @return bool true if all permissions granted
     */
    public function validateBasePermissionsAndConditions(): bool
    {
        return $this->_requireValidCsrfToken() && $this->_requireAuthentication();
    }

    /**
     * perform the action
     *
     * @return void
     */
    public function performAction(): void
    {

        $objActiveUser = $this->getActiveUser();

        $objActiveUser->setIsVisible($this->m_objInputHandler->getIntFormVar('visible', true, true, true));
        $objSkin = new cSkin();
        if ($objSkin->loadDataById($this->m_objInputHandler->getIntFormVar('skinid', true, true, true))
            && array_intersect($this->m_objConfig->getAvailableTemplateEngines(), $objSkin->getSupportedTemplateEngines())) {
            $objActiveUser->setSkinId($objSkin->getId());
        }
        $objActiveUser->setThreadListSortMode($this->m_objInputHandler->getStringFormVar('sort', 'sortmode', true, true, 'trim'));
        $objActiveUser->setTimeOffset($this->m_objInputHandler->getIntFormVar('toff', true, true));
        $objActiveUser->setEmbedExternal($this->m_objInputHandler->getIntFormVar('embed_external', true, true, true));
        $objActiveUser->setPrivateMail($this->m_objInputHandler->getStringFormVar('email', 'email', true, true, 'trim'));
        $objActiveUser->setSendPrivateMessageNotification($this->m_objInputHandler->getIntFormVar('privnotification', true, true, true));

        if ($objActiveUser->updateData()) {
            $this->m_objTemplate = $this->_getTemplateObject('confirm');
            $this->m_objTemplate->addData($this->getContextDataArray());
            $this->m_objTemplate->addData(['message' => eSuccessKeys::USER_CONFIG_SAVED->t()]);
        } else {
            $this->m_objTemplate = $this->_getErrorTemplateObject(eErrorKeys::COULD_NOT_INSERT_DATA);
        }	// could not insert data
    }
}
