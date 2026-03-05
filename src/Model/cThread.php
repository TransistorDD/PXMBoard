<?php

require_once(SRCDIR . '/Model/cMessageHeader.php');
/**
 * thread handling
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cThread
{
    protected int $m_iBoardId = 0;					// board id
    protected int $m_iId = 0;						// thread id
    protected bool $m_bIsActive = false;			// thread status
    protected bool $m_bIsFixed = false;				// is the thread fixed on top of the threadlist?
    protected int $m_iLastMessageId = 0;			// last message id
    protected int $m_iLastMessageTimestamp = 0;		// last message timestamp
    protected int $m_iMessageQuantity = 0;			// quantity of messages in this thread
    protected int $m_iViews = 0;					// views for this thread
    /** @var array<mixed> */
    protected array $m_arrThreadMessages = [];		// message headers of the thread

    /**
     * get data from database by thread and board id
     *
     * @param int $iThreadId thread id
     * @param int $iBoardId board id (will be checked for more security)
     * @return bool success / failure
     */
    public function loadDataById(int $iThreadId, int $iBoardId): bool
    {
        $bReturn = false;

        if ($iThreadId > 0) {
            if ($objResultSet = cDBFactory::getInstance()->executeQuery('SELECT t_boardid,'.
                                                        't_id,'.
                                                        't_active,'.
                                                        't_fixed,'.
                                                        't_lastmsgid,'.
                                                        't_lastmsgtstmp,'.
                                                        't_msgquantity,'.
                                                        't_views'.
                                                        ' FROM pxm_thread'.
                                                        ' WHERE t_id='.$iThreadId.' AND t_boardid='.$iBoardId)) {
                if ($objResultRow = $objResultSet->getNextResultRowObject()) {
                    $bReturn = $this->_setDataFromDb($objResultRow);
                }
                $objResultSet->freeResult();
                unset($objResultSet);
            }
        }
        return $bReturn;
    }

    /** initalize the member variables with the resultset from the db
     *
     * @param object $objResultRow resultrow from db query
     * @return bool success / failure
     */
    protected function _setDataFromDb(object $objResultRow): bool
    {
        $this->m_iBoardId = (int) $objResultRow->t_boardid;
        $this->m_iId = (int) $objResultRow->t_id;
        $this->m_bIsActive = (bool) $objResultRow->t_active;
        $this->m_iLastMessageId = (int) $objResultRow->t_lastmsgid;
        $this->m_iLastMessageTimestamp = (int) $objResultRow->t_lastmsgtstmp;
        $this->m_iMessageQuantity = (int) $objResultRow->t_msgquantity;
        $this->m_iViews = (int) $objResultRow->t_views;
        $this->m_bIsFixed = (bool) $objResultRow->t_fixed;

        return true;
    }

    /**
     * is the thread active?
     *
     * @return bool is the thread active?
     */
    public function isActive(): bool
    {
        return $this->m_bIsActive;
    }

    /**
     * get thread id
     *
     * @return int thread id
     */
    public function getId(): int
    {
        return $this->m_iId;
    }

    /**
     *  change status of the thread (open / closed)
     *
     * @param bool $bIsActive should the thread be activated?
     * @return bool success / failure
     */
    public function updateIsActive(bool $bIsActive): bool
    {
        if (!cDBFactory::getInstance()->executeQuery('UPDATE pxm_thread SET t_active='.intval($bIsActive)." WHERE t_id=$this->m_iId")) {
            return false;
        }
        $this->m_bIsActive = $bIsActive ? true : false;
        return true;
    }

    /**
     * is the thread fixed on top of the threadlist?
     *
     * @return bool is the thread fixed on top of the threadlist?
     */
    public function isFixed(): bool
    {
        return $this->m_bIsFixed;
    }

    /**
     * set whether the thread is fixed on top of the threadlist or not?
     *
     * @param bool $bIsFixed is the thread fixed on top of the threadlist?
     * @return bool success / failure
     */
    public function updateIsFixed(bool $bIsFixed): bool
    {
        if (!cDBFactory::getInstance()->executeQuery('UPDATE pxm_thread SET t_fixed='.intval($bIsFixed)." WHERE t_id=$this->m_iId")) {
            return false;
        }
        $this->m_bIsFixed = $bIsFixed ? true : false;
        return true;
    }

    /**
     * delete data from database
     *
     * @return bool success / failure
     */
    public function deleteData(): bool
    {
        if (cDBFactory::getInstance()->executeQuery("DELETE FROM pxm_message WHERE m_threadid=$this->m_iId")
            && cDBFactory::getInstance()->executeQuery("DELETE FROM pxm_thread WHERE t_id=$this->m_iId")) {

            return true;
        }
        return false;
    }

    /**
     * delete a subthread
     *
     * @param int $iMessageId id of the start message
     * @return bool success / failure
     */
    public function deleteSubThread(int $iMessageId): bool
    {
        $bReturn = false;
        $bClosed = false;
        if ($this->m_bIsActive) {
            $bClosed = true;
            $this->updateIsActive(false);
        }

        // fetch additional data
        $this->m_arrThreadMessages = $this->getThreadMessageIdArray();

        if (isset($this->m_arrThreadMessages[0]) && !in_array($iMessageId, $this->m_arrThreadMessages[0])) {// root message not allowed


            $this->deleteSubThreadRecursive($iMessageId);

            cDBFactory::getInstance()->executeQuery('DELETE FROM pxm_message WHERE m_id='.$iMessageId);

            $this->updateThreadInformation($this->m_iId);
            $bReturn = true;
        }
        if ($bClosed) {
            $this->updateIsActive(true);
        }
        return $bReturn;
    }

    /**
     * Delete a message tree - automatically detects if it's a full thread or subthread
     * If the message is the root message, the entire thread is deleted.
     * Otherwise, only the subtree starting from this message is deleted.
     *
     * Root message detection: message ID == thread ID (first message gets same ID as thread)
     *
     * @param int $iMessageId id of the message to delete (with all children)
     * @return bool success / failure
     */
    public function deleteMessageTree(int $iMessageId): bool
    {
        if ($iMessageId <= 0) {
            return false;
        }

        // Check if this is the root message (message ID == thread ID)
        if ($iMessageId == $this->m_iId) {
            // Root message - delete entire thread
            return $this->deleteData();
        } else {
            // Not root message - delete subtree only
            return $this->deleteSubThread($iMessageId);
        }
    }

    /**
     * extract a subthread
     *
     * @param int $iMessageId id of the start message
     * @return bool success / failure
     */
    public function extractSubThread(int $iMessageId): bool
    {
        $bReturn = false;
        $bClosed = false;
        if ($this->m_bIsActive) {
            $bClosed = true;
            $this->updateIsActive(false);
        }

        $this->m_arrThreadMessages = $this->getThreadMessageIdArray();

        if (isset($this->m_arrThreadMessages[0]) && !in_array($iMessageId, $this->m_arrThreadMessages[0])) {// root message not allowed
            if (cDBFactory::getInstance()->executeQuery("INSERT INTO pxm_thread (t_boardid,t_active,t_lastmsgtstmp) VALUES ($this->m_iBoardId,1,0)")) {
                if (($iNewThreadId = cDBFactory::getInstance()->getInsertId('pxm_thread', 't_id')) > 0) {

                    cDBFactory::getInstance()->executeQuery('UPDATE pxm_message SET m_threadid='.intval($iNewThreadId).',m_parentid=0 WHERE m_id='.$iMessageId);

                    $this->moveSubThreadRecursive($iMessageId, $iNewThreadId);

                    $this->updateThreadInformation($iNewThreadId);
                    $this->updateThreadInformation($this->m_iId);
                    $bReturn = true;
                }
            }
        }
        if ($bClosed) {
            $this->updateIsActive(true);
        }
        return $bReturn;
    }

    /**
     * get the ids of the messages in this thread
     *
     * @return array<int, list<int>> ids of the messages in this thread
     */
    public function getThreadMessageIdArray(): array
    {
        $arrThreadMessageIds = [];
        if ($objResultSet = cDBFactory::getInstance()->executeQuery("SELECT m_id,m_parentid FROM pxm_message WHERE m_threadid=$this->m_iId")) {
            while ($objResultRow = $objResultSet->getNextResultRowObject()) {
                $arrThreadMessageIds[(int) $objResultRow->m_parentid][] = (int) $objResultRow->m_id;
            }
        }
        return $arrThreadMessageIds;
    }

    /**
     * delete a subthread (internal helper method)
     *
     * @param int $iMessageId id of the start message
     * @return bool success / failure
     */
    private function deleteSubThreadRecursive(int $iMessageId): bool
    {
        if (isset($this->m_arrThreadMessages[$iMessageId])) {
            foreach ($this->m_arrThreadMessages[$iMessageId] as $iSubMessageId) {
                $this->deleteSubThreadRecursive($iSubMessageId);
            }
            cDBFactory::getInstance()->executeQuery("DELETE FROM pxm_message WHERE m_threadid=$this->m_iId AND m_parentid=".$iMessageId);
        }
        return true;
    }

    /**
     * move a subthread (internal helper method)
     *
     * @param int $iMessageId id of the start message
     * @param int $iNewThreadId id of the new thread
     * @return bool success / failure
     */
    private function moveSubThreadRecursive(int $iMessageId, int $iNewThreadId): bool
    {
        if (isset($this->m_arrThreadMessages[$iMessageId])) {
            foreach ($this->m_arrThreadMessages[$iMessageId] as $iSubMessageId) {
                $this->moveSubThreadRecursive($iSubMessageId, $iNewThreadId);
            }
            cDBFactory::getInstance()->executeQuery("UPDATE pxm_message SET m_threadid=$iNewThreadId WHERE m_threadid=$this->m_iId AND m_parentid=".$iMessageId);
        }
        return true;
    }

    /**
     * recalculate the thread information (last message, views etc.) and update the database
     *
     * @param int $iThreadId id of the thread
     * @return bool success / failure
     */
    private function updateThreadInformation(int $iThreadId): bool
    {
        if ($objResultSet = cDBFactory::getInstance()->executeQuery("SELECT count(*) AS count,MAX(m_tstmp) AS maxd,MAX(m_id) AS maxid FROM pxm_message WHERE m_threadid=$iThreadId")) {
            if ($objResultRow = $objResultSet->getNextResultRowObject()) {
                cDBFactory::getInstance()->executeQuery("UPDATE pxm_thread SET t_msgquantity=$objResultRow->count-1,t_lastmsgid=$objResultRow->maxid,t_lastmsgtstmp=$objResultRow->maxd WHERE t_id=$iThreadId");
                return true;
            }
        }
        return false;
    }

    /**
     * delete data from database
     *
     * @param int $iDestinationBoardId destination board id
     * @return bool success / failure
     */
    public function moveThread(int $iDestinationBoardId): bool
    {
        $bReturn = false;

        if ($iDestinationBoardId > 0) {
            if ($objResultSet = cDBFactory::getInstance()->executeQuery("UPDATE pxm_thread SET t_boardid=$iDestinationBoardId WHERE t_id=$this->m_iId")) {
                if ($objResultSet->getAffectedRows() > 0) {
                    $bReturn = true;
                }
            }
        }
        return $bReturn;
    }

    /**
     * get membervariables as array
     *
     * @param int $iTimeOffset time offset in seconds
     * @param string $sDateFormat php date format
     * @param int $iLastOnlineTimestamp last online timestamp for user
     * @param int $iCurrentUserId current user id for draft visibility (0 = guest)
     * @return array<string, mixed> member variables
     */
    public function getDataArray(int $iTimeOffset, string $sDateFormat, int $iLastOnlineTimestamp, int $iCurrentUserId = 0): array
    {
        require_once(SRCDIR . '/Enum/eMessageStatus.php');

        if ($this->m_iId > 0) {
            $sStatusFilter = '(m_status='.eMessageStatus::PUBLISHED->value.' OR (m_status='.eMessageStatus::DRAFT->value.' AND m_userid='.$iCurrentUserId.'))';
            if ($objResultSet = cDBFactory::getInstance()->executeQuery("SELECT m_id,m_parentid,m_subject,m_tstmp,m_userid,m_username,m_userhighlight,m_status FROM pxm_message WHERE m_threadid=$this->m_iId AND ".$sStatusFilter.' ORDER BY m_tstmp DESC')) {

                $objParser = null;	// message parser not needed

                $objMessageHeader = new cMessageHeader();
                $this->m_arrThreadMessages = [];
                while ($objResultRow = $objResultSet->getNextResultRowObject()) {

                    $objMessageHeader->setId($objResultRow->m_id);
                    $objMessageHeader->setSubject($objResultRow->m_subject);
                    $objMessageHeader->setMessageTimestamp($objResultRow->m_tstmp);
                    $objMessageHeader->setAuthorId($objResultRow->m_userid);
                    $objMessageHeader->setAuthorUserName($objResultRow->m_username);
                    $objMessageHeader->setAuthorHighlightUser($objResultRow->m_userhighlight);
                    $objMessageHeader->setStatus(eMessageStatus::tryFrom((int) $objResultRow->m_status) ?? eMessageStatus::PUBLISHED);

                    $this->m_arrThreadMessages[$objResultRow->m_parentid][]	= $objMessageHeader->getDataArray($iTimeOffset, $sDateFormat, $iLastOnlineTimestamp, '', $objParser);
                }

                if (sizeof($this->m_arrThreadMessages) > 0) {

                    $objResultSet->freeResult();
                    unset($objResultSet);

                    // increment thread view count
                    cDBFactory::getInstance()->executeQuery("UPDATE pxm_thread SET t_views=t_views+1 WHERE t_id=$this->m_iId");
                    ++$this->m_iViews;
                    return ['id'	=>	$this->m_iId,
                            'active' =>	$this->m_bIsActive,
                            'fixed'	=>	$this->m_bIsFixed,
                            'views'	=>	$this->m_iViews,
                            'msg'	=>	$this->getMessageTreeArray(0)];
                }
            }
        }
        return ['id'	=>	$this->m_iId,
                'active' =>	$this->m_bIsActive,
                'fixed'	=>	$this->m_bIsFixed,
                'views'	=>	$this->m_iViews];
    }

    /**
     * build the message header tree
     *
     * @param int $iParentId parent id
     * @param string $sTreeLines image chars
     * @return array<mixed> message header tree
     */
    private function getMessageTreeArray(int $iParentId, string $sTreeLines = ''): array
    {
        $arrReturn = [];
        if (isset($this->m_arrThreadMessages[$iParentId]) && is_array($this->m_arrThreadMessages[$iParentId]) && ($iLevelArraySize = sizeof($this->m_arrThreadMessages[$iParentId])) > 0) {		//if there is at least one answer to parent message
            for ($iMessagePointer = 0; $iMessagePointer < $iLevelArraySize; $iMessagePointer++) {//recursive call to getMsgTreeArray for every answer
                if ($iMessagePointer < $iLevelArraySize - 1) {	//if it is not the last answer...
                    $this->m_arrThreadMessages[$iParentId][$iMessagePointer] = array_merge($this->m_arrThreadMessages[$iParentId][$iMessagePointer], ['tree_lines' => $sTreeLines.'├']);

                    if ($arrChildren = $this->getMessageTreeArray($this->m_arrThreadMessages[$iParentId][$iMessagePointer]['id'], $sTreeLines.'│')) {
                        $this->m_arrThreadMessages[$iParentId][$iMessagePointer] = array_merge($this->m_arrThreadMessages[$iParentId][$iMessagePointer], ['msg' => $arrChildren]);
                    }
                } else {										//...else draw gif for endpart
                    if ($iParentId > 0) {
                        $this->m_arrThreadMessages[$iParentId][$iMessagePointer] = array_merge($this->m_arrThreadMessages[$iParentId][$iMessagePointer], ['tree_lines' => $sTreeLines.'└']);
                    }
                    if ($arrChildren = $this->getMessageTreeArray($this->m_arrThreadMessages[$iParentId][$iMessagePointer]['id'], $sTreeLines.' ')) {
                        $this->m_arrThreadMessages[$iParentId][$iMessagePointer] = array_merge($this->m_arrThreadMessages[$iParentId][$iMessagePointer], ['msg' => $arrChildren]);
                    }
                }
            }
            $arrReturn = $this->m_arrThreadMessages[$iParentId];
        }
        return $arrReturn;
    }
}
