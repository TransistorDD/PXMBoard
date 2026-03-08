<?php

namespace PXMBoard\Model;

use PXMBoard\Database\cDB;
use PXMBoard\Enum\eBoardStatus;
use PXMBoard\Enum\eMessageStatus;

/**
 * message statistics
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cMessageStatistics
{
    /**
     * get the amount of messages
     *
     * @return int amount of messages
     */
    public function getMessageCount(): int
    {
        if ($objResultSet = cDB::getInstance()->executeQuery('SELECT count(*) AS messages FROM pxm_message')) {
            if ($objResultRow = $objResultSet->getNextResultRowObject()) {
                return $objResultRow->messages;
            }
        }
        return 0;
    }

    /**
     * get the amount of private messages
     *
     * @return int amount of private messages
     */
    public function getPrivateMessageCount(): int
    {
        if ($objResultSet = cDB::getInstance()->executeQuery('SELECT count(*) AS messages FROM pxm_priv_message')) {
            if ($objResultRow = $objResultSet->getNextResultRowObject()) {
                return $objResultRow->messages;
            }
        }
        return 0;
    }

    /**
     * get the newest messages
     *
     * @param int $iTimeSpan timespan
     * @return list<cBoardMessage> newest messages
     */
    public function getNewestMessages(int $iTimeSpan): array
    {
        return $this->_getMessagesByAttribute('m_tstmp', 'DESC', 10, $iTimeSpan);
    }

    /**
     * get the oldest messages
     *
     * @return list<cBoardMessage> oldest messages
     */
    public function getOldestMessages(): array
    {
        return $this->_getMessagesByAttribute('m_tstmp', 'ASC', 10);
    }

    /**
     * get board messages selected by a passed attribute
     *
     * @param string $sAttribute db attribute
     * @param string $sOrder order by (asc|desc)
     * @param int $iLimit limit the result to x rows
     * @param int $iTimeSpan timespan
     * @return list<cBoardMessage> boardmessage objects
     */
    private function _getMessagesByAttribute(string $sAttribute, string $sOrder = 'ASC', int $iLimit = 1, int $iTimeSpan = 0): array
    {
        $arrBoardMessages = [];

        // Use a subquery to force the optimizer to leverage the m_tstmp index for early-exit
        // before joining against the small pxm_board/pxm_thread tables.
        $iInnerLimit = $iLimit * 5;
        $sSql = 'SELECT m.m_id,m.m_parentid,t.t_boardid,t.t_id,t.t_active,m.m_subject,m.m_tstmp,m.m_userid,m.m_username,m.m_usermail,m.m_userhighlight'
            . ' FROM (SELECT m_id,m_parentid,m_threadid,m_subject,m_tstmp,m_userid,m_username,m_usermail,m_userhighlight'
            . ' FROM pxm_message'
            . ' WHERE m_tstmp>' . (int) $iTimeSpan
            . ' AND m_status=' . eMessageStatus::PUBLISHED->value
            . ' ORDER BY m_tstmp ' . $sOrder
            . ' LIMIT ' . $iInnerLimit . ') m'
            . ' INNER JOIN pxm_thread t ON t.t_id=m.m_threadid'
            . ' INNER JOIN pxm_board b ON b.b_id=t.t_boardid'
            . ' WHERE b.b_status!=' . eBoardStatus::CLOSED->value
            . ' ORDER BY m.' . $sAttribute . ' ' . $sOrder;

        if ($objResultSet = cDB::getInstance()->executeQuery($sSql, $iLimit)) {
            while ($objResultRow = $objResultSet->getNextResultRowObject()) {

                $objBoardMessage = new cBoardMessage();

                $objBoardMessage->setId($objResultRow->m_id);
                $objBoardMessage->setParentId($objResultRow->m_parentid);
                $objBoardMessage->setBoardId($objResultRow->t_boardid);
                $objBoardMessage->setThreadId($objResultRow->t_id);
                $objBoardMessage->setIsThreadActive($objResultRow->t_active);
                $objBoardMessage->setSubject($objResultRow->m_subject);
                $objBoardMessage->setMessageTimestamp($objResultRow->m_tstmp);
                $objBoardMessage->setAuthorId($objResultRow->m_userid);
                $objBoardMessage->setAuthorUserName($objResultRow->m_username);
                $objBoardMessage->setAuthorPublicMail($objResultRow->m_usermail);
                $objBoardMessage->setAuthorHighlightUser($objResultRow->m_userhighlight);

                $arrBoardMessages[] = $objBoardMessage;
            }
            $objResultSet->freeResult();
        }
        return $arrBoardMessages;
    }
}
