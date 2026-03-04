<?php

require_once(SRCDIR . '/Controller/Admin/cAdminAction.php');
require_once(SRCDIR . '/Model/cTemplate.php');
/**
 * save template
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cAdminActionTemplatesave extends cAdminAction
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

        $objTemplate = new cTemplate();

        if ($objTemplate->loadDataById($this->m_objInputHandler->getIntFormVar('id', true, true, true))) {

            $this->m_sOutput .= "<h4>template configuration</h4>\n";

            $objTemplate->setMessage($this->m_objInputHandler->getStringFormVar('message', 'template', true, true, 'rtrim'));

            if ($objTemplate->updateData()) {
                $this->m_sOutput .= $this->_getAlert('template saved', 'success');
            } else {
                $this->m_sOutput .= $this->_getAlert('could not update template');
            }
        } else {
            $this->m_sOutput .= $this->_getAlert('template not found');
        }

        $this->m_sOutput .= $this->_getFooter();
    }
}
