<?php

namespace PXMBoard\Model;

use PXMBoard\Database\cDB;

/**
 * message notification subscription list handling
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cMessageNotificationList extends cScrollList
{
    protected int $m_iUserId;				// user id
    protected string $m_sDateFormat;		// date format
    protected int $m_iTimeOffset;			// time offset

    /**
     * Constructor
     *
     * @param int $iUserId user id
     * @param int $iTimeOffset time offset
     * @param string $sDateFormat date format
     * @return void
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
        $sQuery = 'SELECT mn.mn_messageid,m.m_threadid,m.m_subject,m.m_tstmp,b.b_id,b.b_name ';
        $sQuery .= 'FROM pxm_message_notification mn ';
        $sQuery .= 'INNER JOIN pxm_message m ON mn.mn_messageid = m.m_id ';
        $sQuery .= 'LEFT JOIN pxm_thread t ON m.m_threadid = t.t_id ';
        $sQuery .= 'LEFT JOIN pxm_board b ON t.t_boardid = b.b_id ';
        $sQuery .= "WHERE mn.mn_userid=$this->m_iUserId";
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
        $this->m_arrResultList[] = ['messageid'		    => $objResultRow->mn_messageid,
                                    'threadid'			=> $objResultRow->m_threadid,
                                    'subject'			=> $objResultRow->m_subject,
                                    'boardid'			=> $objResultRow->b_id,
                                    'boardname'		    => $objResultRow->b_name,
                                    'date'				=> date($this->m_sDateFormat, ($objResultRow->m_tstmp + $this->m_iTimeOffset)),
                                    'notification_active'	=> true];
        return true;
    }

    /**
     * count active notification subscriptions for user
     *
     * @return int amount of active notification subscriptions
     */
    public function countNotifications(): int
    {
        if ($objResultSet = cDB::getInstance()->executeQuery("SELECT count(*) AS notifcount FROM pxm_message_notification WHERE mn_userid=$this->m_iUserId")) {
            if ($objResultRow = $objResultSet->getNextResultRowObject()) {
                return (int) $objResultRow->notifcount;
            }
        }
        return 0;
    }
}
