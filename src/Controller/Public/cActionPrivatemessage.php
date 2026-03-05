<?php

require_once(SRCDIR . '/Controller/Public/cPublicAction.php');
require_once(SRCDIR . '/Model/cPrivateMessage.php');
require_once(SRCDIR . '/Parser/cPxmParser.php');
/**
 * displays a private message
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cActionPrivatemessage extends cPublicAction
{
    /**
     * Validate permissions for this action
     *
     * @return bool true if all permissions granted
     */
    public function validateBasePermissionsAndConditions(): bool
    {
        return $this->_requireAuthentication();
    }

    /**
     * perform the action
     *
     * @return void
     */
    public function performAction(): void
    {

        $objActiveUser = $this->getActiveUser();
        $iLastOnline = $objActiveUser->getLastOnlineTimestamp();

        $iMessageId = $this->m_objInputHandler->getIntFormVar('msgid', true, true, true);

        if ($iMessageId > 0) {
            $objPrivateMessage = new cPrivateMessage();
            $objPrivateMessage->setAuthorId($objActiveUser->getId());
            $objPrivateMessage->setDestinationUserId($objActiveUser->getId());

            if ($objPrivateMessage->loadDataById($iMessageId)) {

                $this->m_objTemplate = $this->_getTemplateObject('privatemessage');

                $sType = $this->m_objInputHandler->getStringFormVar('type', 'type', true, true);
                if ($sType !== 'inbox' && $sType !== 'outbox') {
                    $sType = 'inbox';
                }
                $this->m_objTemplate->addData($this->getContextDataArray(['type' => $sType]));

                $objActiveSkin = $this->getActiveSkin();

                // parse the message body
                $objPxmParser = $this->_getPredefinedPxmParser(true);

                $this->m_objTemplate->addData(['msg' => $objPrivateMessage->getDataArray(
                    $this->m_objConfig->getTimeOffset() * 3600,
                    $this->m_objConfig->getDateFormat(),
                    $iLastOnline,
                    '',
                    $objPxmParser
                )]);

                if ($objPrivateMessage->getDestinationUserId() == $objActiveUser->getId()) {
                    $objPrivateMessage->setMessageRead();
                }
            } else {
                $this->m_objTemplate = $this->_getErrorTemplateObject(eErrorKeys::INVALID_MESSAGE_ID);
            }	// invalid msg id
        } else {
            $this->m_objTemplate = $this->_getErrorTemplateObject(eErrorKeys::INVALID_MESSAGE_ID);
        }		// invalid msg id
    }
}
