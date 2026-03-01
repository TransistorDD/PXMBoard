<?php
require_once(SRCDIR . '/Model/cUser.php');
/**
 * messageheader handling
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cMessageHeader{

	protected int $m_iId;					// message id
	protected mixed $m_objAuthor;			// author (user)
	protected string $m_sSubject;			// message subject
	protected int $m_iMessageTimestamp;		// date of the message

	/**
	 * Constructor
	 *
	 * @return void
	 */
	public function __construct(){

		$this->m_iId = 0;
		$this->m_objAuthor = new cUser();
		$this->m_sSubject = "";
		$this->m_iMessageTimestamp = 0;
	}

	/**
	 * get data from database by message id
	 *
	 * @param integer $iMessageId message id
	 * @return boolean success / failure
	 */
	public function loadDataById($iMessageId){

		$bReturn = false;
		$iMessageId = intval($iMessageId);

		if($iMessageId>0){


			if($objResultSet = cDBFactory::getInstance()->executeQuery("SELECT m_id,".
															 "m_subject,".
															 "m_tstmp,".
															 "m_userid,".
															 "m_username,".
															 "m_usermail,".
															 "m_userhighlight".
															 $this->_getDbAttributes().
															 " FROM ".
															 $this->_getDbTables().
															 " WHERE m_id=".$iMessageId.
															 $this->_getDbJoin())){
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
	protected function _setDataFromDb($objResultRow): bool{

		$this->m_iId = intval($objResultRow->m_id);
		$this->m_sSubject = $objResultRow->m_subject;
		$this->m_iMessageTimestamp = intval($objResultRow->m_tstmp);

		$this->m_objAuthor->setId($objResultRow->m_userid);
		$this->m_objAuthor->setUserName($objResultRow->m_username);
		$this->m_objAuthor->setPublicMail($objResultRow->m_usermail);
		$this->m_objAuthor->setHighlightUser($objResultRow->m_userhighlight);

		return true;
	}

	/**
	 * get additional database attributes for this object (template method)
	 *
	 * @return string additional database attributes for this object
	 */
	 protected function _getDbAttributes(){
	 	return "";
	 }

	/**
	 * get additional database tables for this object (template method)
	 *
	 * @return string additional database tables for this object
	 */
	 protected function _getDbTables(){
	 	return "pxm_message";
	 }

	/**
	 * get additional database tables for this object (template method)
	 *
	 * @return string additional database join for this object
	 */
	 protected function _getDbJoin(){
	 	return "";
	 }

	/**
	 * get id
	 *
	 * @return integer id
	 */
	public function getId(){
		return $this->m_iId;
	}

	/**
	 * set id
	 *
	 * @param integer $iId id
	 * @return void
	 */
	public function setId($iId){
		$this->m_iId = intval($iId);
	}

	/**
	 * get subject
	 *
	 * @param string $sSubjectQuotePrefix prefix for quoted subject
	 * @return string subject
	 */
	public function getSubject($sSubjectQuotePrefix = ""){
		if(!empty($sSubjectQuotePrefix) && (strncasecmp($this->m_sSubject,$sSubjectQuotePrefix,strlen($sSubjectQuotePrefix))!=0)){
			return $sSubjectQuotePrefix.$this->m_sSubject;
		}
		return $this->m_sSubject;
	}

	/**
	 * set subject
	 *
	 * @param string $sSubject subject
	 * @return void
	 */
	public function setSubject($sSubject){
		$this->m_sSubject = $sSubject;
	}

	/**
	 * get message timestamp
	 *
	 * @return integer message timestamp
	 */
	public function getMessageTimestamp(){
		return $this->m_iMessageTimestamp;
	}

	/**
	 * set message timestamp
	 *
	 * @param integer $iMessageTimestamp message timestamp
	 * @return void
	 */
	public function setMessageTimestamp($iMessageTimestamp){
		$this->m_iMessageTimestamp = intval($iMessageTimestamp);
	}

	/**
	 * get author (user)
	 *
	 * @return object author (user)
	 */
	public function getAuthor(){
		return $this->m_objAuthor;
	}

	/**
	 * set author (user)
	 *
	 * @param object $objAuthor author (user)
	 * @return void
	 */
	public function setAuthor($objAuthor){
		if(strcasecmp(get_class($objAuthor),"cuser") == 0 || is_subclass_of($objAuthor,"cUser")){
			$this->m_objAuthor = $objAuthor;
		}
	}

	/**
	 * get author id
	 *
	 * @return integer author id
	 */
	public function getAuthorId(){
		return $this->m_objAuthor->getId();
	}

	/**
	 * set author id
	 *
	 * @param integer $iAuthorId author id
	 * @return void
	 */
	public function setAuthorId($iAuthorId){
		$this->m_objAuthor->setId($iAuthorId);
	}

	/**
	 * set author username
	 *
	 * @param string $sAuthorUserName author username
	 * @return void
	 */
	public function setAuthorUserName($sAuthorUserName){
		$this->m_objAuthor->setUserName($sAuthorUserName);
	}

	/**
	 * set author public mail
	 *
	 * @param string $sAuthorPublicMail author public mail
	 * @return void
	 */
	public function setAuthorPublicMail($sAuthorPublicMail){
		$this->m_objAuthor->setPublicMail($sAuthorPublicMail);
	}

	/**
	 * set author highlight user
	 *
	 * @param boolean $bAuthorHighlightUser author highlight user
	 * @return void
	 */
	public function setAuthorHighlightUser($bAuthorHighlightUser){
		$this->m_objAuthor->setHighlightUser($bAuthorHighlightUser);
	}

	/**
	 * get membervariables as array
	 *
	 * @param integer $iTimeOffset time offset in seconds
	 * @param string $sDateFormat php date format
	 * @param integer $iLastOnlineTimestamp last online timestamp for user
	 * @param string $sSubjectQuotePrefix prefix for quoted subject
	 * @param object $objParser message parser
	 * @return array member variables
	 */
	public function getDataArray($iTimeOffset,$sDateFormat,$iLastOnlineTimestamp,$sSubjectQuotePrefix = "",$objParser = null){
		// TODO: Vererbung mit unterschiedlicher Methodensignatur optimieren
		return array("id"		=>	$this->m_iId,
					 "subject"	=>	$this->getSubject($sSubjectQuotePrefix),
					 "date"		=>	(($this->m_iMessageTimestamp>0)?date($sDateFormat,($this->m_iMessageTimestamp+$iTimeOffset)):0),
					 "new"		=>	(($iLastOnlineTimestamp>$this->m_iMessageTimestamp)?0:1),
					 "user"		=>	$this->m_objAuthor->getDataArray($iTimeOffset,$sDateFormat,$objParser));
	}
}
?>