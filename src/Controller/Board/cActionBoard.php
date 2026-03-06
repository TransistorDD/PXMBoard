<?php

namespace PXMBoard\Controller\Board;

/**
 * display frameset for a board
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cActionBoard extends cPublicAction
{
    /**
     * Validate permissions for this action
     *
     * @return bool true if all permissions granted
     */
    public function validateBasePermissionsAndConditions(): bool
    {
        return $this->_requireBoard();
    }

    /**
     * perform the action
     *
     * @return void
     */
    public function performAction(): void
    {

        $this->m_objTemplate = $this->_getTemplateObject('board');

        $this->m_objTemplate->addData($this->getContextDataArray([
            'thrdid' => $this->m_objInputHandler->getIntFormVar('thrdid', true, true, true),
            'msgid'  => $this->m_objInputHandler->getIntFormVar('msgid', true, true, true)
        ]));
        $this->m_objTemplate->addData(['boards' => ['board' => $this->_getBoardListArray()]]);
    }
}
