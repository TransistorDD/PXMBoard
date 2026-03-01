<?php
require_once(SRCDIR . '/Model/cUser.php');
require_once(SRCDIR . '/Enum/eBoardStatus.php');
/**
 * board handling
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cBoard{

	protected int $m_iId;						// board id
	protected string $m_sName;					// board name
	protected string $m_sDescription;			// board description
	protected int $m_iPosition;					// position in boardlist
	protected BoardStatus $m_eStatus;			// board status (PUBLIC, MEMBERS_ONLY, READONLY_PUBLIC, READONLY_MEMBERS, CLOSED)
	protected int $m_iLastMessageTimestamp;		// timestamp of last message
	protected int $m_iThreadListTimeSpan;		// timespan for threadlist
	protected string $m_sThreadListSortMode;	// sortmode for threadlist
	protected bool $m_bEmbedExternal;			// externe Inhalte einbetten (Bilder, YouTube, Twitch)
	protected bool $m_bDoTextReplacements;		// do textreplacements
	protected int $m_iThreadsPerPage;			// threads per page

	protected array $m_arrModerators;			// array of moderatores (id and name)

	/**
	 * Constructor
	 *
	 * @return void
	 */
	public function __construct(){

		$this->m_iId = 0;
		$this->m_sName = "";
		$this->m_sDescription = "";
		$this->m_iPosition = 0;
		$this->m_eStatus = BoardStatus::PUBLIC;
		$this->m_iLastMessageTimestamp = 0;

		$this->m_iThreadListTimeSpan = 100;
		$this->m_sThreadListSortMode = "last";
		$this->m_bEmbedExternal = false;
		$this->m_bDoTextReplacements = false;
		$this->m_iThreadsPerPage = 0;

		$this->m_arrModerators = array();
	}

	/**
	 * get data from database by board id
	 *
	 * @param integer $iBoardId board id
	 * @return boolean success / failure
	 */
	public function loadDataById(int $iBoardId): bool{

		$bReturn = false;
		$iBoardId = intval($iBoardId);

		if($iBoardId>0){


			if($objResultSet = cDBFactory::getInstance()->executeQuery("SELECT b_id,".
																	  "b_name,".
																	  "b_description,".
																	  "b_position,".
																	  "b_status,".
																	  "b_lastmsgtstmp,".
																	  "b_timespan,".
																	  "b_threadlistsort,".
																	  "b_embed_external,".
																	  "b_replacetext ".
																"FROM pxm_board WHERE b_id=$iBoardId")){
				if($objResultRow = $objResultSet->getNextResultRowObject()){
					$bReturn = $this->_setDataFromDb($objResultRow);
				}
				$objResultSet->freeResult();
				unset($objResultSet);
			}
		}
		return $bReturn;
	}

	/**
	 * initalize the member variables with the resultset from the db
	 *
	 * @param object $objResultRow resultrow from db query
	 * @return boolean success / failure
	 */
	private function _setDataFromDb($objResultRow): bool{

		$this->m_iId = intval($objResultRow->b_id);
		$this->m_sName = $objResultRow->b_name;
		$this->m_sDescription = $objResultRow->b_description;
		$this->m_iPosition = intval($objResultRow->b_position);
		$this->m_eStatus = BoardStatus::from($objResultRow->b_status);
		$this->m_iLastMessageTimestamp = intval($objResultRow->b_lastmsgtstmp);
		$this->m_iThreadListTimeSpan = intval($objResultRow->b_timespan);
		$this->m_sThreadListSortMode = $objResultRow->b_threadlistsort;
		$this->m_bEmbedExternal = $objResultRow->b_embed_external?true:false;
		$this->m_bDoTextReplacements = $objResultRow->b_replacetext?true:false;

		return true;
	}

	/**
	 * load moderator data from database
	 *
	 * @return boolean success / failure
	 */
	public function loadModData(): bool{


		$this->m_arrModerators = array();

		if($objResultSet = cDBFactory::getInstance()->executeQuery("SELECT u_id,u_username,u_publicmail,u_highlight FROM pxm_moderator,pxm_user WHERE mod_userid=u_id AND mod_boardid=$this->m_iId")){
			while($objResultRow = $objResultSet->getNextResultRowObject()){

				$objUser = new cUser();
				$objUser->setId($objResultRow->u_id);
				$objUser->setUserName($objResultRow->u_username);
				$objUser->setPublicMail($objResultRow->u_publicmail);
				$objUser->setHighlightUser($objResultRow->u_highlight);

				$this->m_arrModerators[] = $objUser;
			}
			$objResultSet->freeResult();
		}
		else return false;

		return true;
	}

	/**
	 * save moderator data to database
	 *
	 * @return boolean success / failure
	 */
	public function updateModData(): bool{


		if(cDBFactory::getInstance()->executeQuery("DELETE FROM pxm_moderator WHERE mod_boardid=$this->m_iId")){
			foreach ($this->m_arrModerators as $objUser) {
    			cDBFactory::getInstance()->executeQuery("INSERT INTO pxm_moderator (mod_userid,mod_boardid) VALUES (" . $objUser->getId() . ",$this->m_iId)");
			}
		}
		else return false;

		return true;
	}

	/**
	 * insert new data into database
	 *
	 * @return boolean success / failure
	 */
	public function insertData(): bool{


		if(cDBFactory::getInstance()->executeQuery("INSERT INTO pxm_board (b_name,b_description,b_status,b_timespan,b_threadlistsort,b_embed_external,b_replacetext) "
										 ."VALUES (".cDBFactory::getInstance()->quote($this->m_sName).",".cDBFactory::getInstance()->quote($this->m_sDescription).",".cDBFactory::getInstance()->quote($this->m_eStatus->value).",$this->m_iThreadListTimeSpan,"
												 .cDBFactory::getInstance()->quote($this->m_sThreadListSortMode).",".intval($this->m_bEmbedExternal).",".intval($this->m_bDoTextReplacements).")")){
			$this->m_iId = cDBFactory::getInstance()->getInsertId("pxm_board","b_id");
			cDBFactory::getInstance()->executeQuery("UPDATE pxm_board SET b_position=b_id WHERE b_id=".$this->m_iId);
		}
		else{
			return false;
		}
		return true;
	}

	/**
	 * update data in database
	 *
	 * @return boolean success / failure
	 */
	public function updateData(): bool{

		$bReturn = false;

		if($this->m_iId>0){
		if(cDBFactory::getInstance()->executeQuery("UPDATE pxm_board SET b_name=".cDBFactory::getInstance()->quote($this->m_sName).","
													."b_description=".cDBFactory::getInstance()->quote($this->m_sDescription).","
													."b_position=$this->m_iPosition,"
													."b_status=".cDBFactory::getInstance()->quote($this->m_eStatus->value).","
													."b_timespan=$this->m_iThreadListTimeSpan,"
													."b_threadlistsort=".cDBFactory::getInstance()->quote($this->m_sThreadListSortMode).","
																."b_embed_external=".intval($this->m_bEmbedExternal).","
																."b_replacetext=".intval($this->m_bDoTextReplacements)." WHERE b_id=$this->m_iId")){
				$bReturn = true;
			}
		}
		return $bReturn;
	}

	/**
	 * delete data from database
	 *
	 * @return boolean success / failure
	 */
	public function deleteData(): bool{


		if($this->m_iId>0){
			if($objResultSet = cDBFactory::getInstance()->executeQuery("SELECT t_id FROM pxm_thread WHERE t_boardid=$this->m_iId")){
				while($objResultRow = $objResultSet->getNextResultRowObject()){
					cDBFactory::getInstance()->executeQuery("DELETE FROM pxm_message WHERE m_threadid=$objResultRow->t_id");
				}
				cDBFactory::getInstance()->executeQuery("DELETE FROM pxm_thread WHERE t_boardid=$this->m_iId");
				cDBFactory::getInstance()->executeQuery("DELETE FROM pxm_moderator WHERE mod_boardid=$this->m_iId");
				cDBFactory::getInstance()->executeQuery("DELETE FROM pxm_board WHERE b_id=$this->m_iId");
			}
		}
		else{
			return false;
		}

		return true;
	}

	/**
	 * get id
	 *
	 * @return integer id
	 */
	public function getId(): int{
		return $this->m_iId;
	}

	/**
	 * set id
	 *
	 * @param integer $iId id
	 * @return void
	 */
	public function setId(int $iId){
		$this->m_iId = intval($iId);
	}

	/**
	 * get name
	 *
	 * @return string name
	 */
	public function getName(): string{
		return $this->m_sName;
	}

	/**
	 * set name
	 *
	 * @param string $sName name
	 * @return void
	 */
	public function setName(string $sName){
		$this->m_sName = $sName;
	}

	/**
	 * get description
	 *
	 * @return string description
	 */
	public function getDescription(){
		return $this->m_sDescription;
	}

	/**
	 * set description
	 *
	 * @param string $sDescription description
	 * @return void
	 */
	public function setDescription(string $sDescription){
		$this->m_sDescription = $sDescription;
	}

	/**
	 * get position
	 *
	 * @return integer position
	 */
	public function getPosition(): int{
		return $this->m_iPosition;
	}

	/**
	 * set position
	 *
	 * @param integer $iPosition position
	 * @return void
	 */
	public function setPosition(int $iPosition){
		$this->m_iPosition = intval($iPosition);
	}

	/**
	 * update position
	 *
	 * @param integer $iPosition position
	 * @return void
	 */
	public function updatePosition(int $iPosition){


		$iPosition = intval($iPosition);

		if($iPosition>0 && $this->m_iPosition!=$iPosition){
			if($this->m_iPosition>$iPosition){
				cDBFactory::getInstance()->executeQuery("UPDATE pxm_board SET b_position = b_position+1 WHERE b_position >= $iPosition AND b_position < $this->m_iPosition");
			}
			else{
				cDBFactory::getInstance()->executeQuery("UPDATE pxm_board SET b_position = b_position-1 WHERE b_position <= $iPosition AND b_position > $this->m_iPosition");
			}
			$this->m_iPosition = $iPosition;
			cDBFactory::getInstance()->executeQuery("UPDATE pxm_board SET b_position = $this->m_iPosition WHERE b_id = $this->m_iId");
		}
	}

	/**
	 * Get board status
	 *
	 * @return BoardStatus current status
	 */
	public function getStatus(): BoardStatus{
		return $this->m_eStatus;
	}

	/**
	 * Set board status
	 *
	 * @param BoardStatus $eStatus new status
	 * @return void
	 */
	public function setStatus(BoardStatus $eStatus){
		$this->m_eStatus = $eStatus;
	}

	/**
	 * Update board status in database
	 *
	 * @param BoardStatus $eStatus new status
	 * @return boolean success / failure
	 */
	public function updateStatus(BoardStatus $eStatus): bool{
		if(!cDBFactory::getInstance()->executeQuery("UPDATE pxm_board SET b_status=".cDBFactory::getInstance()->quote($eStatus->value)." WHERE b_id=$this->m_iId")){
			return false;
		}
		$this->m_eStatus = $eStatus;
		return true;
	}

	/**
	 * Check if board is readable for public (non-authenticated users)
	 *
	 * @return bool true if public can read
	 */
	public function isPublicReadable(){
		return $this->m_eStatus->isPublicReadable();
	}

	/**
	 * Check if board is writable by regular users
	 *
	 * @return bool true if regular users can write
	 */
	public function isWritable(){
		return $this->m_eStatus->isWritable();
	}

	/**
	 * Check if board requires authentication
	 *
	 * @return bool true if authentication required
	 */
	public function requiresAuthentication(){
		return $this->m_eStatus->requiresAuthentication();
	}

	/**
	 * Check if board is closed (only mods/admins can access)
	 *
	 * @return bool true if closed
	 */
	public function isClosed(){
		return $this->m_eStatus->isClosed();
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
	 * get threads per page
	 *
	 * @return integer threads per page
	 */
	public function getThreadsPerPage(){
		return $this->m_iThreadsPerPage;
	}

	/**
	 * set threads per page
	 *
	 * @param integer $iThreadsPerPage threads per page
	 * @return void
	 */
	public function setThreadsPerPage($iThreadsPerPage){
		$this->m_iThreadsPerPage = intval($iThreadsPerPage);
	}

	/**
	 * get threadlist timespan
	 *
	 * @return integer threadlist timespan
	 */
	public function getThreadListTimeSpan(){
		return $this->m_iThreadListTimeSpan;
	}

	/**
	 * set threadlist timespan
	 *
	 * @param integer $iThreadListTimeSpan threadlist timespan
	 * @return void
	 */
	public function setThreadListTimeSpan($iThreadListTimeSpan){
		$this->m_iThreadListTimeSpan = intval($iThreadListTimeSpan);
	}

	/**
	 * get threadlist sort mode
	 *
	 * @return string threadlist sort mode
	 */
	public function getThreadListSortMode(){
		return $this->m_sThreadListSortMode;
	}

	/**
	 * set threadlist sort mode
	 *
	 * @param string $sThreadListSortMode threadlist sort mode
	 * @return void
	 */
	public function setThreadListSortMode($sThreadListSortMode){
		$this->m_sThreadListSortMode = $sThreadListSortMode;
	}

	/**
	 * Externe Inhalte einbetten? (Bilder, YouTube, Twitch)
	 *
	 * @return boolean externe Inhalte einbetten?
	 */
	public function embedExternal(){
		return $this->m_bEmbedExternal;
	}

	/**
	 * Externe Inhalte einbetten setzen
	 *
	 * @param boolean $bEmbedExternal externe Inhalte einbetten?
	 * @return void
	 */
	public function setEmbedExternal($bEmbedExternal){
		$this->m_bEmbedExternal = $bEmbedExternal?true:false;
	}

	/**
	 * do textreplacements?
	 *
	 * @return boolean do textreplacements?
	 */
	public function doTextReplacements(){
		return $this->m_bDoTextReplacements;
	}

	/**
	 * set do textreplacements
	 *
	 * @param boolean $bDoTextReplacements do textreplacements?
	 * @return void
	 */
	public function setDoTextReplacements($bDoTextReplacements){
		$this->m_bDoTextReplacements = $bDoTextReplacements?true:false;
	}

	/**
	 * get moderators
	 *
	 * @return array moderators
	 */
	public function getModerators(){
		return $this->m_arrModerators;
	}

	/**
	 * set moderators
	 *
	 * @param array $arrModeratorUserNames usernames of moderators
	 * @return void
	 */
	public function setModeratorsByUserName($arrModeratorUserNames){

		$this->m_arrModerators = array();

		foreach($arrModeratorUserNames as $sUserName){
			$sUserName = trim($sUserName);
			if(!empty($sUserName)){
				$objUser = new cUser();
				if($objUser->loadDataByUserName($sUserName)){
					$this->m_arrModerators[] = $objUser;
				}
			}
		}
	}

	/**
	 * get membervariables as array
	 *
	 * @param integer $iTimeOffset time offset in seconds
	 * @param string $sDateFormat php date format
	 * @param integer $iLastOnlineTimestamp last online timestamp for user
	 * @param object $objParser message parser (for signature)
	 * @return array member variables
	 */
	public function getDataArray($iTimeOffset,$sDateFormat,$iLastOnlineTimestamp,$objParser){
		$arrModerators = array();
		reset($this->m_arrModerators);
		foreach ($this->m_arrModerators as $objUser) {
    		$arrModerators[] = $objUser->getDataArray($iTimeOffset, $sDateFormat, $objParser);
		}

		return array("id"		=>	$this->m_iId,
					 "name"		=>	$this->m_sName,
					 "desc"		=>	$this->m_sDescription,
					 "position"	=>	$this->m_iPosition,
					 "lastmsg"	=>	(($this->m_iLastMessageTimestamp>0)?date($sDateFormat,($this->m_iLastMessageTimestamp+$iTimeOffset)):0),
					 "new"		=>	(($iLastOnlineTimestamp>$this->m_iLastMessageTimestamp)?0:1),
					 "status"	=>	$this->m_eStatus->value,
					 "status_label"	=>	$this->m_eStatus->getLabel(),
					 "moderator"=>	$arrModerators);
	}
}
?>