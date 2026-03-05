<?php

namespace PXMBoard\Model;

use PXMBoard\Database\cDBFactory;

/**
 * handles the badwords
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cBadwordList
{
    /**
     * get all badwords
     *
     * @return array{search: array<string>, replace: array<string>} badwords and replacements
     */
    public function getList(): array
    {
        $arrBadwords = ['search' => [],'replace' => []];

        if ($objResultSet = cDBFactory::getInstance()->executeQuery('SELECT bw_name,bw_replacement FROM pxm_badword')) {
            while ($objResultRow = $objResultSet->getNextResultRowObject()) {
                if (strlen($objResultRow->bw_name) > 0) {
                    $arrBadwords['search'][] = $objResultRow->bw_name;
                    $arrBadwords['replace'][] = $objResultRow->bw_replacement;
                }
            }
            $objResultSet->freeResult();
        }
        return $arrBadwords;
    }

    /**
     * update all badwords
     *
     * @param array{search?: array<string>, replace?: array<string>} $arrBadwords badwords and replacements
     * @return bool success / failure
     */
    public function updateList(array $arrBadwords): bool
    {
        if (isset($arrBadwords['search']) && isset($arrBadwords['replace'])) {
            if (cDBFactory::getInstance()->executeQuery('DELETE FROM pxm_badword')) {
                foreach ($arrBadwords['search'] as $iKey => $sBadwordSearch) {
                    if (strlen($sBadwordSearch) > 0) {
                        if (isset($arrBadwords['replace'][$iKey])) {
                            $sBadwordReplace = $arrBadwords['replace'][$iKey];
                        } else {
                            $sBadwordReplace = '';
                        }
                        cDBFactory::getInstance()->executeQuery('INSERT INTO pxm_badword (bw_name,bw_replacement) VALUES ('.cDBFactory::getInstance()->quote($sBadwordSearch).','.cDBFactory::getInstance()->quote($sBadwordReplace).')');
                    }
                }
            }
        }
        return true;
    }
}
