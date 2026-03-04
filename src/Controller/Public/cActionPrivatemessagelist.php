<?php

require_once(SRCDIR . '/Controller/Public/cPublicAction.php');
require_once(SRCDIR . '/Model/cPrivateInboxList.php');
require_once(SRCDIR . '/Model/cPrivateOutboxList.php');
/**
 * list of private messages
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cActionPrivatemessagelist extends cPublicAction
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

        $this->m_objTemplate = $this->_getTemplateObject('privatemessagelist');

        $sType = $this->m_objInputHandler->getStringFormVar('type', 'type', true, true);
        if ($sType === 'outbox') {
            // outbox
            $sType = 'outbox';
            $objPrivateMessageList = new cPrivateOutboxList($objActiveUser->getId(), $this->m_objConfig->getTimeOffset() * 3600, $this->m_objConfig->getDateFormat());
        } else {
            // inbox
            $sType = 'inbox';
            $objPrivateMessageList = new cPrivateInboxList($objActiveUser->getId(), $this->m_objConfig->getTimeOffset() * 3600, $this->m_objConfig->getDateFormat());
        }
        $objPrivateMessageList->loadData($this->m_objInputHandler->getIntFormVar('page', true, true, true), $this->m_objConfig->getPrivateMessagesPerPage());

        $this->m_objTemplate->addData($this->getContextDataArray(['previd'	=> $objPrivateMessageList->getPrevPageId(),
                                                                                'nextid'	=> $objPrivateMessageList->getNextPageId(),
                                                                                'type'		=> $sType]));
        $this->m_objTemplate->addData(['msg' => $objPrivateMessageList->getDataArray()]);
    }
}
