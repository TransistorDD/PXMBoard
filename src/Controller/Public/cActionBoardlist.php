<?php

require_once(SRCDIR . '/Controller/Public/cPublicAction.php');
require_once(SRCDIR . '/Model/cBoardList.php');
require_once(SRCDIR . '/Model/cUserStatistics.php');
require_once(SRCDIR . '/Model/cMessageStatistics.php');
require_once(SRCDIR . '/Parser/cParser.php');
/**
 * show the board list
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cActionBoardlist extends cPublicAction
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
        $this->m_objTemplate = $this->_getTemplateObject('boardlist');

        $iLastOnlineTimestamp = 0;

        if ($objActiveUser = $this->getActiveUser()) {
            $iLastOnlineTimestamp = $objActiveUser->getLastOnlineTimestamp();
        }

        $this->m_objTemplate->addData($this->getContextDataArray(['propicdir' => $this->m_objConfig->getProfileImgDirectory()]));

        $objMessageParser = new cParser();	// dummy parser

        // installed boards
        $objBoardList = new cBoardList();
        $objBoardList->loadData();
        $this->m_objTemplate->addData(['boards' => ['board' => $objBoardList->getDataArray(
            $this->m_objConfig->getTimeOffset() * 3600,
            $this->m_objConfig->getDateFormat(),
            $iLastOnlineTimestamp,
            $objMessageParser
        )]]);

        // newest member
        $objStatistics = new cUserStatistics();
        if ($objUser = $objStatistics->getNewestMember()) {
            $this->m_objTemplate->addData(['newestmember' => ['user' => $objUser->getDataArray(
                $this->m_objConfig->getTimeOffset() * 3600,
                $this->m_objConfig->getDateFormat(),
                $objMessageParser
            )]]);
        }

        // newest messages
        $arrBoardMessages = [];
        $objStatistics = new cMessageStatistics();
        foreach ($objStatistics->getNewestMessages($this->m_objConfig->getAccessTimestamp() - 14 * 24 * 3600) as $objBoardMessage) {
            $arrBoardMessages[] = $objBoardMessage->getDataArray(
                $this->m_objConfig->getTimeOffset() * 3600,
                $this->m_objConfig->getDateFormat(),
                $iLastOnlineTimestamp,
                '',
                $objMessageParser
            );
        }
        $this->m_objTemplate->addData(['newestmessages' => ['msg' => $arrBoardMessages]]);
    }
}
