<?php

require_once(SRCDIR . '/Controller/Public/cPublicAction.php');
require_once(SRCDIR . '/Model/cUserSearchList.php');
require_once(SRCDIR . '/Model/cBoardList.php');
require_once(SRCDIR . '/Parser/cParser.php');
/**
 * search users
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cActionUsersearch extends cPublicAction
{
    /**
     * Validate permissions for this action
     *
     * @return bool true if all permissions granted
     */
    public function validateBasePermissionsAndConditions(): bool
    {
        return true;
    }

    /**
     * perform the action
     *
     * @return void
     */
    public function performAction(): void
    {

        if ($objActiveBoard = $this->getActiveBoard()) {
            $iIdBoard = $objActiveBoard->getId();
        } else {
            $iIdBoard = 0;
        }

        $iLastOnline = 0;
        if ($objActiveUser = $this->getActiveUser()) {
            $iLastOnline = $objActiveUser->getLastOnlineTimestamp();
        }

        $sUserName = $this->m_objInputHandler->getStringFormVar('nick', 'username', true, true, 'trim');

        if (empty($sUserName)) {
            $this->m_objTemplate = $this->_getTemplateObject('usersearch');
            $this->m_objTemplate->addData($this->getContextDataArray());
        } else {
            $this->m_objTemplate = $this->_getTemplateObject('userlist');

            // userlist
            $objUserSearchList = new cUserSearchList($sUserName);
            $objUserSearchList->loadData($this->m_objInputHandler->getIntFormVar('page', true, true, true), $this->m_objConfig->getUserPerPage());

            $this->m_objTemplate->addData($this->getContextDataArray(['previd'	=> $objUserSearchList->getPrevPageId(),
                                                                            'nextid'	=> $objUserSearchList->getNextPageId(),
                                                                            'username'	=> urlencode($sUserName)]));

            $this->m_objTemplate->addData(['user' => $objUserSearchList->getDataArray()]);
        }

        // installed boards
        $this->m_objTemplate->addData(['boards' => ['board' => $this->_getBoardListArray()]]);
    }
}
