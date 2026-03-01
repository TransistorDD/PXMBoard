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
class cThreadHeader extends cMessageHeader{

	protected int $m_iBoardId;					// board id
	protected int $m_iThreadId;					// thread id
	protected bool $m_bIsActive;					// thread status
	protected bool $m_bIsFixed;					// is the thread fixed on top of the threadlist?
	protected int $m_iLastMessageId;				// last message id
	protected int $m_iLastMessageTimestamp;		// last message timestamp
	protected int $m_iMessageQuantity;			// quantity of messages in this thread
	protected int $m_iViews;						// views for this thread
	protected bool $m_bThreadMsgRead;				// is thread message read (for logged-in users)?
	protected bool $m_bLastMsgRead;					// is last message read (for logged-in users)?

	/**
	 * Constructor
	 *
	 * @return void
	 */
	public function __construct(){

		parent::__construct();

		$this->m_iBoardId = 0;
		$this->m_iThreadId = 0;
		$this->m_bIsActive = false;
		$this->m_bIsFixed = false;
		$this->m_iLastMessageId = 0;
		$this->m_iLastMessageTimestamp = 0;
		$this->m_iMessageQuantity = 0;
		$this->m_iViews = 0;
		$this->m_bThreadMsgRead = false;
		$this->m_bLastMsgRead = false;
	}

	/**
	 * initalize the member variables with the resultset from the db
	 *
	 * @param object $objResultRow resultrow from db query
	 * @return boolean success / failure
	 */
	protected function _setDataFromDb($objResultRow): bool{

		cMessageHeader::_setDataFromDb($objResultRow);

		$this->m_iThreadId = intval($objResultRow->t_id);
		$this->m_bIsActive = $objResultRow->t_active?true:false;
		$this->m_iLastMessageId = intval($objResultRow->t_lastmsgid);
		$this->m_iLastMessageTimestamp = intval($objResultRow->t_lastmsgtstmp);
		$this->m_iMessageQuantity = intval($objResultRow->t_msgquantity);
		$this->m_iViews = intval($objResultRow->t_views);
		$this->m_bIsFixed = $objResultRow->t_fixed?true:false;

		return true;
	}

	/**
	 * get additional database attributes for this object (template method)
	 *
	 * @return string additional database attributes for this object
	 */
	 protected function _getDbAttributes(){
	 	return cMessageHeader::_getDbAttributes().",t_id,t_active,t_boardid,t_lastmsgid,t_lastmsgtstmp,t_msgquantity,t_views,t_fixed";
	 }

	/**
	 * get additional database tables for this object (template method)
	 *
	 * @return string additional database tables for this object
	 */
	 protected function _getDbTables(){
	 	return cMessageHeader::_getDbTables().",pxm_thread";
	 }

	/**
	 * get additional database tables for this object (template method)
	 *
	 * @return string additional database join for this object
	 */
	 protected function _getDbJoin(){
	 	return cMessageHeader::_getDbJoin()." AND t_id=m_threadid";
	 }

	/**
	 * get board id
	 *
	 * @return integer board id
	 */
	public function getBoardId(){
		return $this->m_iBoardId;
	}

	/**
	 * set board id
	 *
	 * @param integer $iBoardId board id
	 * @return void
	 */
	public function setBoardId($iBoardId){
		$this->m_iBoardId = intval($iBoardId);
	}

	/**
	 * get thread id
	 *
	 * @return integer thread id
	 */
	public function getThreadId(){
		return $this->m_iThreadId;
	}

	/**
	 * set thread id
	 *
	 * @param integer $iThreadId thread id
	 * @return void
	 */
	public function setThreadId($iThreadId){
		$this->m_iThreadId = intval($iThreadId);
	}

	/**
	 * is the thread active?
	 *
	 * @return boolean is active / is not active
	 */
	public function isThreadActive(){
		return $this->m_bIsActive;
	}

	/**
	 * set thread is active
	 *
	 * @param boolean $bIsActive is the thread active
	 * @return void
	 */
	public function setThreadActive($bIsActive){
		$this->m_bIsActive = $bIsActive?true:false;
	}

	/**
	 * is the thread fixed on top of the threadlist?
	 *
	 * @return boolean is the thread fixed on top of the threadlist?
	 */
	public function isThreadFixed(){
		return $this->m_bIsFixed;
	}

	/**
	 * set whether the thread is fixed on top of the threadlist or not?
	 *
	 * @param boolean $bIsFixed is the thread fixed on top of the threadlist?
	 * @return void
	 */
	public function setIsThreadFixed($bIsFixed){
		$this->m_bIsFixed = $bIsFixed?true:false;
	}

	/**
	 * get last message id
	 *
	 * @return integer last message id
	 */
	public function getLastMessageId(){
		return $this->m_iLastMessageId;
	}

	/**
	 * set last message id
	 *
	 * @param integer $iLastMessageId last message id
	 * @return void
	 */
	public function setLastMessageId($iLastMessageId){
		$this->m_iLastMessageId = intval($iLastMessageId);
	}

	/**
	 * get last message timestamp
	 *
	 * @return integer last message timestamp
	 */
	public function getLastMessageTimestamp(){
		return $this->m_iLastMessageTimestamp;
	}

	/**
	 * set last message timestamp
	 *
	 * @param integer $iLastMessageTimestamp last message timestamp
	 * @return void
	 */
	public function setLastMessageTimestamp($iLastMessageTimestamp){
		$this->m_iLastMessageTimestamp = intval($iLastMessageTimestamp);
	}

	/**
	 * get message quantity
	 *
	 * @return integer message quantity
	 */
	public function getMessageQuantity(){
		return $this->m_iMessageQuantity;
	}

	/**
	 * set message quantity
	 *
	 * @param integer $iMessageQuantity message quantity
	 * @return void
	 */
	public function setMessageQuantity($iMessageQuantity){
		$this->m_iMessageQuantity = intval($iMessageQuantity);
	}

	/**
	 * get views
	 *
	 * @return integer views
	 */
	public function getViews(){
		return $this->m_iViews;
	}

	/**
	 * set message quantity
	 *
	 * @param integer $iViews views
	 * @return void
	 */
	public function setViews($iViews){
		$this->m_iViews = intval($iViews);
	}

	/**
	 * is thread message read?
	 *
	 * @return boolean is thread message read
	 */
	public function isThreadMsgRead(){
		return $this->m_bThreadMsgRead;
	}

	/**
	 * set thread message read status
	 *
	 * @param boolean $bThreadMsgRead is thread message read
	 * @return void
	 */
	public function setThreadMsgRead($bThreadMsgRead){
		$this->m_bThreadMsgRead = $bThreadMsgRead?true:false;
	}

	/**
	 * is last message read?
	 *
	 * @return boolean is last message read
	 */
	public function isLastMsgRead(){
		return $this->m_bLastMsgRead;
	}

	/**
	 * set last message read status
	 *
	 * @param boolean $bLastMsgRead is last message read
	 * @return void
	 */
	public function setLastMsgRead($bLastMsgRead){
		$this->m_bLastMsgRead = $bLastMsgRead?true:false;
	}

	/**
	 * get membervariables as array
	 *
	 * @param integer $iTimeOffset time offset in seconds
	 * @param string $sDateFormat php date format
	 * @param integer $iLastOnlineTimestamp last online timestamp for user
	 * @return array member variables
	 */
	public function getDataArray($iTimeOffset = 0,$sDateFormat = "",$iLastOnlineTimestamp = 0,$sSubjectQuotePrefix = "",$objParser = null){
		// TODO: Vererbung mit unterschiedlicher Methodensignatur optimieren
		return array_merge(cMessageHeader::getDataArray($iTimeOffset,$sDateFormat,$iLastOnlineTimestamp,"",$objParser),
						   array("threadid"	=>	$this->m_iThreadId,
						   		 "active"	=>	intval($this->m_bIsActive),
						   		 "views"	=>	strval($this->m_iViews),
								 "fixed"	=>	intval($this->m_bIsFixed),
						   		 "lastid"	=>	$this->m_iLastMessageId,
						   		 "lastdate"	=>	(($this->m_iLastMessageTimestamp>$this->m_iMessageTimestamp)?date($sDateFormat,($this->m_iLastMessageTimestamp+$iTimeOffset)):0),
								 "lastnew"	=>	(($iLastOnlineTimestamp>$this->m_iLastMessageTimestamp)?0:1),
						   		 "msgquan"	=>	strval($this->m_iMessageQuantity),
								 "thread_msg_read"	=>	intval($this->m_bThreadMsgRead),
								 "last_msg_read"	=>	intval($this->m_bLastMsgRead)));
	}
}
?>