<?php
require_once(SRCDIR . '/Model/cUserPermissions.php');
/**
 * user configuration handling
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cUserConfig extends cUserPermissions{

	protected bool $m_bIsVisible;					// user visible? (online list)
	protected int $m_iSkinId;						// skin id
	protected string $m_sThreadListSortMode;			// sort mode for threadlist
	protected int $m_iTimeOffset;					// timeoffset
	protected bool $m_bEmbedExternal;				// externe Inhalte einbetten (Bilder, YouTube, Twitch)
	protected bool $m_bPrivateMessageNotification;	// send private message notification

	/**
	 * Constructor
	 *
	 * @return void
	 */
	public function __construct(){

		parent::__construct();

		$this->m_bIsVisible	= true;
		$this->m_iSkinId = 0;
		$this->m_sThreadListSortMode = "";
		$this->m_iTimeOffset = 0;
		$this->m_bEmbedExternal = false;
		$this->m_bPrivateMessageNotification = false;
	}

	/**
	 * initalize the member variables with the resultset from the db
	 *
	 * @param object $objResultRow resultrow from db query
	 * @return boolean success / failure
	 */
	protected function _setDataFromDb($objResultRow){

		cUserPermissions::_setDataFromDb($objResultRow);

		$this->m_bIsVisible	= $objResultRow->u_visible?true:false;
		$this->m_iSkinId = intval($objResultRow->u_skinid);
		$this->m_sThreadListSortMode = $objResultRow->u_threadlistsort;
		$this->m_iTimeOffset = intval($objResultRow->u_timeoffset);
		$this->m_bEmbedExternal = $objResultRow->u_embed_external?true:false;
		$this->m_bPrivateMessageNotification = $objResultRow->u_privatenotification?true:false;

		return true;
	}

	/**
	 * update data in database
	 *
	 * @return boolean success / failure
	 */
	public function updateData(){

		$bReturn = false;

		if(cDBFactory::getInstance()->executeQuery("UPDATE pxm_user SET u_visible=".intval($this->m_bIsVisible).",".
													"u_skinid=".$this->m_iSkinId.",".
													"u_threadlistsort=".cDBFactory::getInstance()->quote($this->m_sThreadListSortMode).",".
													"u_timeoffset=".$this->m_iTimeOffset.",".
													"u_embed_external=".intval($this->m_bEmbedExternal).",".
													"u_privatemail=".cDBFactory::getInstance()->quote($this->m_sPrivateMail).",".
													"u_privatenotification=".intval($this->m_bPrivateMessageNotification)." ".
								"WHERE u_id=".$this->m_iId)){
			$bReturn = true;
		}

		return $bReturn;
	}

	/**
	 * get additional database attributes for this object (template method)
	 *
	 * @return string additional database attributes for this object
	 */
	protected function _getDbAttributes(){
	 	return cUserPermissions::_getDbAttributes()
				.",u_visible,u_skinid,u_threadlistsort,"
				."u_timeoffset,u_embed_external,u_privatenotification";
	}

	/**
	 * is visible in online list?
	 *
	 * @return boolean visible / invisible
	 */
	public function isVisible(){
		return $this->m_bIsVisible;
	}

	/**
	 * set the visibility of the user in the onlinelist
	 *
	 * @param boolean $bVisible visible / invisible
	 * @return void
	 */
	public function setIsVisible($bVisible){
		$this->m_bIsVisible = $bVisible?true:false;
	}

	/**
	 * get skin id
	 *
	 * @return integer skin id
	 */
	public function getSkinId(){
		return $this->m_iSkinId;
	}

	/**
	 * set skin id
	 *
	 * @param integer $iSkinId skin id
	 * @return void
	 */
	public function setSkinId($iSkinId){
		$this->m_iSkinId = intval($iSkinId);
	}

	/**
	 * get sort mode for threadlist
	 *
	 * @return string sort mode for threadlist
	 */
	public function getThreadListSortMode(){
		return $this->m_sThreadListSortMode;
	}

	/**
	 * set sort mode for threadlist
	 *
	 * @param string $sThreadListSortMode sort mode for threadlist
	 * @return void
	 */
	public function setThreadListSortMode($sThreadListSortMode){
		$this->m_sThreadListSortMode = $sThreadListSortMode;
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
		if(($iTimeOffset<13) && ($iTimeOffset>-13)){
			$this->m_iTimeOffset = $iTimeOffset;
		}
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
	 * send private message notification?
	 *
	 * @return boolean send a notification?
	 */
	public function sendPrivateMessageNotification(){
		return $this->m_bPrivateMessageNotification;
	}

	/**
	 * set send private message notification
	 *
	 * @param boolean $bPrivateMessageNotification send a notification?
	 * @return void
	 */
	public function setSendPrivateMessageNotification($bPrivateMessageNotification){
		$this->m_bPrivateMessageNotification = $bPrivateMessageNotification?true:false;
	}

	/**
	 * get membervariables as array
	 *
	 * @param integer $iTimeOffs time offset in seconds
	 * @param string $sDateFormat php date format
	 * @return array member variables
	 */
	public function getDataArray($iTimeOffs = 0, $sDateFormat = "" , $objParser = null){
		// TODO: bessere Lösung für die übergabe von $iTimeOffset, $sDateFormat und $objParser finden bei Vererbung von cUserProfile
		return array("id"				=>	$this->m_iId,
					 "username"			=>	$this->m_sUserName,
					 "visible"			=>	$this->m_bIsVisible,
					 "skin"				=>	$this->m_iSkinId,
					 "sort"				=>	$this->m_sThreadListSortMode,
					 "toff"				=>	$this->m_iTimeOffset,
					 "embed_external"	=>	$this->m_bEmbedExternal,
					 "privatemail"		=>	$this->m_sPrivateMail,
					 "privnotification"	=>	$this->m_bPrivateMessageNotification);
	}
}
?>