<?php

namespace PXMBoard\Controller\Board;

use PXMBoard\Enum\eErrorKeys;
use PXMBoard\Model\cMessageSearchList;
use PXMBoard\Model\cSearchProfile;
use PXMBoard\Model\cSearchProfileList;

/**
 * search messages
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cActionMessagesearch extends cPublicAction
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

        $iIdBoard = 0;
        if ($objActiveBoard = $this->getActiveBoard()) {
            $iIdBoard = $objActiveBoard->getId();
        }

        $iIdUser = 0;
        if ($objActiveUser = $this->getActiveUser()) {
            $iIdUser = $objActiveUser->getId();
        }

        // init search data
        $objSearch = new cSearchProfile();
        if (!$objSearch->loadDataById($this->m_objInputHandler->getIntFormVar('searchid', true, true, true))) {
            $objSearch->setIdUser($iIdUser);
            $objSearch->setSearchMessage($this->m_objInputHandler->getStringFormVar('smsg', 'searchstring', true, true, 'trim'));
            $objSearch->setSearchUser($this->m_objInputHandler->getStringFormVar('susr', 'username', true, true, 'trim'));
            $objSearch->setBoardIds($this->m_objInputHandler->getArrFormVar('sbrdid', true, true, true, 'intval'));
            $objSearch->setSearchDays($this->m_objInputHandler->getIntFormVar('days', true, true, true));
            $objSearch->setTimestamp($this->m_objConfig->getAccessTimestamp());
            $objSearch->setGroupByThread($this->m_objInputHandler->getIntFormVar('group_by_thread', true, true, true) == 1);
        }

        $objSearchProfileList = new cSearchProfileList();

        if (strlen($objSearch->getSearchMessage()) < 1 && strlen($objSearch->getSearchUser()) < 1) {

            // display the search form
            $this->_initSearchForm($iIdBoard, $objSearchProfileList);
        } else {
            // Check rate limiting - works for new searches only, not for paging and not for executing a previously executed search (recent searches in UI)
            // TODO Cache search results for an appropriate time (paging & recent searches)
            $sIpAddress = $this->m_objServerHandler->getRemoteAddr();
            $iCurrentTime = $this->m_objConfig->getAccessTimestamp();

            if (cSearchProfile::isRateLimitExceeded($sIpAddress, $iCurrentTime)) {
                // Rate limit exceeded
                $this->_initSearchForm($iIdBoard, $objSearchProfileList);
                $this->m_objTemplate->addData(['error' => ['text' => eErrorKeys::RATE_LIMIT_EXCEEDED->t()]]);
                return;
            }

            $objError = null;

            $this->m_objTemplate = $this->_getTemplateObject('messagelist');

            // messagelist
            $objMessageSearchList = new cMessageSearchList($objSearch, $this->m_objConfig->getTimeOffset() * 3600, $this->m_objConfig->getDateFormat(), $iIdUser);

            // execute search
            $objMessageSearchList->loadData($this->m_objInputHandler->getIntFormVar('page', true, true, true), $this->m_objConfig->getMessageHeaderPerPage(), $objSearch->getGroupByThread());

            if ($objMessageSearchList->getItemCount() > 500) {
                $objError = eErrorKeys::RESULT_SET_TOO_LARGE;				// too many results
            } elseif ($objSearch->getId() === 0) {
                // insert a new profile into search table
                $objSearch->setIpAddress($sIpAddress);
                $objSearch->insertData();
            }
            if (is_object($objError)) {
                // display the search form
                $this->_initSearchForm($iIdBoard, $objSearchProfileList);
                $this->m_objTemplate->addData(['error' => ['text' => $objError->t()]]);
            } else {
                // display the result
                $this->m_objTemplate->addData($this->getContextDataArray(['previd'		=> $objMessageSearchList->getPrevPageId(),
                                                                          'nextid'		=> $objMessageSearchList->getNextPageId(),
                                                                          'curid'		=> $objMessageSearchList->getCurPageId(),
                                                                          'count'		=> $objMessageSearchList->getPageCount(),
                                                                          'items'		=> $objMessageSearchList->getItemCount(),
                                                                          'searchprofile' => $objSearch->getDataArray(
                                                                              $this->m_objConfig->getTimeOffset(),
                                                                              $this->m_objConfig->getDateFormat()
                                                                          )]));
                $this->m_objTemplate->addData(['msg' => $objMessageSearchList->getDataArray()]);
            }
        }

        // installed boards
        $this->m_objTemplate->addData(['boards' => ['board' => $this->_getBoardListArray()]]);
    }

    /**
     * init the search form
     *
     * @param $iIdBoard board id
     * @param $objSearchProfileList recent searchprofiles
     * @return void
     */
    private function _initSearchForm(int $iIdBoard, cSearchProfileList $objSearchProfileList): void
    {

        // load recent searchprofiles
        $objSearchProfileList->loadData();

        $this->m_objTemplate = $this->_getTemplateObject('messagesearch');
        $this->m_objTemplate->addData($this->getContextDataArray());

        $this->m_objTemplate->addData(['searchprofiles' => ['searchprofile' => $objSearchProfileList->getDataArray(
            $this->m_objConfig->getTimeOffset() * 3600,
            $this->m_objConfig->getDateFormat()
        )]]);
    }
}
