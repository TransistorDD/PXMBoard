<?php
/**
 * configuration handling
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cConfig{

	protected array $m_arrAvailableTemplateEngines;			// available template engines
	protected string $m_sActiveTemplateEngine;				// active template engine, depending on installed engines and skin configuration

	protected int $m_iAccessTimestamp;						// current timestamp

	protected int $m_iDefaultSkinId;						// default skin id
	protected string $m_sSkinDir;							// skin directory
	protected bool $m_bUseQuickPost;						// activate quickpost?
	protected bool $m_bUseDirectRegistration;				// activate direct registratiom?
	protected bool $m_bUniqueRegistrationMails;				// unique registration mail?
	protected bool $m_bUseSignatures;						// use usersignatures?
	protected string $m_sDateFormat;						// string for php date function
	protected int $m_iTimeOffset;							// date & time offset in hours
	protected int $m_iOnlineTime;							// time that a user will be visible in onlinelist in seconds

	protected int $m_iThreadSizeLimit;						// close threads with at least x messages
	protected int $m_iUserPerPage;							// display x user per page
	protected int $m_iMessageHeaderPerPage;					// display x messages per page (search)
	protected int $m_iMessagesPerPage;						// display x messages per page (flat mode)
	protected int $m_iPrivateMessagesPerPage;				// display x private messages per page
	protected int $m_iThreadsPerPage;						// display x threads per page

	protected string $m_sQuoteSubject;						// prefix for quoted subjects
	protected string $m_sQuoteTag;							// HTML tag for quoted text (blockquote)

	protected string $m_sMailWebmaster;						// mail of webmaster

	protected int $m_iMaxProfileImgSize;					// size of profile images in bytes
	protected int $m_iMaxProfileImgWidth;					// width of profile images
	protected int $m_iMaxProfileImgHeight;				// height of profile images
	protected string $m_sProfileImgDir;						// profile images directory
	protected int $m_iProfileImgSplitDir;					// one directory for x profile images
	protected array $m_arrProfileImgTypes;					// accepted filetypes for profile images

	protected string $m_sUserAgent;							// HTTP_USER_AGENT from request
	protected string $m_sRemoteAddr;						// REMOTE_ADDR from request

	/**
	 * Constructor
	 *
	 * @param array $arrTemplateEngines available template engine ordered by priority
	 * @param string $sUserAgent HTTP_USER_AGENT from request
	 * @param string $sRemoteAddr REMOTE_ADDR from request
	 * @return void
	 */
	public function __construct($arrTemplateEngines, $sUserAgent = "", $sRemoteAddr = ""){

		$this->m_arrAvailableTemplateEngines = $arrTemplateEngines;
		$this->m_sActiveTemplateEngine = "";
		$this->m_sUserAgent = $sUserAgent;
		$this->m_sRemoteAddr = $sRemoteAddr;

		// initialize general configuration

		// defaults
		$this->m_iAccessTimestamp = time();

		$this->m_iDefaultSkinId = 0;
		$this->m_sSkinDir = "skins/";
		$this->m_bUseQuickPost = false;
		$this->m_bUseDirectRegistration = false;
		$this->m_bUniqueRegistrationMails = false;
		$this->m_bUseSignatures =false;
		$this->m_sDateFormat = "j.m.Y H:i";
		$this->m_iTimeOffset = 0;
		$this->m_iOnlineTime = 300;

		$this->m_iThreadSizeLimit = 500;
		$this->m_iUserPerPage = 20;
		$this->m_iMessageHeaderPerPage = 20;
		$this->m_iMessagesPerPage = 20;
		$this->m_iPrivateMessagesPerPage = 20;
		$this->m_iThreadsPerPage = 50;

		$this->m_sQuoteSubject = "Re:";
		$this->m_sQuoteTag = "blockquote";

		$this->m_sMailWebmaster	= "";

		$this->m_iMaxProfileImgSize = 0;
		$this->m_iMaxProfileImgWidth = 200;
		$this->m_iMaxProfileImgHeight = 400;
		$this->m_sProfileImgDir = "";
		$this->m_iProfileImgSplitDir = 100;
		$this->m_arrProfileImgTypes = array("image/jpeg" => "jpg","image/pjpeg" => "jpg","image/gif" => "gif","image/png" => "png");

		// get general configuration from database
		$this->_loadData();
	}

	/**
	 * get data from database
	 *
	 * @return boolean success / failure
	 */
	private function _loadData(){


		if($objResultSet = cDBFactory::getInstance()->executeQuery("SELECT c_skinid,".
														"c_quickpost,".
														"c_directregistration,".
														"c_uniquemail,".
														"c_usesignatures,".
														"c_dateformat,".
														"c_timeoffset,".
														"c_onlinetime,".
														"c_closethreads,".
														"c_usrperpage,".
														"c_msgperpage,".
														"c_msgheaderperpage,".
														"c_privatemsgperpage,".
														"c_thrdperpage,".
														"c_quotesubject,".
														"c_mailwebmaster,".
														"c_skindir,".
														"c_maxprofilepicsize,".
														"c_maxprofilepicwidth,".
														"c_maxprofilepicheight,".
														"c_profileimgdir".
													" FROM pxm_configuration")){
			if($objResultRow = $objResultSet->getNextResultRowObject()){

				$objResultSet->freeResult();
				unset($objResultSet);

				$this->m_iDefaultSkinId = intval($objResultRow->c_skinid);

				$this->m_bUseQuickPost = $objResultRow->c_quickpost?true:false;
				$this->m_bUseDirectRegistration = $objResultRow->c_directregistration?true:false;
				$this->m_bUniqueRegistrationMails = $objResultRow->c_uniquemail?true:false;
				$this->m_bUseSignatures = $objResultRow->c_usesignatures?true:false;
				$this->m_sDateFormat = $objResultRow->c_dateformat;
				$this->m_iTimeOffset = intval($objResultRow->c_timeoffset);
				$this->m_iOnlineTime = intval($objResultRow->c_onlinetime);

				$this->m_iThreadSizeLimit = intval($objResultRow->c_closethreads);
				$this->m_iUserPerPage = intval($objResultRow->c_usrperpage);
				$this->m_iMessagesPerPage = intval($objResultRow->c_msgperpage);
				$this->m_iMessageHeaderPerPage = intval($objResultRow->c_msgheaderperpage);
				$this->m_iPrivateMessagesPerPage = intval($objResultRow->c_privatemsgperpage);
				$this->m_iThreadsPerPage = intval($objResultRow->c_thrdperpage);

				$this->m_sQuoteSubject = $objResultRow->c_quotesubject;

				$this->m_sMailWebmaster	= $objResultRow->c_mailwebmaster;

				$this->m_sSkinDir = $objResultRow->c_skindir;
				$this->m_iMaxProfileImgSize = intval($objResultRow->c_maxprofilepicsize);
				$this->m_iMaxProfileImgWidth = intval($objResultRow->c_maxprofilepicwidth);
				$this->m_iMaxProfileImgHeight = intval($objResultRow->c_maxprofilepicheight);
				$this->m_sProfileImgDir = $objResultRow->c_profileimgdir;

				unset($objResultRow);

				return true;
			}
		}
		return false;
	}

	/**
	 * update data in database
	 *
	 * @return boolean success / failure
	 */
	public function updateData(){


		if(cDBFactory::getInstance()->executeQuery("UPDATE pxm_configuration SET c_skinid=$this->m_iDefaultSkinId,".
																	    "c_quickpost=".intval($this->m_bUseQuickPost).",".
																	    "c_directregistration=".intval($this->m_bUseDirectRegistration).",".
																	    "c_uniquemail=".intval($this->m_bUniqueRegistrationMails).",".
																		"c_usesignatures=".intval($this->m_bUseSignatures).",".
																		"c_dateformat=".cDBFactory::getInstance()->quote($this->m_sDateFormat).",".
																	    "c_timeoffset=$this->m_iTimeOffset,".
																	    "c_onlinetime=$this->m_iOnlineTime,".
																	    "c_closethreads=$this->m_iThreadSizeLimit,".
																	    "c_usrperpage=$this->m_iUserPerPage,".
																	    "c_msgperpage=$this->m_iMessagesPerPage,".
																		"c_msgheaderperpage=$this->m_iMessageHeaderPerPage,".
																		"c_privatemsgperpage=$this->m_iPrivateMessagesPerPage,".
																	    "c_thrdperpage=$this->m_iThreadsPerPage,".
																		"c_quotesubject=".cDBFactory::getInstance()->quote($this->m_sQuoteSubject).",".
																	    "c_mailwebmaster=".cDBFactory::getInstance()->quote($this->m_sMailWebmaster).",".
																		"c_skindir=".cDBFactory::getInstance()->quote($this->m_sSkinDir).",".
																	    "c_maxprofilepicsize=$this->m_iMaxProfileImgSize,".
																	    "c_maxprofilepicwidth=$this->m_iMaxProfileImgWidth,".
																	    "c_maxprofilepicheight=$this->m_iMaxProfileImgHeight,".
																	    "c_profileimgdir=".cDBFactory::getInstance()->quote($this->m_sProfileImgDir))){
			return true;
		}
		return false;
	}

	/**
	 * get available template engines
	 *
	 * @return array available template engines
	 */
	public function getAvailableTemplateEngines(){
		return $this->m_arrAvailableTemplateEngines;
	}

	/**
	 * get active template engine
	 *
	 * @return string active template engine
	 */
	public function getActiveTemplateEngine(){
		return $this->m_sActiveTemplateEngine;
	}

	/**
	 * set active template engine
	 *
	 * @param string $sActiveTemplateEngine active template engine
	 * @return void
	 */
	public function setActiveTemplateEngine($sActiveTemplateEngine){
		$this->m_sActiveTemplateEngine = $sActiveTemplateEngine;
	}

	/**
	 * get default skin id
	 *
	 * @return integer default skin id
	 */
	public function getDefaultSkinId(){
		return $this->m_iDefaultSkinId;
	}

	/**
	 * set default skin id
	 *
	 * @param integer $iDefaultSkinId default skin id
	 * @return void
	 */
	public function setDefaultSkinId($iDefaultSkinId){
		$this->m_iDefaultSkinId = intval($iDefaultSkinId);
	}

	/**
	 * get access timestamp
	 *
	 * @return integer access timestamp
	 */
	public function getAccessTimestamp(){
		return $this->m_iAccessTimestamp;
	}

	/**
	 * use quickpost?
	 *
	 * @return boolean use quickpost?
	 */
	public function useQuickPost(){
		return $this->m_bUseQuickPost;
	}

	/**
	 * set use quickpost
	 *
	 * @param boolean $bUseQuickPost use quickpost?
	 * @return void
	 */
	public function setUseQuickPost($bUseQuickPost){
		$this->m_bUseQuickPost = $bUseQuickPost?true:false;
	}

	/**
	 * use signatures?
	 *
	 * @return boolean use signatures?
	 */
	public function useSignatures(){
		return $this->m_bUseSignatures;
	}

	/**
	 * set use signatures
	 *
	 * @param boolean $bUseSignatures use signatures?
	 * @return void
	 */
	public function setUseSignatures($bUseSignatures){
		$this->m_bUseSignatures = $bUseSignatures?true:false;
	}

	/**
	 * use direct registration?
	 *
	 * @return boolean use direct registration?
	 */
	public function useDirectRegistration(){
		return $this->m_bUseDirectRegistration;
	}

	/**
	 * set use direct registration
	 *
	 * @param boolean $bUseDirectRegistration use direct registration?
	 * @return void
	 */
	public function setUseDirectRegistration($bUseDirectRegistration){
		$this->m_bUseDirectRegistration = $bUseDirectRegistration?true:false;
	}

	/**
	 * are the private mail adresses unique?
	 *
	 * @return boolean registration mail adresses unique?
	 */
	public function uniqueRegistrationMails(){
		return $this->m_bUniqueRegistrationMails;
	}

	/**
	 * set private mail adresses unique
	 *
	 * @param boolean $bUniqueRegistrationMails registration mail adresses unique?
	 * @return void
	 */
	public function setUniqueRegistrationMails($bUniqueRegistrationMails){
		$this->m_bUniqueRegistrationMails = $bUniqueRegistrationMails?true:false;
	}

	/**
	 * get date format
	 *
	 * @return string date format
	 */
	public function getDateFormat(){
		return $this->m_sDateFormat;
	}

	/**
	 * set date format
	 *
	 * @param string $sDateFormat date format
	 * @return void
	 */
	public function setDateFormat($sDateFormat){
		$this->m_sDateFormat = $sDateFormat;
	}

	/**
	 * get time offset
	 *
	 * @return integer time offset
	 */
	public function getTimeOffset(){
		return $this->m_iTimeOffset;
	}

	/**
	 * set time offset
	 *
	 * @param integer $iTimeOffset time offset
	 * @return void
	 */
	public function setTimeOffset($iTimeOffset){
		$iTimeOffset = intval($iTimeOffset);
		if($iTimeOffset<13 && $iTimeOffset>-13){
			$this->m_iTimeOffset = $iTimeOffset;
		}
	}

	/**
	 * get online time
	 *
	 * @return integer online time (seconds)
	 */
	public function getOnlineTime(){
		return $this->m_iOnlineTime;
	}

	/**
	 * set online time
	 *
	 * @param integer $iOnlineTime online time (seconds)
	 * @return void
	 */
	public function setOnlineTime($iOnlineTime){
		$this->m_iOnlineTime = intval($iOnlineTime);
	}

	/**
	 * get thread size limit
	 *
	 * @return integer thread size limit (0 = no limit)
	 */
	public function getThreadSizeLimit(){
		return $this->m_iThreadSizeLimit;
	}

	/**
	 * set thread size limit
	 *
	 * @param integer $iThreadSizeLimit thread size limit (0 = no limit)
	 * @return void
	 */
	public function setThreadSizeLimit($iThreadSizeLimit){
		$this->m_iThreadSizeLimit = intval($iThreadSizeLimit);
	}

	/**
	 * get user per page
	 *
	 * @return integer user per page
	 */
	public function getUserPerPage(){
		return $this->m_iUserPerPage;
	}

	/**
	 * set user per page
	 *
	 * @param integer $iUserPerPage user per page
	 * @return void
	 */
	public function setUserPerPage($iUserPerPage){
		$this->m_iUserPerPage = intval($iUserPerPage);
	}

	/**
	 * get message header per page (search)
	 *
	 * @return integer message header per page
	 */
	public function getMessageHeaderPerPage(){
		return $this->m_iMessageHeaderPerPage;
	}

	/**
	 * set message header per page (search)
	 *
	 * @param integer $iMessageHeaderPerPage message header per page
	 * @return void
	 */
	public function setMessageHeaderPerPage($iMessageHeaderPerPage){
		$this->m_iMessageHeaderPerPage = intval($iMessageHeaderPerPage);
	}

	/**
	 * get messages per page (flat mode)
	 *
	 * @return integer messages per page
	 */
	public function getMessagesPerPage(){
		return $this->m_iMessagesPerPage;
	}

	/**
	 * set messages per page (flat mode)
	 *
	 * @param integer $iMessagesPerPage messages per page
	 * @return void
	 */
	public function setMessagesPerPage($iMessagesPerPage){
		$this->m_iMessagesPerPage = intval($iMessagesPerPage);
	}

	/**
	 * get private messages per page
	 *
	 * @return integer private messages per page
	 */
	public function getPrivateMessagesPerPage(){
		return $this->m_iPrivateMessagesPerPage;
	}

	/**
	 * set private messages per page
	 *
	 * @param integer $iPrivateMessagesPerPage private messages per page
	 * @return void
	 */
	public function setPrivateMessagesPerPage($iPrivateMessagesPerPage){
		$this->m_iPrivateMessagesPerPage = intval($iPrivateMessagesPerPage);
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
	 * get webmaster mail adress
	 *
	 * @return string webmaster mail adress
	 */
	public function getMailWebmaster(){
		return $this->m_sMailWebmaster;
	}

	/**
	 * set webmaster mail adress
	 *
	 * @param string $sMailWebmaster webmaster mail adress
	 * @return void
	 */
	public function setMailWebmaster($sMailWebmaster){
		$this->m_sMailWebmaster = $sMailWebmaster;
	}

	/**
	 * get quote subject
	 *
	 * @return string quote subject
	 */
	public function getQuoteSubject(){
		return $this->m_sQuoteSubject;
	}

	/**
	 * set quote subject
	 *
	 * @param string $sQuoteSubject quote subject
	 * @return void
	 */
	public function setQuoteSubject($sQuoteSubject){
		$this->m_sQuoteSubject = $sQuoteSubject;
	}

	/**
	 * get quote tag
	 *
	 * @return string quote tag (HTML element name)
	 */
	public function getQuoteTag(){
		return $this->m_sQuoteTag;
	}

	/**
	 * get skin directory
	 *
	 * @return string skin directory
	 */
	public function getSkinDirectory(): string{
		// Resolve relative paths to absolute using BASEDIR when available.
		// Skins reside outside public/, so relative paths must be anchored to the project root.
		if($this->m_sSkinDir !== '' && $this->m_sSkinDir[0] !== '/' && defined('BASEDIR')){
			return BASEDIR . '/' . $this->m_sSkinDir;
		}
		return $this->m_sSkinDir;
	}

	/**
	 * set skin directory
	 *
	 * @param string $sSkinDir skin directory
	 * @return void
	 */
	public function setSkinDirectory($sSkinDir){
		$this->m_sSkinDir = $sSkinDir.(((strlen($sSkinDir)>0) && ($sSkinDir[strlen($sSkinDir)-1]!='/'))?"/":"");
	}

	/**
	 * get max profile img size
	 *
	 * @return integer max profile img size (byte)
	 */
	public function getMaxProfileImgSize(){
		return $this->m_iMaxProfileImgSize;
	}

	/**
	 * set max profile img size
	 *
	 * @param integer $iMaxProfileImgSize max profile img size (byte)
	 * @return void
	 */
	public function setMaxProfileImgSize($iMaxProfileImgSize){
		$this->m_iMaxProfileImgSize = intval($iMaxProfileImgSize);
	}

	/**
	 * get max profile img width
	 *
	 * @return integer max profile img width (pixel)
	 */
	public function getMaxProfileImgWidth(){
		return $this->m_iMaxProfileImgWidth;
	}

	/**
	 * set max profile img width
	 *
	 * @param integer $iMaxProfileImgWidth max profile img width (pixel)
	 * @return void
	 */
	public function setMaxProfileImgWidth($iMaxProfileImgWidth){
		$this->m_iMaxProfileImgWidth = intval($iMaxProfileImgWidth);
	}

	/**
	 * get max profile img height
	 *
	 * @return integer max profile img height (pixel)
	 */
	public function getMaxProfileImgHeight(){
		return $this->m_iMaxProfileImgHeight;
	}

	/**
	 * set max profile img height
	 *
	 * @param integer $iMaxProfileImgHeight max profile img height (pixel)
	 * @return void
	 */
	public function setMaxProfileImgHeight($iMaxProfileImgHeight){
		$this->m_iMaxProfileImgHeight = intval($iMaxProfileImgHeight);
	}

	/**
	 * get profile img directory (web-relative path for use in templates)
	 *
	 * @return string profile img directory (web-relative)
	 */
	public function getProfileImgDirectory(){
		return $this->m_sProfileImgDir;
	}

	/**
	 * get profile img directory as absolute filesystem path (for file operations)
	 * Resolves relative paths against PUBLICDIR when available.
	 *
	 * @return string absolute filesystem path to profile img directory
	 */
	public function getProfileImgFsDirectory(): string{
		if($this->m_sProfileImgDir !== '' && $this->m_sProfileImgDir[0] !== '/' && defined('PUBLICDIR')){
			return PUBLICDIR . '/' . $this->m_sProfileImgDir;
		}
		return $this->m_sProfileImgDir;
	}

	/**
	 * set profile img directory
	 *
	 * @param string $sProfileImgDir profile img directory
	 * @return void
	 */
	public function setProfileImgDirectory($sProfileImgDir){
		$this->m_sProfileImgDir = $sProfileImgDir.(((strlen($sProfileImgDir)>0) && ($sProfileImgDir[strlen($sProfileImgDir)-1]!='/'))?"/":"");
	}

	/**
	 * get profile img types
	 *
	 * @return array profile img types
	 */
	public function getProfileImgTypes(){
		return $this->m_arrProfileImgTypes;
	}

	/**
	 * get profile img directory split
	 *
	 * @return integer profile img directory split
	 */
	public function getProfileImgDirectorySplit(){
		return $this->m_iProfileImgSplitDir;
	}

	/**
	 * get user agent from request
	 *
	 * @return string user agent
	 */
	public function getUserAgent(){
		return $this->m_sUserAgent;
	}

	/**
	 * get remote address from request
	 *
	 * @return string remote address (IP)
	 */
	public function getRemoteAddr(){
		return $this->m_sRemoteAddr;
	}

	/**
	 * get membervariables as array
	 *
	 * @param array  $arrAdditionalConfig additional configuration
	 * @return array member variables
	 */
	public function getDataArray($arrAdditionalConfig = array()){
		$arrGeneralConfiguration = array(
			"webmaster"			=> $this->m_sMailWebmaster,
			"usesignatures"		=> intval($this->m_bUseSignatures),
			"profile_img_dir"	=> $this->m_sProfileImgDir
		);
//TODO: additonalConfig entfernen
		return array("config"=> $arrGeneralConfiguration, $arrAdditionalConfig);
	}
}
?>