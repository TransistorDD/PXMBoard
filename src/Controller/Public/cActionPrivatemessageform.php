<?php

require_once(SRCDIR . '/Controller/Public/cPublicAction.php');
require_once(SRCDIR . '/Model/cPrivateMessage.php');
require_once(SRCDIR . '/Model/cBoardMessage.php');
require_once(SRCDIR . '/Model/cUser.php');
require_once(SRCDIR . '/Parser/cPxmParser.php');
/**
 * displays a private message form
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cActionprivatemessageform extends cPublicAction
{
    /**
     * Validate permissions for this action
     *
     * @return bool true if all permissions granted
     */
    public function validateBasePermissionsAndConditions(): bool
    {
        return $this->_requirePostPermission();
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
        $iDestinationId = $this->m_objInputHandler->getIntFormVar('toid', true, true, true);
        if ($iDestinationId > 0) {
            $objDestinationUser = new cUser();
            if ($objDestinationUser->loadDataById($iDestinationId)) {
                $this->m_objTemplate = $this->_getTemplateObject('privatemessageform');

                if ($this->m_objConfig->useSignatures()) {
                    $this->m_objTemplate->addData($this->getContextDataArray(['type' => 'outbox',
                                                                                            'user' => ['signature' => $objActiveUser->getSignature()]]));
                } else {
                    $this->m_objTemplate->addData($this->getContextDataArray(['type' => 'outbox']));
                }

                $this->m_objTemplate->addData(['touser' => ['id'		=> $objDestinationUser->getId(),
                                                                                'username'	=> $objDestinationUser->getUserName()]]);

                $iMessageId = $this->m_objInputHandler->getIntFormVar('msgid', true, true, true);

                // parse the message body
                $objPxmParser = $this->_getPredefinedPxmParser(true, true);

                if ($iMessageId > 0) {
                    if ($objActiveBoard = $this->getActiveBoard()) {

                        $objMessage = new cBoardMessage();

                        if ($objMessage->loadDataById($iMessageId, $objActiveBoard->getId())) {
                            $this->m_objTemplate->addData(['msg' => $objMessage->getDataArray(
                                $this->m_objConfig->getTimeOffset() * 3600,
                                $this->m_objConfig->getDateFormat(),
                                $iLastOnline,
                                $this->m_objConfig->getQuoteSubject(),
                                $objPxmParser
                            )]);
                        }
                    }
                } else {
                    $iMessageId = $this->m_objInputHandler->getIntFormVar('pmsgid', true, true, true);

                    if ($iMessageId > 0) {
                        $objPrivateMessage = new cPrivateMessage();
                        $objPrivateMessage->setDestinationUserId($objActiveUser->getId());

                        if ($objPrivateMessage->loadDataById($iMessageId)) {
                            $this->m_objTemplate->addData(['msg' => $objPrivateMessage->getDataArray(
                                $this->m_objConfig->getTimeOffset() * 3600,
                                $this->m_objConfig->getDateFormat(),
                                $iLastOnline,
                                $this->m_objConfig->getQuoteSubject(),
                                $objPxmParser
                            )]);
                        }
                    }
                }
            } else {
                $this->m_objTemplate = $this->_getErrorTemplateObject(eError::INVALID_USER_ID);
            }// invalid user id
        } else {
            $this->m_objTemplate = $this->_getErrorTemplateObject(eError::INVALID_USER_ID);
        }	// invalid user id
    }
}
