<?php

namespace PXMBoard\Controller\Admin;

use PXMBoard\Model\cBoard;

/**
 * delete a board
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cAdminActionBoarddelete extends cAdminAction
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

        $iBoardId = $this->m_objInputHandler->getIntFormVar('id', true, true, true);

        $objBoard = new cBoard();
        if ($objBoard->loadDataById($iBoardId)) {
            if ($objBoard->deleteData()) {
                $this->m_sOutput .= $this->_getAlert('board deleted', 'success');
            } else {
                $this->m_sOutput .= $this->_getAlert('could not delete data');
            }
        } else {
            $this->m_sOutput .= $this->_getAlert('invalid boardid');
        }

        $this->m_sOutput .= $this->_getFooter();
    }
}
