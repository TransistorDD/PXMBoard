<?php

require_once(SRCDIR . '/Controller/Admin/cAdminAction.php');
require_once(SRCDIR . '/Model/cBadwordList.php');
/**
 * save the badwords
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cAdminActionBadwordsave extends cAdminAction
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

        $this->m_sOutput .= "<h4>save badwords</h4>\n";

        $iAllowedLengthBadword = $this->m_objInputHandler->getInputSize('badword');
        $arrBadwords = [];
        foreach (explode("\n", $this->m_objInputHandler->getStringFormVar('badword', '', true, true, 'trim')) as $sVal) {
            $arrBadword = explode('=>', trim($sVal), 2);
            if (sizeof($arrBadword) > 1) {
                $iLengthSearch = strlen($arrBadword[0]);
                $iLengthReplace = strlen($arrBadword[1]);
                if ($iLengthSearch > 0 && $iLengthSearch <= $iAllowedLengthBadword && $iLengthReplace > 0 && $iLengthReplace <= $iAllowedLengthBadword) {
                    $arrBadwords['search'][] = $arrBadword[0];
                    $arrBadwords['replace'][] = $arrBadword[1];
                }
            }
        }

        $objBadwordList = new cBadwordList();

        if ($objBadwordList->updateList($arrBadwords)) {
            $this->m_sOutput .= $this->_getAlert('badwords saved', 'success');
        } else {
            $this->m_sOutput .= $this->_getAlert('could not save badword data');
        }
        $this->m_sOutput .= $this->_getFooter();
    }
}
