<?php

namespace PXMBoard\Controller\Board;

use PXMBoard\Enum\eErrorKeys;
use PXMBoard\Model\cBoardMessage;
use PXMBoard\Model\cMessageReadTracker;

/**
 * display a message
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cActionMessage extends cPublicAction
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
        $objActiveBoard = $this->getActiveBoard();

        $iMessageId = $this->m_objInputHandler->getIntFormVar('msgid', true, true, true);

        if ($iMessageId > 0) {
            $objBoardMessage = new cBoardMessage();
            if ($objBoardMessage->loadDataById($iMessageId, $objActiveBoard->getId())) {

                // Track read status for logged-in users
                if (($objActiveUser = $this->getActiveUser()) && $objActiveUser->getId() > 0) {
                    cMessageReadTracker::markAsRead(
                        $objActiveUser->getId(),
                        $objBoardMessage->getId()
                    );
                }

                // Get read count
                $iReadCount = cMessageReadTracker::getReadCount($objBoardMessage->getId());

                $iLastOnlineTimestamp = 0;
                $bEditAllowed = false;
                if ($objActiveUser = $this->getActiveUser()) {
                    $iLastOnlineTimestamp = $objActiveUser->getLastOnlineTimestamp();
                    $bEditAllowed = ($objActiveUser->isEditAllowed() &&  $objBoardMessage->getAuthorId() == $objActiveUser->getId());
                }

                $this->m_objTemplate = $this->_getTemplateObject('message');
                $this->m_objTemplate->addData($this->getContextDataArray(['edit' => (int) $bEditAllowed]));

                $objActiveSkin = $this->getActiveSkin();

                // parse the message body
                $objPxmParser = $this->_getPredefinedPxmParser(true);

                // Get message data array and add read count
                $arrMessageData = $objBoardMessage->getDataArray(
                    $this->m_objConfig->getTimeOffset() * 3600,
                    $this->m_objConfig->getDateFormat(),
                    $iLastOnlineTimestamp,
                    '',
                    $objPxmParser
                );
                $arrMessageData['readcount'] = $iReadCount;

                // Add notification subscription status for logged-in users
                if ($objActiveUser && $objActiveUser->getId() > 0) {
                    $arrMessageData['notification_active'] = $objBoardMessage->isNotificationActiveForUser($objActiveUser->getId());
                } else {
                    $arrMessageData['notification_active'] = false;
                }

                $this->m_objTemplate->addData(['msg' => $arrMessageData]);
            } else {
                $this->m_objTemplate = $this->_getErrorTemplateObject(eErrorKeys::INVALID_MESSAGE_ID);	// invalid msg id
            }
        } else {
            $this->m_objTemplate = $this->_getTemplateObject('message');
            $this->m_objTemplate->addData($this->getContextDataArray());
        }
    }
}
