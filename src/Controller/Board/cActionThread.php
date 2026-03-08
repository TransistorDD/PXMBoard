<?php

namespace PXMBoard\Controller\Board;

use PXMBoard\Model\cMessageReadTracker;
use PXMBoard\Model\cThread;

/**
 * display a thread
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cActionThread extends cPublicAction
{
    /**
     * Validate permissions for this action
     *
     * @return bool true if all permissions granted
     */
    public function validateBasePermissionsAndConditions(): bool
    {
        return $this->_requireReadableBoard();
    }

    /**
     * perform the action
     *
     * @return void
     */
    public function performAction(): void
    {
        $this->m_objTemplate = $this->_getTemplateObject('thread');
        $this->m_objTemplate->addData($this->getContextDataArray());

        $objThread = new cThread();
        if ($objThread->loadDataById($this->m_objInputHandler->getIntFormVar('thrdid', true, true, true), $this->getActiveBoard()->getId())) {

            // Get current user id for draft visibility
            $iCurrentUserId = 0;
            $iLastOnline = 0;
            if ($objActiveUser = $this->getActiveUser()) {
                $iCurrentUserId = $objActiveUser->getId();
                $iLastOnline = $objActiveUser->getLastOnlineTimestamp();
            }

            $this->m_objTemplate->addData(['thread' => $objThread->getDataArray(
                $this->m_objConfig->getTimeOffset() * 3600,
                $this->m_objConfig->getDateFormat(),
                $iLastOnline,
                $iCurrentUserId
            )]);

//            if ($iCurrentUserId > 0) {
//                cMessageReadTracker::markAsRead(
//                    $iCurrentUserId,
////                    $objThread->getId()
//                );
//            }
        }
    }
}
