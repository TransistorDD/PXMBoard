<?php

require_once(SRCDIR . '/Controller/Admin/cAdminAction.php');
require_once(SRCDIR . '/Model/cProfileConfig.php');
require_once(SRCDIR . '/Enum/eUser.php');
require_once(SRCDIR . '/Model/cBoardList.php');
require_once(SRCDIR . '/Model/cUserAdmin.php');
require_once(SRCDIR . '/Model/cSkinList.php');
require_once(SRCDIR . '/Parser/cParser.php');
/**
 * displays the user edit form
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cAdminActionUserform extends cAdminAction
{
    /**
     * Validate permissions - requires admin rights.
     *
     * @return bool true if user is admin, false otherwise
     */
    public function validateBasePermissionsAndConditions(): bool
    {
        return $this->_requireAdminPermission();
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

        if ($iUserId > 0) {

            $objProfileConfig = new cProfileConfig();
            $arrSlotList = $objProfileConfig->getSlotList();

            $objUser = new cUserAdmin($arrSlotList);

            if ($objUser->loadDataById($iUserId)) {
                $this->m_sOutput .= "<form action=\"pxmboard.php\" method=\"post\" onsubmit=\"return confirm('update userdata?')\">".$this->_getHiddenField('mode', 'admusersave');
                $this->m_sOutput .= $this->_getHiddenCsrfField();

                $this->m_sOutput .= "<div class=\"pxm-admin-card\"><div class=\"pxm-admin-card__header\">profile</div><div class=\"pxm-admin-card__body\">\n";
                $this->m_sOutput .= "<div class=\"pxm-form-group\"><label>ID</label><div class=\"pxm-field\">$iUserId".$this->_getHiddenField('usrid', $iUserId)."</div></div>\n";
                $this->m_sOutput .= $this->_getTextField('nick', $this->m_objInputHandler->getInputSize('username'), $objUser->getUserName(), 'username');
                $this->m_sOutput .= $this->_getTextField('fname', $this->m_objInputHandler->getInputSize('firstname'), $objUser->getFirstName(), 'firstname');
                $this->m_sOutput .= $this->_getTextField('lname', $this->m_objInputHandler->getInputSize('lastname'), $objUser->getLastName(), 'lastname');
                $this->m_sOutput .= $this->_getTextField('city', $this->m_objInputHandler->getInputSize('city'), $objUser->getCity(), 'city');
                $this->m_sOutput .= $this->_getTextField('pmail', $this->m_objInputHandler->getInputSize('email'), $objUser->getPublicMail(), 'public mailadr');
                $this->m_sOutput .= $this->_getTextField('prmail', $this->m_objInputHandler->getInputSize('email'), $objUser->getPrivateMail(), 'private mailadr');
                $this->m_sOutput .= '<div class="pxm-form-group"><label>registration mailadr</label><div class="pxm-field">'.htmlspecialchars($objUser->getRegistrationMail())."</div></div>\n";
                $this->m_sOutput .= "<div class=\"pxm-form-group\"><label>signature</label><div class=\"pxm-field\"><textarea cols=\"20\" rows=\"3\" name=\"signature\">\n".htmlspecialchars($objUser->getSignature())."</textarea></div></div>\n";

                foreach ($arrSlotList as $sKey => $arrVal) {
                    switch ($arrVal[0]) {
                        case 'a':	$this->m_sOutput .= '<div class="pxm-form-group"><label>'.htmlspecialchars($sKey).'</label><div class="pxm-field"><textarea cols="20" rows="3" name="'.htmlspecialchars('profile_'.$sKey).'">'.htmlspecialchars($objUser->getAdditionalDataElement($sKey))."</textarea></div></div>\n";
                            break;
                        case 's':	$this->m_sOutput .= $this->_getTextField('profile_'.$sKey, $arrVal[1], $objUser->getAdditionalDataElement($sKey), $sKey);
                            break;
                        default:	$this->m_sOutput .= $this->_getTextField('profile_'.$sKey, 10, $objUser->getAdditionalDataElement($sKey), $sKey);
                            break;
                    }
                }

                $sDateFormat = $this->m_objConfig->getDateFormat();
                $iTimeOffset = $this->m_objConfig->getTimeOffset() * 3600;

                $this->m_sOutput .= $this->_getCheckboxField('delpic', '1', 'delete profile picture?');
                $this->m_sOutput .= '<div class="pxm-form-group"><label>date of registration</label><div class="pxm-field">'.(($objUser->getRegistrationTimestamp() > 0) ? date($sDateFormat, ($objUser->getRegistrationTimestamp() + $iTimeOffset)) : 0).'</div></div>';
                $this->m_sOutput .= '<div class="pxm-form-group"><label>last online</label><div class="pxm-field">'.(($objUser->getLastOnlineTimestamp() > 0) ? date($sDateFormat, ($objUser->getLastOnlineTimestamp() + $iTimeOffset)) : 0)."</div></div>\n";
                $this->m_sOutput .= '<div class="pxm-form-group"><label>last profile edit</label><div class="pxm-field">'.(($objUser->getLastUpdateTimestamp() > 0) ? date($sDateFormat, ($objUser->getLastUpdateTimestamp() + $iTimeOffset)) : 0)."</div></div>\n";
                $this->m_sOutput .= '<div class="pxm-form-group"><label>quantity of messages</label><div class="pxm-field">'.$objUser->getMessageQuantity()."</div></div>\n";
                $this->m_sOutput .= "</div></div>\n";

                $this->m_sOutput .= "<div class=\"pxm-admin-card\"><div class=\"pxm-admin-card__header\">rights</div><div class=\"pxm-admin-card__body\">\n";

                $this->m_sOutput .= '<div class="pxm-form-group"><label>state</label><div class="pxm-field"><select name="state" size="1">';
                foreach (UserStatus::getAll() as $iKey => $sVal) {
                    $this->m_sOutput .= '<option value="'.$iKey.(($objUser->getStatus()->value == $iKey) ? '" selected>' : '">').htmlspecialchars($sVal).'</option>';
                }
                $this->m_sOutput .= "</select></div></div>\n";

                $this->m_sOutput .= $this->_getCheckboxField('post', '1', 'post?', $objUser->isPostAllowed());
                $this->m_sOutput .= $this->_getCheckboxField('edit', '1', 'edit?', $objUser->isEditAllowed());
                $this->m_sOutput .= $this->_getCheckboxField('admin', '1', 'administrator?', $objUser->isAdmin(), " onclick=\"return confirm('change admin status?')\"");
                $this->m_sOutput .= $this->_getCheckboxField('high', '1', 'highlight user?', $objUser->highlightUser());

                $this->m_sOutput .= "<div class=\"pxm-form-group\"><label>moderator for</label><div class=\"pxm-field\">\n";
                $this->m_sOutput .= "<select name=\"mod[]\" size=\"4\" multiple>\n";
                $arrBoardIds = [];
                $objUser->loadModData();
                foreach ($objUser->getModeratedBoards() as $objBoard) {
                    $arrBoardIds[] = $objBoard->getId();
                }

                foreach ($this->_getBoardListArray() as $arrBoard) {
                    $this->m_sOutput .= '<option value="'.$arrBoard['id'].(in_array($arrBoard['id'], $arrBoardIds) ? '" selected>' : '">').htmlspecialchars($arrBoard['name']).'</option>';
                }
                $this->m_sOutput .= "</select></div></div>\n";
                $this->m_sOutput .= "</div></div>\n";

                $this->m_sOutput .= "<div class=\"pxm-admin-card\"><div class=\"pxm-admin-card__header\">new password</div><div class=\"pxm-admin-card__body\">\n";
                $this->m_sOutput .= $this->_getPasswordField('pass1', $this->m_objInputHandler->getInputSize('password'), 'password');
                $this->m_sOutput .= $this->_getPasswordField('pass2', $this->m_objInputHandler->getInputSize('password'), 'repeat');
                $this->m_sOutput .= "</div></div>\n";

                $this->m_sOutput .= "<div class=\"pxm-admin-card\"><div class=\"pxm-admin-card__header\">configuration</div><div class=\"pxm-admin-card__body\">\n";
                $this->m_sOutput .= '<div class="pxm-form-group"><label>skin</label><div class="pxm-field"><select name="skinid" size="1">';
                $this->m_sOutput .= '<option value="0">default</option>';

                $arrAvailableTemplateEngines = $this->m_objConfig->getAvailableTemplateEngines();
                $objSkinList = new cSkinList();
                foreach ($objSkinList->getList() as $objSkin) {
                    if (array_intersect($arrAvailableTemplateEngines, $objSkin->getSupportedTemplateEngines())) {
                        $this->m_sOutput .= '<option value="'.$objSkin->getId().(($objUser->getSkinId() == $objSkin->getId()) ? '" selected>' : '">').htmlspecialchars($objSkin->getName()).'</option>';
                    }
                }

                $this->m_sOutput .= "</select></div></div>\n";

                $sSortMode = $objUser->getThreadListSortMode();
                $this->m_sOutput .= '<div class="pxm-form-group"><label>sortmode</label><div class="pxm-field"><select name="sort" size="1">';
                $this->m_sOutput .= '<option value="thread"'.((strcasecmp($sSortMode, 'thread') == 0) ? ' selected' : '').">thread</option>\n";
                $this->m_sOutput .= '<option value="last"'.((strcasecmp($sSortMode, 'last') == 0) ? ' selected' : '').">last reply</option>\n";
                $this->m_sOutput .= '<option value="username"'.((strcasecmp($sSortMode, 'username') == 0) ? ' selected' : '').">username</option>\n";
                $this->m_sOutput .= '<option value="subject"'.((strcasecmp($sSortMode, 'subject') == 0) ? ' selected' : '').">subject</option>\n";
                $this->m_sOutput .= '<option value="replies"'.((strcasecmp($sSortMode, 'replies') == 0) ? ' selected' : '').">replies</option>\n";
                $this->m_sOutput .= '<option value="views"'.((strcasecmp($sSortMode, 'views') == 0) ? ' selected' : '').">views</option>\n";
                $this->m_sOutput .= "</select></div></div>\n";

                $this->m_sOutput .= $this->_getTextField('toff', 2, (string)($objUser->getTimeOffset() * 3600), 'timeoffset');
                $this->m_sOutput .= $this->_getCheckboxField('embed_external', '1', 'Externe Inhalte einbetten (Bilder, YouTube, Twitch)?', $objUser->embedExternal());
                $this->m_sOutput .= $this->_getCheckboxField('visible', '1', 'is visible?', $objUser->isVisible());
                $this->m_sOutput .= $this->_getCheckboxField('privnot', '1', 'private message notification?', $objUser->sendPrivateMessageNotification());
                $this->m_sOutput .= "</div></div>\n";

                $this->m_sOutput .= '<div class="pxm-btn-row"><button type="submit" class="pxm-btn pxm-btn--primary">update data</button> <button type="reset" class="pxm-btn">reset data</button></div>';
                $this->m_sOutput .= '</form>';
            } else {
                $this->m_sOutput .= $this->_getAlert('invalid userid');
            }
        } else {
            $this->m_sOutput .= $this->_getAlert('invalid userid');
        }

        $this->m_sOutput .= $this->_getFooter();
    }
}
