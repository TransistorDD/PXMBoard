<?php

require_once(SRCDIR . '/Controller/Public/cActionBoardlist.php');
/**
 * user logout
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cActionLogout extends cActionBoardlist
{
    /**
     * do the pre actions
     *
     * @return void
     */
    public function doPreActions(): void
    {
        // Clear active user - pxmboard.php will destroy session automatically
        $this->m_objActiveUser = null;
    }
}
