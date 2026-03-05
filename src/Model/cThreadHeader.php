<?php

require_once(SRCDIR . '/Model/cMessageHeader.php');
/**
 * threadheader handling
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cThreadHeader extends cMessageHeader
{
    protected int $m_iBoardId = 0;					// board id
    protected int $m_iThreadId = 0;					// thread id
    protected bool $m_bIsActive = true;				// thread status
    protected bool $m_bIsFixed = false;				// is the thread fixed on top of the threadlist?
    protected int $m_iLastMessageId = 0;			// last message id
    protected int $m_iLastMessageTimestamp = 0;		// last message timestamp
    protected int $m_iMessageQuantity = 0;			// quantity of messages in this thread
    protected int $m_iViews = 0;					// views for this thread
    protected bool $m_bThreadMsgRead = false;		// is thread message read (for logged-in users)?
    protected bool $m_bLastMsgRead = false;			// is last message read (for logged-in users)?

    /**
     * initalize the member variables with the resultset from the db
     *
     * @param object $objResultRow resultrow from db query
     * @return bool success / failure
     */
    protected function _setDataFromDb(object $objResultRow): bool
    {
        cMessageHeader::_setDataFromDb($objResultRow);

        $this->m_iThreadId = (int) $objResultRow->t_id;
        $this->m_bIsActive = (bool) $objResultRow->t_active;
        $this->m_iLastMessageId = (int) $objResultRow->t_lastmsgid;
        $this->m_iLastMessageTimestamp = (int) $objResultRow->t_lastmsgtstmp;
        $this->m_iMessageQuantity = (int) $objResultRow->t_msgquantity;
        $this->m_iViews = (int) $objResultRow->t_views;
        $this->m_bIsFixed = (bool) $objResultRow->t_fixed;

        return true;
    }

    /**
     * get additional database attributes for this object (template method)
     *
     * @return string additional database attributes for this object
     */
    protected function _getDbAttributes(): string
    {
        return cMessageHeader::_getDbAttributes().',t_id,t_active,t_boardid,t_lastmsgid,t_lastmsgtstmp,t_msgquantity,t_views,t_fixed';
    }

    /**
     * get additional database tables for this object (template method)
     *
     * @return string additional database tables for this object
     */
    protected function _getDbTables(): string
    {
        return cMessageHeader::_getDbTables().',pxm_thread';
    }

    /**
     * get additional database tables for this object (template method)
     *
     * @return string additional database join for this object
     */
    protected function _getDbJoin(): string
    {
        return cMessageHeader::_getDbJoin().' AND t_id=m_threadid';
    }

    /**
     * get board id
     *
     * @return int board id
     */
    public function getBoardId(): int
    {
        return $this->m_iBoardId;
    }

    /**
     * set board id
     *
     * @param int $iBoardId board id
     * @return void
     */
    public function setBoardId(int $iBoardId): void
    {
        $this->m_iBoardId = $iBoardId;
    }

    /**
     * get thread id
     *
     * @return int thread id
     */
    public function getThreadId(): int
    {
        return $this->m_iThreadId;
    }

    /**
     * set thread id
     *
     * @param int $iThreadId thread id
     * @return void
     */
    public function setThreadId(int $iThreadId): void
    {
        $this->m_iThreadId = $iThreadId;
    }

    /**
     * is the thread active?
     *
     * @return bool is active / is not active
     */
    public function isThreadActive(): bool
    {
        return $this->m_bIsActive;
    }

    /**
     * set thread is active
     *
     * @param bool $bIsActive is the thread active
     * @return void
     */
    public function setThreadActive(bool $bIsActive): void
    {
        $this->m_bIsActive = $bIsActive;
    }

    /**
     * is the thread fixed on top of the threadlist?
     *
     * @return bool is the thread fixed on top of the threadlist?
     */
    public function isThreadFixed(): bool
    {
        return $this->m_bIsFixed;
    }

    /**
     * set whether the thread is fixed on top of the threadlist or not?
     *
     * @param bool $bIsFixed is the thread fixed on top of the threadlist?
     * @return void
     */
    public function setIsThreadFixed(bool $bIsFixed): void
    {
        $this->m_bIsFixed = $bIsFixed;
    }

    /**
     * get last message id
     *
     * @return int last message id
     */
    public function getLastMessageId(): int
    {
        return $this->m_iLastMessageId;
    }

    /**
     * set last message id
     *
     * @param int $iLastMessageId last message id
     * @return void
     */
    public function setLastMessageId(int $iLastMessageId): void
    {
        $this->m_iLastMessageId = $iLastMessageId;
    }

    /**
     * get last message timestamp
     *
     * @return int last message timestamp
     */
    public function getLastMessageTimestamp(): int
    {
        return $this->m_iLastMessageTimestamp;
    }

    /**
     * set last message timestamp
     *
     * @param int $iLastMessageTimestamp last message timestamp
     * @return void
     */
    public function setLastMessageTimestamp(int $iLastMessageTimestamp): void
    {
        $this->m_iLastMessageTimestamp = $iLastMessageTimestamp;
    }

    /**
     * get message quantity
     *
     * @return int message quantity
     */
    public function getMessageQuantity(): int
    {
        return $this->m_iMessageQuantity;
    }

    /**
     * set message quantity
     *
     * @param int $iMessageQuantity message quantity
     * @return void
     */
    public function setMessageQuantity(int $iMessageQuantity): void
    {
        $this->m_iMessageQuantity = $iMessageQuantity;
    }

    /**
     * get views
     *
     * @return int views
     */
    public function getViews(): int
    {
        return $this->m_iViews;
    }

    /**
     * set message quantity
     *
     * @param int $iViews views
     * @return void
     */
    public function setViews(int $iViews): void
    {
        $this->m_iViews = $iViews;
    }

    /**
     * is thread message read?
     *
     * @return bool is thread message read
     */
    public function isThreadMsgRead(): bool
    {
        return $this->m_bThreadMsgRead;
    }

    /**
     * set thread message read status
     *
     * @param bool $bThreadMsgRead is thread message read
     * @return void
     */
    public function setThreadMsgRead(bool $bThreadMsgRead): void
    {
        $this->m_bThreadMsgRead = $bThreadMsgRead;
    }

    /**
     * is last message read?
     *
     * @return bool is last message read
     */
    public function isLastMsgRead(): bool
    {
        return $this->m_bLastMsgRead;
    }

    /**
     * set last message read status
     *
     * @param bool $bLastMsgRead is last message read
     * @return void
     */
    public function setLastMsgRead(bool $bLastMsgRead): void
    {
        $this->m_bLastMsgRead = $bLastMsgRead;
    }

    /**
     * get membervariables as array
     *
     * @param int $iTimeOffset time offset in seconds
     * @param string $sDateFormat php date format
     * @param int $iLastOnlineTimestamp last online timestamp for user
     * @param string $sSubjectQuotePrefix quote prefix for a message subject
     * @param ?cParser $objParser message parser
     * @return array<string, mixed> member variables
     */
    public function getDataArray(int $iTimeOffset = 0, string $sDateFormat = '', int $iLastOnlineTimestamp = 0, string $sSubjectQuotePrefix = '', ?cParser $objParser = null): array
    {
        // TODO: Vererbung mit unterschiedlicher Methodensignatur optimieren
        return array_merge(
            cMessageHeader::getDataArray($iTimeOffset, $sDateFormat, $iLastOnlineTimestamp, '', $objParser),
            ['threadid'	=>	$this->m_iThreadId,
            'active'	=>	(int) $this->m_bIsActive,
            'views'	    =>	(string) $this->m_iViews,
            'fixed'	    =>	(int) $this->m_bIsFixed,
            'lastid'	=>	$this->m_iLastMessageId,
            'lastdate'	=>	(($this->m_iLastMessageTimestamp > $this->m_iMessageTimestamp) ? date($sDateFormat, ($this->m_iLastMessageTimestamp + $iTimeOffset)) : 0),
            'lastnew'	=>	(($iLastOnlineTimestamp > $this->m_iLastMessageTimestamp) ? 0 : 1),
            'msgquan'	=>	(string) $this->m_iMessageQuantity,
            'thread_msg_read'	=>	(int) $this->m_bThreadMsgRead,
            'last_msg_read'	    =>	(int) $this->m_bLastMsgRead]
        );
    }
}
