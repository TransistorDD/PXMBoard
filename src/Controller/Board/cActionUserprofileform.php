<?php

namespace PXMBoard\Controller\Board;

use PXMBoard\Enum\eErrorKeys;
use PXMBoard\Model\cProfileConfig;
use PXMBoard\Model\cUserProfile;
use PXMBoard\Parser\cPlainTextParser;

/**
 * shows the user profile form
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cActionUserprofileform extends cPublicAction
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

        $objProfileConfig = new cProfileConfig();

        $objUserProfile = new cUserProfile($objProfileConfig->getSlotList());

        if ($objUserProfile->loadDataById($this->getActiveUser()->getId())) {

            $objPlainTextParser = new cPlainTextParser();
            $this->m_objTemplate = $this->_getTemplateObject('userprofileform');
            $this->m_objTemplate->addData($this->getContextDataArray(['propicdir' => $this->m_objConfig->getProfileImgDirectory()]));
            $this->m_objTemplate->addData(['user' => $objUserProfile->getDataArray(
                $this->m_objConfig->getTimeOffset() * 3600,
                $this->m_objConfig->getDateFormat(),
                $objPlainTextParser
            )]);
        } else {
            $this->m_objTemplate = $this->_getErrorTemplateObject(eErrorKeys::INVALID_USER_ID);
        }// invalid user id
    }
}
