<?php

require_once(SRCDIR . '/Controller/Admin/cAdminAction.php');
require_once(SRCDIR . '/Model/cProfileConfig.php');
/**
 * add a field to the profile
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cAdminActionProfileadd extends cAdminAction
{
    /**
     * Validate permissions - requires admin rights and valid CSRF token.
     *
     * @return bool true if admin and CSRF valid, false otherwise
     */
    public function validateBasePermissionsAndConditions(): bool
    {
        return $this->_requireValidCsrfToken() && $this->_requireAdminPermission();
    }

    /**
     * perform the action
     *
     * @return void
     */
    public function performAction(): void
    {

        $this->m_sOutput .= $this->_getHead();

        $objProfileConfig = new cProfileConfig();

        if ($objProfileConfig->addSlot(
            $this->m_objInputHandler->getStringFormVar('name', 'dbattributename', true, true, 'trim'),
            $this->m_objInputHandler->getStringFormVar('type', 'character', true, true, 'trim'),
            $this->m_objInputHandler->getIntFormVar('length', true, true)
        )) {

            $this->m_sOutput .= $this->_getAlert('profile field added', 'success');
            $this->m_sOutput .= $this->_getAlert('you can adjust templates "userprofile", "userprofileform" and "userregistration" for all skins now!', 'warning');
        } else {
            $this->m_sOutput .= $this->_getAlert('could not add profile field');
        }

        $this->m_sOutput .= $this->_getFooter();
    }
}
