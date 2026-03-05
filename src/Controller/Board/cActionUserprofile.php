<?php

namespace PXMBoard\Controller\Board;

use PXMBoard\Enum\eErrorKeys;
use PXMBoard\Model\cProfileConfig;
use PXMBoard\Model\cUserProfile;
use PXMBoard\Parser\cParser;

/**
 * shows a user profile
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cActionUserprofile extends cPublicAction
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
        $iIdUser = $this->m_objInputHandler->getIntFormVar('usrid', true, true, true);

        if ($iIdUser > 0) {

            $objProfileConfig = new cProfileConfig();

            $objUserProfile = new cUserProfile($objProfileConfig->getSlotList());

            if ($objUserProfile->loadDataById($iIdUser)) {

                $objParser = new cParser();	// dummy parser

                $this->m_objTemplate = $this->_getTemplateObject('userprofile');
                $this->m_objTemplate->addData($this->getContextDataArray(['propicdir' => $this->m_objConfig->getProfileImgDirectory()]));
                $this->m_objTemplate->addData(['user' => $objUserProfile->getDataArray(
                    $this->m_objConfig->getTimeOffset() * 3600,
                    $this->m_objConfig->getDateFormat(),
                    $objParser
                )]);
            } else {
                $this->m_objTemplate = $this->_getErrorTemplateObject(eErrorKeys::INVALID_USER_ID);
            }// invalid user id
        } else {
            $this->m_objTemplate = $this->_getErrorTemplateObject(eErrorKeys::INVALID_USER_ID);
        }	// invalid user id
    }
}
