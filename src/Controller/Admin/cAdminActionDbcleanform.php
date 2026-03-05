<?php

namespace PXMBoard\Controller\Admin;

/**
 * displays the db clean tool
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cAdminActionDbcleanform extends cAdminAction
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

        $this->m_sOutput .= "<div class=\"pxm-admin-card\">\n<div class=\"pxm-admin-card__header\">delete / restore invalid database entries</div>\n<div class=\"pxm-admin-card__body\">\n";
        $this->m_sOutput .= "<form action=\"pxmboard.php\" method=\"post\" onsubmit=\"return confirm('clean database?')\">\n";
        $this->m_sOutput .= $this->_getHiddenCsrfField();
        $this->m_sOutput .= "<input type=\"hidden\" name=\"mode\" value=\"admdbclean\">\n";
        $this->m_sOutput .= $this->_getCheckboxField('nobrd', '1', 'delete lost threads (invalid board id)?');
        $this->m_sOutput .= $this->_getCheckboxField('nousr', '1', 'delete lost messages (invalid user id)?');
        $this->m_sOutput .= $this->_getCheckboxField('nomod', '1', 'delete lost moderators (invalid user or board id)?');
        $this->m_sOutput .= $this->_getCheckboxField('nomsg', '1', 'delete empty threads?');
        $this->m_sOutput .= $this->_getCheckboxField('restrd', '1', 'restore messages and threads?');
        $this->m_sOutput .= $this->_getCheckboxField('cleanread', '1', 'delete old read tracking entries (>60 days)?');
        $this->m_sOutput .= $this->_getCheckboxField('logintickets', '1', 'delete old login tickets (>180 days / 6 months)?');
        $this->m_sOutput .= "<div class=\"pxm-btn-row\"><button type=\"submit\" class=\"pxm-btn pxm-btn--primary\">clean database</button></div>\n";
        $this->m_sOutput .= "</form>\n</div>\n</div>";
        $this->m_sOutput .= $this->_getFooter();
    }
}
