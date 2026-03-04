<?php

require_once(SRCDIR . '/Model/cScrollList.php');
/**
 * Message search list handling
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cMessageSearchList extends cScrollList
{
    //TODO make this work for postgres

    protected array $m_arrBoardIds;			// board ids
    protected string $m_sUserName;			// username
    protected string $m_sSearchString;		// search string
    protected int $m_iSearchDays;			// timespan of the search (last x days)
    protected int $m_iSearchTimestamp;		// timestamp of this search
    protected int $m_iTimeOffset;			// time offset
    protected string $m_sDateFormat;		// date format
    protected int $m_iCurrentUserId;		// current user id (for draft visibility)
    protected bool $m_bGroupByThread;		// group results by thread

    /**
     * Constructor
     *
     * @param object $objSearch search config
     * @param int $iTimeOffset time offset
     * @param string $sDateFormat date format
     * @param int $iCurrentUserId current user id (for draft visibility)
     * @return void
     */
    public function __construct(object $objSearch, int $iTimeOffset, string $sDateFormat, int $iCurrentUserId = 0)
    {

        $this->m_arrBoardIds = [];
        foreach ($objSearch->getBoardIds() as $iBoardId) {
            $iBoardId = intval($iBoardId);
            if ($iBoardId > 0) {
                $this->m_arrBoardIds[] = $iBoardId;
            }
        }
        $this->m_sUserName = $objSearch->getSearchUser();
        $this->m_sSearchString = $objSearch->getSearchMessage();
        $this->m_iSearchDays = intval($objSearch->getSearchDays());
        $this->m_iSearchTimestamp = intval($objSearch->getTimestamp());
        $this->m_iTimeOffset = intval($iTimeOffset);
        $this->m_sDateFormat = $sDateFormat;
        $this->m_iCurrentUserId = intval($iCurrentUserId);
        $this->m_bGroupByThread = false;

        parent::__construct();
    }

    /**
     * Override parent loadData to accept grouping parameter
     *
     * @param int $iCurPageId page offset
     * @param int $iResultRowLimit quantity of entries that should be loaded
     * @param bool $bGroupByThread group results by thread
     * @return bool success / failure
     */
    public function loadData(int $iCurPageId, int $iResultRowLimit, bool $bGroupByThread = false): bool
    {
        $this->m_bGroupByThread = $bGroupByThread;
        return parent::loadData($iCurPageId, $iResultRowLimit);
    }

    /**
     * do the query initializaton stuff here
     *
     * @return void
     */
    protected function _doPreQuery(): void
    {

        require_once(SRCDIR . '/Search/cSearchEngineFactory.php');

        // Get search engine instance
        $objSearchEngine = cSearchEngineFactory::getInstance();

        // Execute search via search engine abstraction
        $objResultSet = $objSearchEngine->search(
            $this->m_sSearchString,
            $this->m_sUserName,
            $this->m_arrBoardIds,
            $this->m_iSearchDays,
            $this->m_iSearchTimestamp,
            $this->m_iTimeOffset,
            $this->m_iCurrentUserId,
            501  // +1 to detect overflow (500 limit)
        );

        // Create temporary table to store search results (maintains existing architecture)
        cDBFactory::getInstance()->executeQuery('CREATE TEMPORARY TABLE pxm_tmp_search (
			tmp_id INT PRIMARY KEY,
			tmp_score DECIMAL(10,2),
			tmp_tstmp INT,
			KEY idx_score_time (tmp_score, tmp_tstmp)
		) ENGINE=MEMORY');

        // Populate temporary table with search results
        $arrResults = $objResultSet->getResults();
        if (!empty($arrResults)) {
            $arrValues = [];
            foreach ($arrResults as $arrResult) {
                $arrValues[] = '(' . intval($arrResult['id']) . ',' .
                               floatval($arrResult['score']) . ',' .
                               intval($arrResult['timestamp']) . ')';
            }

            if (!empty($arrValues)) {
                $sInsertQuery = 'INSERT INTO pxm_tmp_search (tmp_id, tmp_score, tmp_tstmp) VALUES ' .
                                implode(',', $arrValues);
                cDBFactory::getInstance()->executeQuery($sInsertQuery);
            }
        }
    }

    /**
     * get the query.
     *
     * @return string query
     */
    protected function _getQuery(): string
    {
        return 'SELECT m_id,'.
                    'm_threadid,'.
                    't_boardid,'.
                    'm_subject,'.
                    'm_userid,'.
                    'm_username,'.
                    'm_userhighlight,'.
                    'tmp_score,'.
                    'tmp_tstmp '.
                    'FROM pxm_tmp_search tmp,pxm_message,pxm_thread '.
                    'WHERE m_id=tmp_id AND t_id=m_threadid '.
                    'ORDER BY tmp_score DESC,tmp_tstmp DESC';
    }

    /**
     * do the query shutdown stuff here
     *
     * @return void
     */
    protected function _doPostQuery(): void
    {

        if ($objResultSet = cDBFactory::getInstance()->executeQuery('SELECT count(*) AS cou FROM pxm_tmp_search')) {
            if ($objResultRow = $objResultSet->getNextResultRowObject()) {
                $this->m_iItemCount = $objResultRow->cou;
            }
            $objResultSet->freeResult();
        }

        // If grouping by thread, load root messages and reorganize data
        if ($this->m_bGroupByThread && !empty($this->m_arrResultList)) {
            $this->_groupResultsByThread();
        }

        cDBFactory::getInstance()->executeQuery('DROP TABLE pxm_tmp_search');
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
                                         'boardid'	=> $objResultRow->t_boardid,
                                         'subject'	=> $objResultRow->m_subject,
                                         'score'	=> $objResultRow->tmp_score,
                                         'date'		=> (($objResultRow->tmp_tstmp > 0) ? date($this->m_sDateFormat, ($objResultRow->tmp_tstmp + $this->m_iTimeOffset)) : 0),
                                         'user'		=> ['id'		=> $objResultRow->m_userid,
                                                            'username'	=> $objResultRow->m_username,
                                                            'highlight'	=> $objResultRow->m_userhighlight]];
        return true;
    }

    /**
     * Group search results by thread and load root messages
     *
     * @return void
     */
    protected function _groupResultsByThread(): void
    {
        // Extract unique thread IDs
        $arrThreadIds = [];
        foreach ($this->m_arrResultList as $arrMessage) {
            if (!in_array($arrMessage['threadid'], $arrThreadIds)) {
                $arrThreadIds[] = intval($arrMessage['threadid']);
            }
        }

        if (empty($arrThreadIds)) {
            return;
        }

        // Load all root messages in a single query (performance-critical!)
        $arrRootMessages = [];
        $sQuery = 'SELECT m_id, m_threadid, m_subject, m_userid, m_username, m_userhighlight, m_tstmp '.
                  'FROM pxm_message '.
                  'WHERE m_parentid = 0 AND m_threadid IN ('.implode(',', $arrThreadIds).')';

        if ($objResultSet = cDBFactory::getInstance()->executeQuery($sQuery)) {
            while ($objRow = $objResultSet->getNextResultRowObject()) {
                $arrRootMessages[intval($objRow->m_threadid)] = [
                    'id'		=> $objRow->m_id,
                    'threadid'	=> $objRow->m_threadid,
                    'subject'	=> $objRow->m_subject,
                    'date'		=> (($objRow->m_tstmp > 0) ? date($this->m_sDateFormat, ($objRow->m_tstmp + $this->m_iTimeOffset)) : 0),
                    'user'		=> [
                        'id'		=> $objRow->m_userid,
                        'username'	=> $objRow->m_username,
                        'highlight'	=> $objRow->m_userhighlight
                    ]
                ];
            }
            $objResultSet->freeResult();
        }

        // Reorganize results by thread
        $arrGrouped = [];
        foreach ($this->m_arrResultList as $arrMessage) {
            $iThreadId = intval($arrMessage['threadid']);
            if (!isset($arrGrouped[$iThreadId])) {
                $arrGrouped[$iThreadId] = [
                    'threadid'		=> $iThreadId,
                    'root_message'	=> $arrRootMessages[$iThreadId] ?? null,
                    'messages'		=> []
                ];
            }
            $arrGrouped[$iThreadId]['messages'][] = $arrMessage;
        }

        // Convert to indexed array (preserve order by first occurrence)
        $this->m_arrResultList = array_values($arrGrouped);
    }
}
