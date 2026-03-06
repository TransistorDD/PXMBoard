<?php

namespace PXMBoard\Model;

use PXMBoard\Database\cDB;
use PXMBoard\Enum\eMessageStatus;

/**
 * message draft list handling
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cMessageDraftList extends cScrollList
{
    protected int $m_iUserId;				// user id
    protected string $m_sDateFormat;			// date format
    protected int $m_iTimeOffset;			// time offset

    /**
     * Constructor
     *
     * @param int $iUserId user id
     * @param int $iTimeOffset time offset
     * @param string $sDateFormat date format
     */
    public function __construct(int $iUserId, int $iTimeOffset = 0, string $sDateFormat = '')
    {
        parent::__construct();

        $this->m_iUserId = $iUserId;
        $this->m_iTimeOffset = $iTimeOffset;
        $this->m_sDateFormat = $sDateFormat;
    }

    /**
     * get the query
     *
     * @return string query
     */
    protected function _getQuery(): string
    {
        $sQuery = 'SELECT m.m_id,m.m_threadid,m.m_subject,m.m_tstmp,m.m_userid,m.m_username,m.m_userhighlight,m.m_status,b.b_name,b.b_id ';
        $sQuery .= 'FROM pxm_message m ';
        $sQuery .= 'LEFT JOIN pxm_thread t ON m.m_threadid = t.t_id ';
        $sQuery .= 'LEFT JOIN pxm_board b ON t.t_boardid = b.b_id ';
        $sQuery .= "WHERE m.m_userid=$this->m_iUserId AND m.m_status=".eMessageStatus::DRAFT->value;
        $sQuery .= ' ORDER BY m.m_tstmp DESC';
        return $sQuery;
    }

    /**
     * initalize the member variables with the resultrow from the db
     *
     * @param object $objResultRow resultrow from db query
     * @return bool success / failure
     */
    protected function _setDataFromDb(object $objResultRow): bool
    {
        $this->m_arrResultList[] = ['id'		=> $objResultRow->m_id,
                                    'threadid'	=> $objResultRow->m_threadid,
                                    'subject'	=> $objResultRow->m_subject,
                                    'boardid'	=> $objResultRow->b_id,
                                    'boardname' => $objResultRow->b_name,
                                    'date'		=> date($this->m_sDateFormat, ($objResultRow->m_tstmp + $this->m_iTimeOffset)),
                                    'status'	=> $objResultRow->m_status,
                                    'user'		=> ['id'		=> $objResultRow->m_userid,
                                                    'username'	=> $objResultRow->m_username,
                                                    'highlight'	=> $objResultRow->m_userhighlight]];
        return true;
    }

    /**
     * count draft messages for user
     *
     * @return int amount of draft messages
     */
    public function countDrafts(): int
    {
        if ($objResultSet = cDB::getInstance()->executeQuery("SELECT count(*) AS msgcount FROM pxm_message WHERE m_userid=$this->m_iUserId AND m_status=".eMessageStatus::DRAFT->value)) {
            if ($objResultRow = $objResultSet->getNextResultRowObject()) {
                return (int) $objResultRow->msgcount;
            }
        }
        return 0;
    }
}
