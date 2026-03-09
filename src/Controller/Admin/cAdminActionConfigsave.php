<?php

namespace PXMBoard\Controller\Admin;

/**
 * save the general config
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cAdminActionConfigsave extends cAdminAction
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

        $this->m_objConfig->setDefaultSkinId($this->m_objInputHandler->getIntFormVar('skinid', true, true, true));
        $this->m_objConfig->setUseQuickPost((bool)$this->m_objInputHandler->getIntFormVar('quickpost', true, true, true));
        $this->m_objConfig->setUseSignatures((bool)$this->m_objInputHandler->getIntFormVar('signatures', true, true, true));
        $this->m_objConfig->setUseDirectRegistration((bool)$this->m_objInputHandler->getIntFormVar('directregistration', true, true, true));
        $this->m_objConfig->setUniqueRegistrationMails((bool)$this->m_objInputHandler->getIntFormVar('uniquemail', true, true, true));

        $this->m_objConfig->setDateFormat($this->m_objInputHandler->getStringFormVar('dateformat', 'dateformat', true, true, 'trim'));
        $this->m_objConfig->setTimeOffset($this->m_objInputHandler->getIntFormVar('timeoffset', true, true));
        $this->m_objConfig->setOnlineTime($this->m_objInputHandler->getIntFormVar('onlinetime', true, true, true));
        $this->m_objConfig->setThreadSizeLimit($this->m_objInputHandler->getIntFormVar('threadsizelimit', true, true, true));
        $this->m_objConfig->setUserPerPage($this->m_objInputHandler->getIntFormVar('userperpage', true, true, true));
        $this->m_objConfig->setThreadsPerPage($this->m_objInputHandler->getIntFormVar('threadsperpage', true, true, true));
        $this->m_objConfig->setMessageHeaderPerPage($this->m_objInputHandler->getIntFormVar('messageheaderperpage', true, true, true));
        $this->m_objConfig->setPrivateMessagesPerPage($this->m_objInputHandler->getIntFormVar('privatemessagesperpage', true, true, true));

        $this->m_objConfig->setMailWebmaster($this->m_objInputHandler->getStringFormVar('mailwebmaster', 'email', true, true, 'trim'));

        $this->m_objConfig->setQuoteSubject($this->m_objInputHandler->getStringFormVar('quotesubject', 'quotesubject', true, true, 'ltrim'));

        $this->m_objConfig->setSkinDirectory($this->m_objInputHandler->getStringFormVar('skindir', 'directory', true, true, 'trim'));
        $this->m_objConfig->setMaxProfileImgSize($this->m_objInputHandler->getIntFormVar('imgsize', true, true, true));
        $this->m_objConfig->setMaxProfileImgHeight($this->m_objInputHandler->getIntFormVar('imgheight', true, true, true));
        $this->m_objConfig->setMaxProfileImgWidth($this->m_objInputHandler->getIntFormVar('imgwidth', true, true, true));
        $this->m_objConfig->setProfileImgDirectory($this->m_objInputHandler->getStringFormVar('imgdir', 'directory', true, true, 'trim'));

        if ($this->m_objConfig->updateData()) {
            $this->m_sOutput .= $this->_getAlert('general configuration saved', 'success');
        } else {
            $this->m_sOutput .= $this->_getAlert('could not save general configuration');
        }

        $this->m_sOutput .= $this->_getFooter();
    }
}
