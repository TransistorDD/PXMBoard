<?php

require_once(SRCDIR . '/Controller/Admin/cAdminAction.php');
/**
 * displays the intro page for the admintool
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cAdminActionIntro extends cAdminAction
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

        $this->m_sOutput .= '<div class="pxm-admin-card"><div class="pxm-admin-card__header">PXMBoard Admin</div><div class="pxm-admin-card__body"><p>Select a function from the sidebar.</p></div></div>';

        $this->m_sOutput .= $this->_getFooter();
    }
}
