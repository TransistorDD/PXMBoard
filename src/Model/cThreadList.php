<?php

namespace PXMBoard\Model;

use PXMBoard\Database\cDB;
use PXMBoard\Enum\eMessageStatus;

/**
 * threadlist handling
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cThreadList extends cScrollList
{
    protected int $m_iBoardId;			// board id
    protected string $m_sSortMode;		// sort mode
    protected string $m_sSortDirection;	// sort direction
    protected int $m_iTimeSpan;			// timespan
    protected int $m_iUserId;			// user id (for read tracking)
    protected int $m_iReadRetentionMonths;	// read tracking retention in months

    /**
     * Constructor
     *
     * @param int $iBoardId board id
     * @param string $sSortMode sort mode
     * @param int $iTimeSpan timespan
     * @param int $iUserId user id (for read tracking, 0 for guests)
     * @param int $iReadRetentionMonths read tracking retention in months (from cConfig)
     * @return void
     */
    public function __construct(int $iBoardId, string $sSortMode, int $iTimeSpan, int $iUserId = 0, int $iReadRetentionMonths = 13)
    {
        parent::__construct();

        $this->m_sSortDirection = 'DESC';

        switch ($sSortMode) {
            case 'thread': 	$this->m_sSortMode = 'm_tstmp';
                break;
            case 'last': 	$this->m_sSortMode = 't_lastmsgtstmp';
                break;
            case 'subject': $this->m_sSortMode = 'm_subject';
                $this->m_sSortDirection = 'ASC';
                break;
            case 'username':$this->m_sSortMode = 'm_username';
                $this->m_sSortDirection = 'ASC';
                break;
            case 'views':	$this->m_sSortMode = 't_views';
                break;
            case 'replies':	$this->m_sSortMode = 't_msgquantity';
                break;
            default: 		$this->m_sSortMode = 'm_tstmp';
                break;
        }
        $this->m_iBoardId = $iBoardId;
        $this->m_iTimeSpan = $iTimeSpan;
        $this->m_iUserId = $iUserId;
        $this->m_iReadRetentionMonths = $iReadRetentionMonths;
    }

    /**
     * get the query
     *
     * @return string query
     */
    protected function _getQuery(): string
    {
        $objDb = cDB::getInstance();

        // For logged-in users: add read status via correlated EXISTS subqueries.
        // Using EXISTS (not LEFT JOIN) is mandatory: the new PRIMARY KEY includes
        // mr_year_month, so a JOIN would produce one row per partition month each
        // user has a read record in. mr_year_month >= cutoff enables partition pruning.
        $sReadSelectThreadMsg = '';
        $sReadSelectLastMsg = '';

        if ($this->m_iUserId > 0) {
            $iUserId = (int) $this->m_iUserId;
            // Cutoff keeps the EXISTS within live partitions and enables partition pruning
            $iCutoffYearMonth = (int) date('ym', strtotime('-' . $this->m_iReadRetentionMonths . ' months'));

            $sReadSelectThreadMsg = ', (EXISTS ('
                . 'SELECT 1 FROM pxm_message_read'
                . ' WHERE mr_messageid = pxm_message.m_id'
                . ' AND mr_userid = ' . $iUserId
                . ' AND mr_year_month >= ' . $iCutoffYearMonth
                . ')) AS thread_msg_read';

            $sReadSelectLastMsg = ', (EXISTS ('
                . 'SELECT 1 FROM pxm_message_read'
                . ' WHERE mr_messageid = pxm_thread.t_lastmsgid'
                . ' AND mr_userid = ' . $iUserId
                . ' AND mr_year_month >= ' . $iCutoffYearMonth
                . ')) AS last_msg_read';
        }

        $sStatusFilter = '(m_status='.eMessageStatus::PUBLISHED->value.' OR (m_status='.eMessageStatus::DRAFT->value.' AND m_userid='.$this->m_iUserId.'))';

        return 'SELECT    m_id,'
                    .'m_subject,'
                    .'m_tstmp,'
                    .'m_threadid,'
                    .'t_active,'
                    .'t_lastmsgid,'
                    .'t_lastmsgtstmp,'
                    .'t_msgquantity,'
                    .'t_views,'
                    .'t_fixed,'
                    .'m_userid,'
                    .'m_username,'
                    .'m_userhighlight'
                    .$sReadSelectThreadMsg
                    .$sReadSelectLastMsg
            .' FROM   pxm_thread'
            .' INNER JOIN pxm_message ON t_id=m_threadid'
            .' WHERE  m_parentid=0'
            .' AND 	  t_boardid='.$this->m_iBoardId
            .' AND 	  (t_lastmsgtstmp>'.$this->m_iTimeSpan.' OR t_fixed=1)'
            .' AND    '.$sStatusFilter
            .' ORDER BY t_fixed DESC,'.$this->m_sSortMode
            .' '.$this->m_sSortDirection;
    }

    /**
     * initalize the member variables with the resultrow from the db
     *
     * @param object $objResultRow resultrow from db query
     * @return bool success / failure
     */
    protected function _setDataFromDb(object $objResultRow): bool
    {
        $objThreadHeader = new cThreadHeader();
        $objThreadHeader->setId($objResultRow->m_id);
        $objThreadHeader->setSubject($objResultRow->m_subject);
        $objThreadHeader->setMessageTimestamp($objResultRow->m_tstmp);
        $objThreadHeader->setThreadId($objResultRow->m_threadid);
        $objThreadHeader->setThreadActive($objResultRow->t_active);
        $objThreadHeader->setLastMessageId($objResultRow->t_lastmsgid);
        $objThreadHeader->setLastMessageTimestamp($objResultRow->t_lastmsgtstmp);
        $objThreadHeader->setMessageQuantity($objResultRow->t_msgquantity);
        $objThreadHeader->setViews($objResultRow->t_views);
        $objThreadHeader->setIsThreadFixed($objResultRow->t_fixed);

        $objThreadHeader->getAuthor()->setId($objResultRow->m_userid);
        $objThreadHeader->getAuthor()->setUserName($objResultRow->m_username);
        $objThreadHeader->getAuthor()->setHighlightUser($objResultRow->m_userhighlight);

        // Set read status for logged-in users
        if ($this->m_iUserId > 0) {
            $objThreadHeader->setThreadMsgRead(isset($objResultRow->thread_msg_read) ? (bool)$objResultRow->thread_msg_read : false);
            $objThreadHeader->setLastMsgRead(isset($objResultRow->last_msg_read) ? (bool)$objResultRow->last_msg_read : false);
        }

        $this->m_arrResultList[] = $objThreadHeader;
        return true;
    }

    /**
     * get membervariables as array
     *
     * @param int $iTimeOffset time offset in seconds
     * @param string $sDateFormat php date format
     * @param int $iLastLoginTimestamp last login timestamp for user
     * @return list<array<string, mixed>> member variables
     */
    public function getDataArray(int $iTimeOffset = 0, string $sDateFormat = '', int $iLastLoginTimestamp = 0): array
    {
        // TODO: Vererbung mit unterschiedlicher Methodensignatur optimieren
        $arrOutput = [];
        foreach ($this->m_arrResultList as $objThreadHeader) {
            $arrOutput[] = $objThreadHeader->getDataArray($iTimeOffset, $sDateFormat, $iLastLoginTimestamp);
        }
        return $arrOutput;
    }
}
