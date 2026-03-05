<?php

namespace PXMBoard\Controller\Board;

use PXMBoard\Enum\eErrorKeys;
use PXMBoard\Model\cBoardMessage;

/**
 * display the message form
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cActionMessageform extends cPublicAction
{
    /**
     * Validate permissions for this action
     *
     * @return bool true if all permissions granted
     */
    public function validateBasePermissionsAndConditions(): bool
    {
        return $this->_requireWritableBoard();
    }

    /**
     * perform the action
     *
     * @return void
     */
    public function performAction(): void
    {

        $iMessageId = $this->m_objInputHandler->getIntFormVar('msgid', true, true, true);

        $arrAdditionalConfig = ['quickpost' => $this->m_objConfig->useQuickPost()];
        $iLastOnline = 0;
        if ($objActiveUser = $this->getActiveUser()) {
            $iLastOnline = $objActiveUser->getLastOnlineTimestamp();
        }

        if ($iMessageId > 0) {
            $objMessage = new cBoardMessage();
            if ($objMessage->loadDataById($iMessageId, $this->getActiveBoard()->getId())) {

                // Check if this is a draft - cannot reply to drafts
                if ($objMessage->isDraft()) {
                    $this->m_objTemplate = $this->_getErrorTemplateObject(eErrorKeys::INVALID_MESSAGE_ID);
                    return;
                }

                if ($objMessage->isThreadActive()) {
                    $this->m_objTemplate = $this->_getTemplateObject('messageform');
                    // New message (reply to existing)
                    $this->m_objTemplate->addData($this->getContextDataArray($arrAdditionalConfig));

                    // parse the message body
                    $objPxmParser = $this->_getPredefinedPxmParser(true, true);

                    $this->m_objTemplate->addData(['msg' => $objMessage->getDataArray(
                        $this->m_objConfig->getTimeOffset() * 3600,
                        $this->m_objConfig->getDateFormat(),
                        $iLastOnline,
                        $this->m_objConfig->getQuoteSubject(),
                        $objPxmParser
                    )]);
                } else {
                    $this->m_objTemplate = $this->_getErrorTemplateObject(eErrorKeys::THREAD_CLOSED);
                }	// thread closed
            } else {
                $this->m_objTemplate = $this->_getErrorTemplateObject(eErrorKeys::INVALID_MESSAGE_ID);
            }		// invalid msg id
        } else {
            $this->m_objTemplate = $this->_getTemplateObject('messageform');
            // New message (new thread)
            $this->m_objTemplate->addData($this->getContextDataArray($arrAdditionalConfig));
        }
    }
}
