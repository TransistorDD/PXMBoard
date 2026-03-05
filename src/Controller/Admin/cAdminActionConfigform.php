<?php

namespace PXMBoard\Controller\Admin;

use PXMBoard\Model\cSkinList;

/**
 * displays the general config edit form
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cAdminActionConfigform extends cAdminAction
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

        $this->m_sOutput .= "<form action=\"pxmboard.php\" method=\"post\" onsubmit=\"return confirm('update configuration?')\">\n";
        $this->m_sOutput .= $this->_getHiddenCsrfField();
        $this->m_sOutput .= '<input type="hidden" name="mode" value="admconfigsave">';

        $this->m_sOutput .= "<div class=\"pxm-admin-card\"><div class=\"pxm-admin-card__header\">general configuration</div><div class=\"pxm-admin-card__body\">\n";

        $this->m_sOutput .= "<div class=\"pxm-form-group\"><label>default skin</label><div class=\"pxm-field\"><select name=\"skinid\" size=\"1\">\n";

        $arrAvailableTemplateEngines = $this->m_objConfig->getAvailableTemplateEngines();
        $objSkinList = new cSkinList();
        foreach ($objSkinList->getList() as $objSkin) {
            $this->m_sOutput .= '<option value="'.$objSkin->getId().(($this->m_objConfig->getDefaultSkinId() == $objSkin->getId()) ? '" selected>' : '">').htmlspecialchars($objSkin->getName()).'</option>';
        }
        $this->m_sOutput .= "</select></div></div>\n";

        $this->m_sOutput .= $this->_getCheckboxField('quickpost', '1', 'enable quickpost?', $this->m_objConfig->useQuickPost());
        $this->m_sOutput .= $this->_getCheckboxField('signatures', '1', 'enable signatures?', $this->m_objConfig->useSignatures(false));
        $this->m_sOutput .= $this->_getCheckboxField('directregistration', '1', 'enable direct registration?', $this->m_objConfig->useDirectRegistration());
        $this->m_sOutput .= $this->_getCheckboxField('uniquemail', '1', 'unique registration mail adr?', $this->m_objConfig->uniqueRegistrationMails());
        $this->m_sOutput .= '<div class="pxm-form-group"><label>date format (<a href="https://www.php.net/manual/en/datetime.format.php" target="_blank">php style</a>)</label><div class="pxm-field">';
        $this->m_sOutput .= $this->_getTextField('dateformat', $this->m_objInputHandler->getInputSize('dateformat'), $this->m_objConfig->getDateFormat())."</div></div>\n";
        $this->m_sOutput .= $this->_getTextField('timeoffset', 2, $this->m_objConfig->getTimeOffset(false), 'time offset (hours)');
        $this->m_sOutput .= $this->_getTextField('onlinetime', 5, $this->m_objConfig->getOnlineTime(), "time for onlinelist (seconds; 0 = don't log online time)");
        $this->m_sOutput .= $this->_getTextField('threadsizelimit', 5, $this->m_objConfig->getThreadSizeLimit(), 'message limit per thread (0 = no limit)');
        $this->m_sOutput .= $this->_getTextField('userperpage', 3, $this->m_objConfig->getUserPerPage(), 'user per page (online, search & admin)');
        $this->m_sOutput .= $this->_getTextField('threadsperpage', 3, $this->m_objConfig->getThreadsPerPage(), 'threads per page (msg index)');
        $this->m_sOutput .= $this->_getTextField('messageheaderperpage', 3, $this->m_objConfig->getMessageHeaderPerPage(), 'messages per page (search)');
        $this->m_sOutput .= $this->_getTextField('privatemessagesperpage', 3, $this->m_objConfig->getPrivateMessagesPerPage(), 'private messages per page');
        $this->m_sOutput .= $this->_getTextField('mailwebmaster', $this->m_objInputHandler->getInputSize('email'), $this->m_objConfig->getMailWebmaster(), 'mail webmaster');
        $this->m_sOutput .= $this->_getTextField('quotesubject', $this->m_objInputHandler->getInputSize('quotesubject'), $this->m_objConfig->getQuoteSubject(), 'quote subject');
        $this->m_sOutput .= $this->_getTextField('skindir', $this->m_objInputHandler->getInputSize('directory'), $this->m_objConfig->getSkinDirectory(), 'skin dir');
        $this->m_sOutput .= $this->_getTextField('imgsize', 10, $this->m_objConfig->getMaxProfileImgSize(), 'max profile img size (byte)');
        $this->m_sOutput .= $this->_getTextField('imgheight', 5, $this->m_objConfig->getMaxProfileImgHeight(), 'max profile img height (pixel)');
        $this->m_sOutput .= $this->_getTextField('imgwidth', 5, $this->m_objConfig->getMaxProfileImgWidth(), 'max profile img width (pixel)');
        $this->m_sOutput .= $this->_getTextField('imgdir', $this->m_objInputHandler->getInputSize('directory'), $this->m_objConfig->getProfileImgDirectory(), 'profile img dir');
        $this->m_sOutput .= "</div></div>\n";

        $this->m_sOutput .= "<div class=\"pxm-btn-row\"><button type=\"submit\" class=\"pxm-btn pxm-btn--primary\">update data</button> <button type=\"reset\" class=\"pxm-btn\">reset data</button></div>\n";
        $this->m_sOutput .= '</form>';

        $this->m_sOutput .= $this->_getFooter();
    }
}
