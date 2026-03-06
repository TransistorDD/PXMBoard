<?php

namespace PXMBoard\Model;

use PXMBoard\Database\cDB;

/**
 * handles the forbidden mails
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cForbiddenMailList
{
    /**
     * get all forbidden mails
     *
     * @return array<string> forbidden mails
     */
    public function getList(): array
    {
        $arrForbiddenMails = [];

        if ($objResultSet = cDB::getInstance()->executeQuery('SELECT fm_adress FROM pxm_forbiddenmail')) {
            while ($objResultRow = $objResultSet->getNextResultRowObject()) {
                if (strlen($objResultRow->fm_adress) > 0) {
                    $arrForbiddenMails[] = $objResultRow->fm_adress;
                }
            }
            $objResultSet->freeResult();
        }
        return $arrForbiddenMails;
    }

    /**
     * update all forbidden mails
     *
     * @param array<string> $arrForbiddenMails forbidden mails
     * @return bool success / failure
     */
    public function updateList(array $arrForbiddenMails): bool
    {
        if (cDB::getInstance()->executeQuery('DELETE FROM pxm_forbiddenmail')) {
            foreach ($arrForbiddenMails as $sForbiddenMail) {
                if (strlen($sForbiddenMail) > 0) {
                    cDB::getInstance()->executeQuery('INSERT INTO pxm_forbiddenmail (fm_adress) VALUES ('.cDB::getInstance()->quote($sForbiddenMail).')');
                }
            }
        }
        return true;
    }
}
