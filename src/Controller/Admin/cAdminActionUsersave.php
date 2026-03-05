<?php

require_once(SRCDIR . '/Controller/Admin/cAdminAction.php');
require_once(SRCDIR . '/Model/cProfileConfig.php');
require_once(SRCDIR . '/Model/cUserAdmin.php');
require_once(SRCDIR . '/Enum/eUserStatus.php');
/**
 * save the user data
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cAdminActionUsersave extends cAdminAction
{
    /**
     * Validate permissions - requires admin rights and valid CSRF token.
     *
     * @return bool true if admin and CSRF valid, false otherwise
     */
    public function validateBasePermissionsAndConditions(): bool
    {
        return $this->_requireValidCsrfToken() && $this->_requireAdminPermission();
    }

    /**
     * perform the action
     *
     * @return void
     */
    public function performAction(): void
    {

        $this->m_sOutput .= $this->_getHead();

        $iUserId = $this->m_objInputHandler->getIntFormVar('usrid', true, true, true);

        $this->m_sOutput .= "<div class=\"pxm-admin-card\"><div class=\"pxm-admin-card__header\">edit user data</div><div class=\"pxm-admin-card__body\">\n";

        if ($iUserId > 0) {

            $objProfileConfig = new cProfileConfig();
            $arrSlotList = $objProfileConfig->getSlotList();

            $objUser = new cUserAdmin($arrSlotList);

            if ($objUser->loadDataById($iUserId)) {
                $objUser->setUserName($this->m_objInputHandler->getStringFormVar('nick', 'username', true, true, 'trim'));
                $objUser->setCity($this->m_objInputHandler->getStringFormVar('city', 'city', true, true, 'trim'));
                $objUser->setPublicMail($this->m_objInputHandler->getStringFormVar('pmail', 'email', true, true, 'trim'));
                $objUser->setPrivateMail($this->m_objInputHandler->getStringFormVar('prmail', 'email', true, true, 'trim'));
                $objUser->setFirstName($this->m_objInputHandler->getStringFormVar('fname', 'firstname', true, true, 'trim'));
                $objUser->setLastName($this->m_objInputHandler->getStringFormVar('lname', 'lastname', true, true, 'trim'));
                $objUser->setSignature($this->m_objInputHandler->getStringFormVar('signature', 'signature', true, true, 'rtrim'));
                $objUser->setHighlightUser($this->m_objInputHandler->getIntFormVar('high', true, true, true));

                foreach ($arrSlotList as $sKey => $arrVal) {
                    if ($arrVal[0] == 'i') {
                        $objUser->setAdditionalDataElement($sKey, $this->m_objInputHandler->getIntFormVar('profile_'.$sKey, true, true, false));
                    } else {
                        $sValue = $this->m_objInputHandler->getStringFormVar('profile_'.$sKey, '', true, true, 'trim');
                        if (mb_strlen($sValue) > $arrVal[1]) {
                            $sValue = mb_substr($sValue, 0, $arrVal[1]);
                        }
                        $objUser->setAdditionalDataElement($sKey, $sValue);
                    }
                }

                $iStatus = $this->m_objInputHandler->getIntFormVar('state', true, true, true);
                try {
                    $eStatus = eUserStatus::from($iStatus);
                    $objUser->setStatus($eStatus);
                } catch (ValueError $e) {
                    // Invalid status, default to ACTIVE
                    $objUser->setStatus(eUserStatus::ACTIVE);
                }
                $objUser->setPostAllowed($this->m_objInputHandler->getIntFormVar('post', true, true, true));
                $objUser->setEditAllowed($this->m_objInputHandler->getIntFormVar('edit', true, true, true));
                $objUser->setAdmin($this->m_objInputHandler->getIntFormVar('admin', true, true, true));

                $objUser->setIsVisible($this->m_objInputHandler->getIntFormVar('visible', true, true, true));
                $objUser->setSkinId($this->m_objInputHandler->getIntFormVar('skinid', true, true, true));
                $objUser->setThreadListSortMode($this->m_objInputHandler->getStringFormVar('sort', 'sortmode', true, true, 'trim'));
                $objUser->setTimeOffset($this->m_objInputHandler->getIntFormVar('toff', true, true));
                $objUser->setEmbedExternal($this->m_objInputHandler->getIntFormVar('embed_external', true, true, true));
                $objUser->setSendPrivateMessageNotification($this->m_objInputHandler->getIntFormVar('privnot', true, true, true));

                $objUser->setModeratedBoardsById($this->m_objInputHandler->getArrFormVar('mod', true, true, true, 'intval'));

                if ($objUser->updateData()) {
                    $objUser->updateModData();
                    $this->m_sOutput .= $this->_getAlert('data saved', 'success');
                } else {
                    $this->m_sOutput .= $this->_getAlert('could not update data');
                }

                $sPassword1 = $this->m_objInputHandler->getStringFormVar('pass1', 'password', true, true, 'trim');
                $sPassword2 = $this->m_objInputHandler->getStringFormVar('pass2', 'password', true, true, 'trim');
                if (!empty($sPassword1) && !empty($sPassword2)) {
                    if ($objUser->changePassword($sPassword1, $sPassword2)) {
                        $this->m_sOutput .= $this->_getAlert('password changed', 'success');
                    } else {
                        $this->m_sOutput .= $this->_getAlert('could not change password');
                    }
                }
                if ($this->m_objInputHandler->getIntFormVar('delpic', true, true) == 1) {
                    if ($objUser->deleteImage($this->m_objConfig->getProfileImgFsDirectory())) {
                        $this->m_sOutput .= $this->_getAlert('picture removed', 'success');
                    }
                }
            } else {
                $this->m_sOutput .= $this->_getAlert('invalid userid');
            }
        } else {
            $this->m_sOutput .= $this->_getAlert('invalid userid');
        }

        $this->m_sOutput .= "</div></div>\n";

        $this->m_sOutput .= $this->_getFooter();
    }
}
