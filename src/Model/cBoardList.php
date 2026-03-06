<?php

namespace PXMBoard\Model;

use PXMBoard\Database\cDB;
use PXMBoard\Enum\eBoardStatus;

/**
 * boardlist handling
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cBoardList
{
    /** @var array<cBoard> */
    protected array $m_arrBoards = [];			// boards

    /**
     * get data from database
     *
     * @return bool success / failure
     */
    public function loadData(): bool
    {
        if ($objResultSet = cDB::getInstance()->executeQuery('SELECT b_id,b_name,b_description,b_position,b_lastmsgtstmp,b_status FROM pxm_board ORDER BY b_position ASC')) {

            while ($objResultRow = $objResultSet->getNextResultRowObject()) {

                $objBoard = new cBoard();

                $objBoard->setId($objResultRow->b_id);
                $objBoard->setName($objResultRow->b_name);
                $objBoard->setDescription($objResultRow->b_description);
                $objBoard->setPosition($objResultRow->b_position);
                $objBoard->setLastMessageTimestamp($objResultRow->b_lastmsgtstmp);
                $objBoard->setStatus(eBoardStatus::from($objResultRow->b_status));

                $objBoard->loadModData();

                $this->m_arrBoards[] = $objBoard;
            }
            $objResultSet->freeResult();
        } else {
            return false;
        }
        return true;
    }

    /**
     * get basic data from database (without description, last message date and moderators)
     *
     * @return bool success / failure
     */
    public function loadBasicData(): bool
    {
        if ($objResultSet = cDB::getInstance()->executeQuery('SELECT b_id,b_name,b_position,b_status FROM pxm_board ORDER BY b_position ASC')) {

            while ($objResultRow = $objResultSet->getNextResultRowObject()) {

                $objBoard = new cBoard();

                $objBoard->setId($objResultRow->b_id);
                $objBoard->setName($objResultRow->b_name);
                $objBoard->setPosition($objResultRow->b_position);
                $objBoard->setStatus(eBoardStatus::from($objResultRow->b_status));

                $this->m_arrBoards[] = $objBoard;
            }
            $objResultSet->freeResult();
        } else {
            return false;
        }
        return true;
    }

    /**
     * open boards - sets status to PUBLIC for specified boards
     *
     * @param array<int> $arrBoardIds board ids
     * @return bool success / failure
     */
    public function openBoards(array $arrBoardIds): bool
    {
        if (sizeof($arrBoardIds) > 0) {
            if (!cDB::getInstance()->executeQuery('UPDATE pxm_board SET b_status=1 WHERE b_id IN ('.implode(',', $arrBoardIds).')')) {
                return false;
            }
        }
        return true;
    }

    /**
     * close all boards - sets status to CLOSED for all boards
     *
     * @return array<int> closed boards
     */
    public function closeAllBoards(): array
    {
        $arrClosedBoards = [];

        if ($objResultSet = cDB::getInstance()->executeQuery('SELECT b_id FROM pxm_board WHERE b_status!=5')) {
            while ($objResultRow = $objResultSet->getNextResultRowObject()) {
                $arrClosedBoards[] = $objResultRow->b_id;
            }
            cDB::getInstance()->executeQuery('UPDATE pxm_board SET b_status=5');
        }
        return $arrClosedBoards;
    }

    /**
     * get membervariables as array
     *
     * @param int $iTimeOffset time offset in seconds
     * @param string $sDateFormat php date format
     * @param int $iLastOnlineTimestamp last online timestamp for user
     * @param object $objParser message parser (for signature)
     * @return list<array<string, mixed>> member variables
     */
    public function getDataArray(int $iTimeOffset, string $sDateFormat, int $iLastOnlineTimestamp, object $objParser): array
    {
        $arrOutput = [];
        foreach ($this->m_arrBoards as $objBoard) {
            $arrOutput[] = $objBoard->getDataArray($iTimeOffset, $sDateFormat, $iLastOnlineTimestamp, $objParser);
        }
        return $arrOutput;
    }
}
