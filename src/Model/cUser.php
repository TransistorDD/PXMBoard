<?php
require_once(SRCDIR . '/Enum/eUser.php');
/**
 * user handling
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cUser{

	protected int $m_iId;						// user id
	protected string $m_sUserName;				// user username
	protected string $m_sPassword;				// user password
	protected string $m_sPublicMail;			// public mailadress
	protected string $m_sPrivateMail;			// mailadress for internal use only
	protected string $m_sRegistrationMail;		// registration mailadress
	protected string $m_sFirstName;				// first name
	protected string $m_sLastName;				// last name
	protected string $m_sCity;					// user city
	protected string $m_sSignature;				// signature (will not be loaded in this class)
	protected string $m_sImgFileName;			// filename of profile picture
	protected int $m_iMessageQuantity;			// number of messages
	protected int $m_iRegistrationTimestamp;	// date of registration
	protected int $m_iLastOnlineTimestamp;		// last online timestamp
	protected bool $m_bHighlight;				// highlight user ?
	protected UserStatus $m_eStatus;			// status of the user
	protected int $m_iNotificationUnreadCount;	// unread notification count
	protected int $m_iPrivMessageUnreadCount;	// unread private message count

	/**
	 * Constructor
	 *
	 * @return void
	 */
	public function __construct(){

		$this->m_iId = 0;
		$this->m_sUserName = "";
		$this->m_sPassword = "";
		$this->m_sPublicMail = "";
		$this->m_sPrivateMail = "";
		$this->m_sRegistrationMail = "";
		$this->m_sFirstName = "";
		$this->m_sLastName = "";
		$this->m_sCity = "";
		$this->m_sSignature = "";
		$this->m_sImgFileName = "";
		$this->m_iMessageQuantity = 0;
		$this->m_iRegistrationTimestamp = 0;
		$this->m_iLastOnlineTimestamp = 0;
		$this->m_bHighlight = false;
		$this->m_eStatus = UserStatus::NOT_ACTIVATED;
		$this->m_iNotificationUnreadCount = 0;
		$this->m_iPrivMessageUnreadCount = 0;
	}

	/**
	 * get data from database by user id
	 *
	 * @param integer $iUserId user id
	 * @return boolean success / failure
	 */
	public function loadDataById(int $iUserId): bool{

		$bReturn = false;
		$iUserId = intval($iUserId);

		if($iUserId>0){
			if($objResultSet = cDBFactory::getInstance()->executeQuery("SELECT ".$this->_getDbAttributes()." FROM pxm_user WHERE u_id=".$iUserId)){
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
	 * get data from database by user username
	 *
	 * @param string $sUserName username
	 * @return boolean success / failure
	 */
	public function loadDataByUserName($sUserName){

		$bReturn = false;

		if(!empty($sUserName)){


			if($objResultSet = cDBFactory::getInstance()->executeQuery("SELECT ".$this->_getDbAttributes()." FROM pxm_user WHERE u_username=".cDBFactory::getInstance()->quote($sUserName))){
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
	 * get data from database by user ticket
	 *
	 * @param string $sTicket ticket
	 * @return boolean success / failure
	 */
	public function loadDataByTicket($sTicket){
		require_once(SRCDIR . '/Model/cUserLoginTicket.php');

		$bReturn = false;

		if(!empty($sTicket)){
			// Validate ticket and get user ID
			$iUserId = cUserLoginTicket::validateTicket($sTicket);

			if($iUserId > 0){
				// Load user data
				$bReturn = $this->loadDataById($iUserId);
			}
		}
		return $bReturn;
	}

	/**
	 * get data from database by password key
	 *
	 * @param string $sPasswordKey password key
	 * @return boolean success / failure
	 */
	public function loadDataByPasswordKey($sPasswordKey){

		$bReturn = false;

		if(!empty($sPasswordKey)){


			if($objResultSet = cDBFactory::getInstance()->executeQuery("SELECT ".$this->_getDbAttributes()." FROM pxm_user WHERE u_passwordkey=".cDBFactory::getInstance()->quote($sPasswordKey))){
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
	protected function _setDataFromDb($objResultRow){

		$this->m_iId = intval($objResultRow->u_id);
		$this->m_sUserName = $objResultRow->u_username;
		$this->m_sPassword = $objResultRow->u_password;
		$this->m_sFirstName = $objResultRow->u_firstname;
		$this->m_sLastName = $objResultRow->u_lastname;
		$this->m_sCity = $objResultRow->u_city;
		$this->m_sImgFileName = $objResultRow->u_imgfile;
		$this->m_sPublicMail = $objResultRow->u_publicmail;
		$this->m_sPrivateMail = $objResultRow->u_privatemail;
		$this->m_sRegistrationMail = $objResultRow->u_registrationmail;
		$this->m_iRegistrationTimestamp = intval($objResultRow->u_registrationtstmp);
		$this->m_iLastOnlineTimestamp = intval($objResultRow->u_lastonlinetstmp);
		$this->m_iMessageQuantity = intval($objResultRow->u_msgquantity);
		$this->m_bHighlight = $objResultRow->u_highlight?true:false;
		$this->m_eStatus = UserStatus::tryFrom($objResultRow->u_status) ?? UserStatus::NOT_ACTIVATED;
		$this->m_iNotificationUnreadCount = intval($objResultRow->u_notification_unread_count);
		$this->m_iPrivMessageUnreadCount = intval($objResultRow->u_priv_message_unread_count);

		return true;
	}

	/**
	 * get additional database attributes for this object (template method)
	 *
	 * @return string additional database attributes for this object
	 */
	protected function _getDbAttributes(){
	 	return "u_id,u_username,u_password,u_firstname,u_lastname,"
				."u_city,u_imgfile,u_publicmail,u_privatemail,u_registrationmail,"
				."u_registrationtstmp,u_lastonlinetstmp,u_msgquantity,u_highlight,u_status,"
				."u_notification_unread_count,u_priv_message_unread_count";
	 }

	/**
	 * insert a new user into the database
	 *
	 * @param boolean $bUniqueRegistrationMail should the registration email attribute be unique?
	 * @return boolean success / failure
	 */
	public function insertData($bUniqueRegistrationMail){

		$bReturn = false;

		if($objResultSet = cDBFactory::getInstance()->executeQuery("SELECT u_id FROM pxm_user WHERE u_username=".cDBFactory::getInstance()->quote($this->m_sUserName).
														  ($bUniqueRegistrationMail?" OR u_registrationmail=".cDBFactory::getInstance()->quote($this->m_sRegistrationMail):""))){
			if($objResultSet->getNumRows()<1){
				if(cDBFactory::getInstance()->executeQuery("INSERT INTO pxm_user (u_username,u_password,u_privatemail,u_registrationmail,u_registrationtstmp,u_status) ".
												  "VALUES (".cDBFactory::getInstance()->quote($this->m_sUserName).",".
												  			 cDBFactory::getInstance()->quote($this->m_sPassword).",".
															 cDBFactory::getInstance()->quote($this->m_sPrivateMail).",".
															 cDBFactory::getInstance()->quote($this->m_sRegistrationMail).",".
															 $this->m_iRegistrationTimestamp.",".
															 $this->m_eStatus->value.")")){
					$this->m_iId = cDBFactory::getInstance()->getInsertId("pxm_user","u_id");
					$bReturn = true;
				}
			}
		}
		return $bReturn;
	}

	/**
	 * update data in database
	 *
	 * @return boolean success / failure
	 */
	public function updateData(){

		$bReturn = false;

		if($this->m_iId>0){
			if($objResultSet = cDBFactory::getInstance()->executeQuery("UPDATE pxm_user SET u_password=".cDBFactory::getInstance()->quote($this->m_sPassword).",u_status=".$this->m_eStatus->value.",u_passwordkey='' WHERE u_id=".$this->m_iId)){
				if($objResultSet->getAffectedRows()>0){
					$bReturn = true;
				}
			}
		}
		return $bReturn;
	}

	/**
	 * delete a user from the database
	 *
	 * @return boolean success / failure
	 */
	public function deleteData(){

		$bReturn = false;

		if($objResultSet = cDBFactory::getInstance()->executeQuery("DELETE FROM pxm_user WHERE u_id=".$this->m_iId)){
			if($objResultSet->getAffectedRows()>0){
				$bReturn = true;
			}
		}
		return $bReturn;
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
	 * get username
	 *
	 * @return string username
	 */
	public function getUserName(){
		return $this->m_sUserName;
	}

	/**
	 * set username
	 *
	 * @param string $sUserName username
	 * @return void
	 */
	public function setUserName($sUserName){
		$this->m_sUserName = $sUserName;
	}

	/**
	 * get firstname
	 *
	 * @return string firstname
	 */
	public function getFirstName(){
		return $this->m_sFirstName;
	}

	/**
	 * set firstname
	 *
	 * @param string $sFirstName firstname
	 * @return void
	 */
	public function setFirstName($sFirstName){
		$this->m_sFirstName = $sFirstName;
	}

	/**
	 * get lastname
	 *
	 * @return string lastname
	 */
	public function getLastName(){
		return $this->m_sLastName;
	}

	/**
	 * set firstname
	 *
	 * @param string $sLastName lastname
	 * @return void
	 */
	public function setLastName($sLastName){
		$this->m_sLastName = $sLastName;
	}

	/**
	 * get city
	 *
	 * @return string city
	 */
	public function getCity(){
		return $this->m_sCity;
	}

	/**
	 * set city
	 *
	 * @param string $sCity city
	 * @return void
	 */
	public function setCity($sCity){
		$this->m_sCity = $sCity;
	}

	/**
	 * get signature
	 *
	 * @return string signature
	 */
	public function getSignature(){
		return $this->m_sSignature;
	}

	/**
	 * set signature
	 *
	 * @param string $sSignature signature
	 * @return void
	 */
	public function setSignature($sSignature){
		$this->m_sSignature = $sSignature;
	}

	/**
	 * get profile imagefilename
	 *
	 * @return string imagefilename
	 */
	public function getImageFileName(){
		return $this->m_sImgFileName;
	}

	/**
	 * set profile imagefilename
	 *
	 * @param string $sImgFileName imagefilename
	 * @return void
	 */
	public function setImageFileName($sImgFileName){
		$this->m_sImgFileName = $sImgFileName;
	}

	/**
	 * add profile image
	 *
	 * @param string $sImageDir profile image directory
	 * @param integer $iSplitImageDir split profile image directory after x entries
	 * @param string $sSrcFileName profile image sourcefile
	 * @param string $sImageType filetype (jpg, gif, png)
	 * @return boolean success / failure
	 */
	public function addImage($sImageDir,$iSplitImageDir,$sSrcFileName,$sImageType){


		$this->deleteImage($sImageDir);

		$sImageDir .= (floor($this->m_iId/$iSplitImageDir)*$iSplitImageDir)."/";

		if(!@is_dir($sImageDir)){
			if(!mkdir($sImageDir,0755)){
				return false;
			}
		}

		if(@move_uploaded_file($sSrcFileName,$sImageDir.$this->m_iId.".".$sImageType)){
			$this->m_sImgFileName = (floor($this->m_iId/$iSplitImageDir)*$iSplitImageDir)."/".$this->m_iId.".".$sImageType;
			if(!cDBFactory::getInstance()->executeQuery("UPDATE pxm_user SET u_imgfile=".cDBFactory::getInstance()->quote($this->m_sImgFileName)." WHERE u_id=".$this->m_iId)){
				return false;
			}
		}
		else{
			return false;
		}

		return true;
	}

	/**
	 * add delete profile image
	 *
	 * @param string $sImageDir profile image directory
	 * @return boolean success / failure
	 */
	public function deleteImage($sImageDir){


		if(!empty($this->m_sImgFileName)){
			if(!file_exists($sImageDir.$this->m_sImgFileName) || @unlink($sImageDir.$this->m_sImgFileName)){
				$this->m_sImgFileName = "";
				if(cDBFactory::getInstance()->executeQuery("UPDATE pxm_user SET u_imgfile='' WHERE u_id=".$this->m_iId)){
					return true;
				}
			}
		}
		return false;
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
	 * increment the msgquantity and save it to the database
	 *
	 * @return void
	 */
	public function incrementMessageQuantity(){


		cDBFactory::getInstance()->executeQuery("UPDATE pxm_user SET u_msgquantity=u_msgquantity+1 WHERE u_id=".$this->m_iId);
		++$this->m_iMessageQuantity;
	}

	/**
	 * get registration timestamp
	 *
	 * @return integer registration timestamp
	 */
	public function getRegistrationTimestamp(){
		return $this->m_iRegistrationTimestamp;
	}

	/**
	 * set registration timestamp
	 *
	 * @param integer $iRegistrationTimestamp registration timestamp
	 * @return void
	 */
	public function setRegistrationTimestamp($iRegistrationTimestamp){
		$this->m_iRegistrationTimestamp = intval($iRegistrationTimestamp);
	}

	/**
	 * get the password
	 *
	 * @return string password (bcrypt hash)
	 */
	public function getPassword(){
		return $this->m_sPassword;
	}

	/**
	 * set password
	 *
	 * @param string $sPassword password (bcrypt hash from password_hash())
	 * @return void
	 */
	public function setPassword($sPassword){
		$this->m_sPassword = $sPassword;
	}

	/**
	 * change the password and update the database
	 *
	 * @param string $sNewPassword new password (not encrypted)
	 * @param string $sNewPasswordConfirm new password confirm (not encrypted)
	 * @return boolean success / failure
	 */
	public function changePassword($sNewPassword,$sNewPasswordConfirm){

		$bReturn = false;

		if((strlen($sNewPassword)>2) && (strcmp($sNewPassword,$sNewPasswordConfirm)==0)){


			$sNewPasswordHash = password_hash($sNewPassword, PASSWORD_DEFAULT);

			if(cDBFactory::getInstance()->executeQuery("UPDATE pxm_user SET u_password=".cDBFactory::getInstance()->quote($sNewPasswordHash).",u_passwordkey='' WHERE u_password=".cDBFactory::getInstance()->quote($this->m_sPassword)." AND u_id=".$this->m_iId)){
				$this->m_sPassword = $sNewPasswordHash;

				// Delete all login tickets for security
				require_once(SRCDIR . '/Model/cUserLoginTicketList.php');
				cUserLoginTicketList::deleteAllTicketsForUser($this->m_iId);

				$bReturn = true;
			}
		}
		return $bReturn;
	}

	/**
	 * get the user status
	 *
	 * @return UserStatus user status
	 */
	public function getStatus(): UserStatus{
		return $this->m_eStatus;
	}

	/**
	 * set the user status
	 *
	 * @param UserStatus $status user status
	 * @return void
	 */
	public function setStatus(UserStatus $status): void{
		$this->m_eStatus = $status;
	}

	/**
	 * update the status of an user
	 *
	 * @return boolean success / failure
	 */
	public function updateStatus(){


		if(!cDBFactory::getInstance()->executeQuery("UPDATE pxm_user SET u_status=".$this->m_eStatus->value." WHERE u_id=".$this->m_iId)){
			return false;
		}
		return true;
	}

	/**
	 * get registration mail
	 *
	 * @return string registration mail
	 */
	public function getRegistrationMail(){
		return $this->m_sRegistrationMail;
	}

	/**
	 * validate and set registration mail
	 *
	 * @param string $sRegistrationMail registration mail address
	 * @param array $arrForbiddenMails forbidden mail address parts
	 * @return boolean success / failure
	 */
	public function setRegistrationMail($sRegistrationMail,$arrForbiddenMails = array()){
		$bReturn = true;

		if($this->_isValidEmail($sRegistrationMail)){
			foreach($arrForbiddenMails as $sMailPart){
				if(preg_match("/".$sMailPart."$/",$sRegistrationMail)){
					$bReturn = false;
				}
			}
			if($bReturn){
				$this->m_sRegistrationMail = $sRegistrationMail;
			}
		}
		else{
			$bReturn = false;
		}
		return $bReturn;
	}

	/**
	 * get private mail
	 *
	 * @return string private mail
	 */
	public function getPrivateMail(){
		return $this->m_sPrivateMail;
	}

	/**
	 * validate and set private mail
	 *
	 * @param string $sPrivateMail private mail address
	 * @return boolean success / failure
	 */
	public function setPrivateMail($sPrivateMail){
		if($this->_isValidEmail($sPrivateMail)){
			$this->m_sPrivateMail = $sPrivateMail;
			return true;
		}
		return false;
	}

	/**
	 * get public mail
	 *
	 * @return string public mail
	 */
	public function getPublicMail(){
		return $this->m_sPublicMail;
	}

	/**
	 * validate and set public mail
	 *
	 * @param string $sPublicMail public mail address
	 * @return boolean success / failure
	 */
	public function setPublicMail($sPublicMail){
		if(empty($sPublicMail) || $this->_isValidEmail($sPublicMail)){
			$this->m_sPublicMail = $sPublicMail;
			return true;
		}
		return false;
	}

	/**
	 * get last online timestamp
	 *
	 * @return integer last online timestamp
	 */
	public function getLastOnlineTimestamp(){
		return $this->m_iLastOnlineTimestamp;
	}

	/**
	 * set last online timestamp
	 *
	 * @param integer $iLastOnlineTimestamp last online timestamp
	 * @return void
	 */
	public function setLastOnlineTimestamp($iLastOnlineTimestamp){
		$this->m_iLastOnlineTimestamp = intval($iLastOnlineTimestamp);
	}

	/**
	 * update last online timestamp
	 *
	 * @param integer $iLastOnlineTimestamp last online timestamp
	 * @return boolean success / failure
	 */
	public function updateLastOnlineTimestamp($iLastOnlineTimestamp){


		$iLastOnlineTimestamp = intval($iLastOnlineTimestamp);

		if(cDBFactory::getInstance()->executeQuery("UPDATE pxm_user SET u_lastonlinetstmp=".$iLastOnlineTimestamp." WHERE u_id=".$this->m_iId)){
#			$this->m_iLastOnlineTimestamp = $iLastOnlineTimestamp;
		}
		else{
			return false;
		}
		return true;
	}

	/**
	 * should the user be highlighted?
	 *
	 * @return boolean highlight / don't highlight
	 */
	public function highlightUser(){
		return $this->m_bHighlight;
	}

	/**
	 * should the user be highlighted?
	 *
	 * @param  boolean $bHighlight highlight / don't highlight
	 * @return void
	 */
	public function setHighlightUser($bHighlight){
		$this->m_bHighlight = $bHighlight?true:false;
	}

	/**
	 * validate email
	 *
	 * @param string $sEmail email address
	 * @return boolean is valid / is not valid
	 */
	private function _isValidEmail($sEmail){
		if(!preg_match("/^[0-9a-zA-Z_-]+(\.[0-9a-zA-Z_-]+)*@[0-9a-zA-Z_-]+(\.[0-9a-zA-Z_-]+)*\.[a-zA-Z]{2,4}$/",$sEmail)){
			return false;
		}
		return true;
	}

	/**
	 * generate a new 12 char password
	 *
	 * @return string password (not encrypted)
	 */
	public function generatePassword(){

		$sAllowedChars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()-_=+[]{}<>?";
    	$sPassword = "";
		for ($i = 0; $i < 12; $i++) {
			$sPassword .= $sAllowedChars[random_int(0, strlen($sAllowedChars) - 1)];
		}
		$this->m_sPassword = password_hash($sPassword, PASSWORD_DEFAULT);
		return $sPassword;
	}

	/**
	 * checks if the password is valid
	 *
	 * @param string $sPassword password (not encrypted)
	 * @return boolean valid / invalid
	 */
	public function validatePassword($sPassword){
		$bReturn = false;
		
		if(strlen($sPassword) > 0){
			// Check if it's a legacy MD5 hash (32 hex characters)
			if($this->_isLegacyPasswordHash($this->m_sPassword)){
				// Validate against MD5
				if(strcmp($this->m_sPassword, md5($sPassword)) == 0){
					$bReturn = true;
					// Auto-migrate to modern hash
					$this->_migratePasswordHash($sPassword);
				}
			}
			else{
				// Modern password_hash (bcrypt/argon2)
				if(password_verify($sPassword, $this->m_sPassword)){
					$bReturn = true;
					// Check if hash needs rehashing (algorithm or cost changed)
					if(password_needs_rehash($this->m_sPassword, PASSWORD_DEFAULT)){
						$this->_migratePasswordHash($sPassword);
					}
				}
			}
		}
		
		return $bReturn;
	}

	/**
	 * check if password hash is a legacy MD5 hash
	 *
	 * @param string $sHash password hash
	 * @return boolean is legacy hash / is modern hash
	 */
	private function _isLegacyPasswordHash($sHash){
		// MD5 hashes are exactly 32 characters of hexadecimal
		return (strlen($sHash) == 32 && ctype_xdigit($sHash));
	}

	/**
	 * migrate password from MD5 to modern hash (bcrypt)
	 *
	 * @param string $sPassword plain text password
	 * @return boolean success / failure
	 */
	private function _migratePasswordHash($sPassword){
		
		$sNewHash = password_hash($sPassword, PASSWORD_DEFAULT);
		
		if(cDBFactory::getInstance()->executeQuery("UPDATE pxm_user SET u_password=".cDBFactory::getInstance()->quote($sNewHash)." WHERE u_id=".$this->m_iId)){
			$this->m_sPassword = $sNewHash;
			return true;
		}
		return false;
	}

	/**
	 * create a login ticket and store it in the database
	 *
	 * @param string $sUserAgent HTTP_USER_AGENT from request
	 * @param string $sIpAddress REMOTE_ADDR from request
	 * @return string ticket
	 */
	public function createNewTicket($sUserAgent, $sIpAddress){
		require_once(SRCDIR . '/Model/cUserLoginTicket.php');

		return cUserLoginTicket::createTicket($this->m_iId, $sUserAgent, $sIpAddress);
	}

	/**
	 * create a password key for password retrival and store it in the database
	 *
	 * @return string password key
	 */
	public function createNewPasswordKey(){
		$sPasswordKey = bin2hex(random_bytes(16));
		if(cDBFactory::getInstance()->executeQuery("UPDATE pxm_user SET u_passwordkey=".cDBFactory::getInstance()->quote($sPasswordKey)." WHERE u_id=".$this->m_iId)){
			return $sPasswordKey;
		}
		return "";
	}

	/**
	 * get membervariables as array
	 *
	 * @param integer $iTimeOffset time offset in seconds
	 * @param string $sDateFormat php date format
	 * @param object|null $objParser message parser (for signature)
	 * @return array member variables
	 */
	public function getDataArray($iTimeOffset,$sDateFormat,$objParser){
		return array(	"id"		=>	$this->m_iId,
						"username"	=>	$this->m_sUserName,
						"email"		=>	$this->m_sPublicMail,
						"fname"		=>	$this->m_sFirstName,
						"lname"		=>	$this->m_sLastName,
						"city"		=>	$this->m_sCity,
						"_signature"=>	$objParser?$objParser->parse($this->m_sSignature):$this->m_sSignature,
						"imgfile"	=>	$this->m_sImgFileName,
						"msgquan"	=>	$this->m_iMessageQuantity,
						"regdate"	=>	(($this->m_iRegistrationTimestamp>0)?date($sDateFormat,($this->m_iRegistrationTimestamp+$iTimeOffset)):0),
						"highlight"	=>	$this->m_bHighlight,
						"status" => $this->m_eStatus->value);
	}

	/**
	 * Get unread notification count
	 *
	 * @copyright Torsten Rentsch 2001 - 2026
	 * @return int Unread notification count
	 */
	public function getUnreadNotificationCount(): int{
		return $this->m_iNotificationUnreadCount;
	}

	/**
	 * Increment unread notification count
	 *
	 * @copyright Torsten Rentsch 2001 - 2026
	 * @return bool Success / Failure
	 */
	public function incrementNotificationCount(): bool{
		++$this->m_iNotificationUnreadCount;

		$result = cDBFactory::getInstance()->executeQuery(
			"UPDATE pxm_user SET u_notification_unread_count = u_notification_unread_count + 1 ".
			"WHERE u_id=".$this->m_iId
		);
		return $result !== null;
	}

	/**
	 * Decrement unread notification count
	 *
	 * @copyright Torsten Rentsch 2001 - 2026
	 * @return bool Success / Failure
	 */
	public function decrementNotificationCount(): bool{
		if($this->m_iNotificationUnreadCount > 0){
			$this->m_iNotificationUnreadCount--;
		}

		$objResult = cDBFactory::getInstance()->executeQuery(
			"UPDATE pxm_user SET u_notification_unread_count = GREATEST(0, u_notification_unread_count - 1) ".
			"WHERE u_id=".$this->m_iId
		);
		return $objResult !== null;
	}

	/**
	 * Recalculate unread notification count from database
	 *
	 * @copyright Torsten Rentsch 2001 - 2026
	 * @return bool Success / Failure
	 */
	public function recalculateNotificationCount(): bool{
		// Count actual unread notifications
		$sCountQuery = "SELECT COUNT(*) AS count FROM pxm_notification ".
					   "WHERE n_userid=".$this->m_iId." AND n_status='unread'";

		$iCount = 0;
		if($objResultSet = cDBFactory::getInstance()->executeQuery($sCountQuery)){
			if($objResultRow = $objResultSet->getNextResultRowObject()){
				$iCount = intval($objResultRow->count);
			}
		}

		// Update cache
		$this->m_iNotificationUnreadCount = $iCount;

		$objResult = cDBFactory::getInstance()->executeQuery(
			"UPDATE pxm_user SET u_notification_unread_count=".intval($iCount)." ".
			"WHERE u_id=".$this->m_iId
		);
		return $objResult !== null;
	}

	/**
	 * Get unread private message count
	 *
	 * @copyright Torsten Rentsch 2001 - 2026
	 * @return int Unread private message count
	 */
	public function getUnreadPrivMessageCount(): int{
		return $this->m_iPrivMessageUnreadCount;
	}

	/**
	 * Increment unread private message count
	 *
	 * @copyright Torsten Rentsch 2001 - 2026
	 * @return bool Success / Failure
	 */
	public function incrementPrivMessageCount(): bool{
		++$this->m_iPrivMessageUnreadCount;

		$result = cDBFactory::getInstance()->executeQuery(
			"UPDATE pxm_user SET u_priv_message_unread_count = u_priv_message_unread_count + 1 ".
			"WHERE u_id=".$this->m_iId
		);
		return $result !== null;
	}

	/**
	 * Decrement unread private message count
	 *
	 * @copyright Torsten Rentsch 2001 - 2026
	 * @return bool Success / Failure
	 */
	public function decrementPrivMessageCount(): bool{
		if($this->m_iPrivMessageUnreadCount > 0){
			$this->m_iPrivMessageUnreadCount--;
		}

		$result = cDBFactory::getInstance()->executeQuery(
			"UPDATE pxm_user SET u_priv_message_unread_count = IF(u_priv_message_unread_count > 0, u_priv_message_unread_count - 1, 0) ".
			"WHERE u_id=".$this->m_iId
		);
		return $result !== null;
	}

	/**
	 * Recalculate unread private message count from database
	 *
	 * @copyright Torsten Rentsch 2001 - 2026
	 * @return bool Success / Failure
	 */
	public function recalculatePrivMessageCount(): bool{
		// Count actual unread private messages
		$sCountQuery = "SELECT COUNT(*) AS count FROM pxm_priv_message ".
					   "WHERE p_touserid=".$this->m_iId." AND p_tostate=".cMessageStates::messageNew();

		$iCount = 0;
		if($objResultSet = cDBFactory::getInstance()->executeQuery($sCountQuery)){
			if($objResultRow = $objResultSet->getNextResultRowObject()){
				$iCount = intval($objResultRow->count);
			}
		}

		// Update cache
		$this->m_iPrivMessageUnreadCount = $iCount;

		$objResult = cDBFactory::getInstance()->executeQuery(
			"UPDATE pxm_user SET u_priv_message_unread_count=".intval($iCount)." ".
			"WHERE u_id=".$this->m_iId
		);
		return $objResult !== null;
	}
}
?>