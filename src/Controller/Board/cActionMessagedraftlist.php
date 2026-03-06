<?php

namespace PXMBoard\Controller\Board;

use PXMBoard\Model\cMessageDraftList;

/**
 * list of message drafts
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cActionMessagedraftlist extends cPublicAction
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

        $this->m_objTemplate = $this->_getTemplateObject('messagedraftlist');

        $objDraftList = new cMessageDraftList($this->getActiveUser()->getId(), $this->m_objConfig->getTimeOffset() * 3600, $this->m_objConfig->getDateFormat());
        $objDraftList->loadData($this->m_objInputHandler->getIntFormVar('page', true, true, true), $this->m_objConfig->getMessageHeaderPerPage());

        $this->m_objTemplate->addData($this->getContextDataArray(['previd'	=> $objDraftList->getPrevPageId(),
                                                                                'nextid'	=> $objDraftList->getNextPageId()]));
        $this->m_objTemplate->addData(['drafts' => $objDraftList->getDataArray()]);
    }
}
