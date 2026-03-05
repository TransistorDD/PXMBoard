<?php

require_once(SRCDIR . '/Controller/Admin/cAdminAction.php');
require_once(SRCDIR . '/Model/cBoard.php');
require_once(SRCDIR . '/Enum/eBoardStatus.php');
/**
 * save the board data
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cAdminActionBoardsave extends cAdminAction
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
            $bInsert = false;
        } else {
            $bInsert = true;
        }

        $objBoard->setName($this->m_objInputHandler->getStringFormVar('name', 'boardname', true, true, 'trim'));
        $objBoard->setDescription($this->m_objInputHandler->getStringFormVar('desc', 'boarddescription', true, true, 'trim'));

        // Board status - validate and set enum
        $iStatus = $this->m_objInputHandler->getIntFormVar('status', true, true, true);
        try {
            $eStatus = eBoardStatus::from($iStatus);
            $objBoard->setStatus($eStatus);
        } catch (ValueError $e) {
            // Invalid status, default to PUBLIC
            $objBoard->setStatus(eBoardStatus::PUBLIC);
        }

        $objBoard->setThreadListTimeSpan($this->m_objInputHandler->getIntFormVar('date', true, true, true));
        $objBoard->setThreadListSortMode($this->m_objInputHandler->getStringFormVar('sort', 'sortmode', true, true, 'trim'));
        $objBoard->setEmbedExternal($this->m_objInputHandler->getIntFormVar('embed_external', true, true, true));
        $objBoard->setDoTextReplacements($this->m_objInputHandler->getIntFormVar('repl', true, true, true));

        $objBoard->setModeratorsByUserName(explode("\n", $this->m_objInputHandler->getStringFormVar('mod', '', true, true, 'trim')));

        if ($bInsert) {
            if ($objBoard->insertData()) {
                $objBoard->updateModData();
                $this->m_sOutput .= $this->_getAlert('data saved', 'success');
            } else {
                $this->m_sOutput .= $this->_getAlert('could not insert data');
            }
        } else {
            if ($objBoard->updateData()) {
                $objBoard->updateModData();
                $this->m_sOutput .= $this->_getAlert('data saved', 'success');
            } else {
                $this->m_sOutput .= $this->_getAlert('could not update data');
            }
        }
        $this->m_sOutput .= $this->_getFooter();
    }
}
