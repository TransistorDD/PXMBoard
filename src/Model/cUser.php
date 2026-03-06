<?php

namespace PXMBoard\Model;

use PXMBoard\Database\cDB;
use PXMBoard\Enum\ePrivateMessageStatus;
use PXMBoard\Enum\eUserStatus;

/**
 * user handling
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cUser
{
    protected int $m_iId = 0;						// user id
    protected string $m_sUserName = '';				// user username
    protected string $m_sPassword = '';				// user password
    protected string $m_sPublicMail = '';			// public mailadress
    protected string $m_sPrivateMail = '';			// mailadress for internal use only
    protected string $m_sRegistrationMail = '';		// registration mailadress
    protected string $m_sFirstName = '';			// first name
    protected string $m_sLastName = '';				// last name
    protected string $m_sCity = '';					// user city
    protected string $m_sSignature = '';			// signature (will not be loaded in this class)
    protected string $m_sImgFileName = '';			// filename of profile picture
    protected int $m_iMessageQuantity = 0;			// number of messages
    protected int $m_iRegistrationTimestamp = 0;	// date of registration
    protected int $m_iLastOnlineTimestamp = 0;		// last online timestamp
    protected bool $m_bHighlight = false;			// highlight user ?
    protected eUserStatus $m_eStatus = eUserStatus::NOT_ACTIVATED;	// status of the user
    protected int $m_iNotificationUnreadCount = 0;	// unread notification count
    protected int $m_iPrivMessageUnreadCount = 0;	// unread private message count

    /**
     * Constructor
     */
    public function __construct()
    {
    }

    /**
     * get data from database by user id
     *
     * @param int $iUserId user id
     * @return bool success / failure
     */
    public function loadDataById(int $iUserId): bool
    {
        $bReturn = false;

        if ($iUserId > 0) {
            if ($objResultSet = cDB::getInstance()->executeQuery('SELECT '.$this->_getDbAttributes().' FROM pxm_user WHERE u_id='.$iUserId)) {
                if ($objResultRow = $objResultSet->getNextResultRowObject()) {
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
     * @return bool success / failure
     */
    public function loadDataByUserName(string $sUserName): bool
    {
        $bReturn = false;

        if (!empty($sUserName)) {
            if ($objResultSet = cDB::getInstance()->executeQuery('SELECT '.$this->_getDbAttributes().' FROM pxm_user WHERE u_username='.cDB::getInstance()->quote($sUserName))) {
                if ($objResultRow = $objResultSet->getNextResultRowObject()) {
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
     * @return bool success / failure
     */
    public function loadDataByTicket(string $sTicket): bool
    {
        $bReturn = false;

        if (!empty($sTicket)) {
            // Validate ticket and get user ID
            $iUserId = cUserLoginTicket::validateTicket($sTicket);

            if ($iUserId > 0) {
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
     * @return bool success / failure
     */
    public function loadDataByPasswordKey(string $sPasswordKey): bool
    {
        $bReturn = false;

        if (!empty($sPasswordKey)) {
            if ($objResultSet = cDB::getInstance()->executeQuery('SELECT '.$this->_getDbAttributes().' FROM pxm_user WHERE u_passwordkey='.cDB::getInstance()->quote($sPasswordKey))) {
                if ($objResultRow = $objResultSet->getNextResultRowObject()) {
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
     * @return bool success / failure
     */
    protected function _setDataFromDb(object $objResultRow): bool
    {
        $this->m_iId = (int) $objResultRow->u_id;
        $this->m_sUserName = $objResultRow->u_username;
        $this->m_sPassword = $objResultRow->u_password;
        $this->m_sFirstName = $objResultRow->u_firstname;
        $this->m_sLastName = $objResultRow->u_lastname;
        $this->m_sCity = $objResultRow->u_city;
        $this->m_sImgFileName = $objResultRow->u_imgfile;
        $this->m_sPublicMail = $objResultRow->u_publicmail;
        $this->m_sPrivateMail = $objResultRow->u_privatemail;
        $this->m_sRegistrationMail = $objResultRow->u_registrationmail;
        $this->m_iRegistrationTimestamp = (int) $objResultRow->u_registrationtstmp;
        $this->m_iLastOnlineTimestamp = (int) $objResultRow->u_lastonlinetstmp;
        $this->m_iMessageQuantity = (int) $objResultRow->u_msgquantity;
        $this->m_bHighlight = (bool) $objResultRow->u_highlight;
        $this->m_eStatus = eUserStatus::tryFrom($objResultRow->u_status) ?? eUserStatus::NOT_ACTIVATED;
        $this->m_iNotificationUnreadCount = (int) $objResultRow->u_notification_unread_count;
        $this->m_iPrivMessageUnreadCount = (int) $objResultRow->u_priv_message_unread_count;

        return true;
    }

    /**
     * get additional database attributes for this object (template method)
     *
     * @return string additional database attributes for this object
     */
    protected function _getDbAttributes(): string
    {
        return 'u_id,u_username,u_password,u_firstname,u_lastname,'
                .'u_city,u_imgfile,u_publicmail,u_privatemail,u_registrationmail,'
                .'u_registrationtstmp,u_lastonlinetstmp,u_msgquantity,u_highlight,u_status,'
                .'u_notification_unread_count,u_priv_message_unread_count';
    }

    /**
     * insert a new user into the database
     *
     * @param bool $bUniqueRegistrationMail should the registration email attribute be unique?
     * @return bool success / failure
     */
    public function insertData(bool $bUniqueRegistrationMail): bool
    {
        $bReturn = false;

        if ($objResultSet = cDB::getInstance()->executeQuery('SELECT 1 FROM pxm_user WHERE u_username='.cDB::getInstance()->quote($this->m_sUserName).
                                                          ($bUniqueRegistrationMail ? ' OR u_registrationmail='.cDB::getInstance()->quote($this->m_sRegistrationMail) : '').
                                                          ' LIMIT 1')) {
            $bUserExists = (bool) $objResultSet->getNextResultRowObject();
            $objResultSet->freeResult();
            if (!$bUserExists) {
                if (cDB::getInstance()->executeQuery('INSERT INTO pxm_user (u_username,u_password,u_privatemail,u_registrationmail,u_registrationtstmp,u_status) '.
                                                  'VALUES ('.cDB::getInstance()->quote($this->m_sUserName).','.
                                                             cDB::getInstance()->quote($this->m_sPassword).','.
                                                             cDB::getInstance()->quote($this->m_sPrivateMail).','.
                                                             cDB::getInstance()->quote($this->m_sRegistrationMail).','.
                                                             $this->m_iRegistrationTimestamp.','.
                                                             $this->m_eStatus->value.')')) {
                    $this->m_iId = cDB::getInstance()->getInsertId('pxm_user', 'u_id');
                    $bReturn = true;
                }
            }
        }
        return $bReturn;
    }

    /**
     * update data in database
     *
     * @return bool success / failure
     */
    public function updateData(): bool
    {
        $bReturn = false;

        if ($this->m_iId > 0) {
            if ($objResultSet = cDB::getInstance()->executeQuery('UPDATE pxm_user SET u_password='.cDB::getInstance()->quote($this->m_sPassword).',u_status='.$this->m_eStatus->value.",u_passwordkey='' WHERE u_id=".$this->m_iId)) {
                if ($objResultSet->getAffectedRows() > 0) {
                    $bReturn = true;
                }
            }
        }
        return $bReturn;
    }

    /**
     * delete a user from the database
     *
     * @return bool success / failure
     */
    public function deleteData(): bool
    {
        $bReturn = false;

        if ($objResultSet = cDB::getInstance()->executeQuery('DELETE FROM pxm_user WHERE u_id='.$this->m_iId)) {
            if ($objResultSet->getAffectedRows() > 0) {
                $bReturn = true;
            }
        }
        return $bReturn;
    }

    /**
     * get id
     *
     * @return int id
     */
    public function getId(): int
    {
        return $this->m_iId;
    }

    /**
     * set id
     *
     * @param int $iId id
     * @return void
     */
    public function setId(int $iId): void
    {
        $this->m_iId = $iId;
    }

    /**
     * get username
     *
     * @return string username
     */
    public function getUserName(): string
    {
        return $this->m_sUserName;
    }

    /**
     * set username
     *
     * @param string $sUserName username
     * @return void
     */
    public function setUserName(string $sUserName): void
    {
        $this->m_sUserName = $sUserName;
    }

    /**
     * get firstname
     *
     * @return string firstname
     */
    public function getFirstName(): string
    {
        return $this->m_sFirstName;
    }

    /**
     * set firstname
     *
     * @param string $sFirstName firstname
     * @return void
     */
    public function setFirstName(string $sFirstName): void
    {
        $this->m_sFirstName = $sFirstName;
    }

    /**
     * get lastname
     *
     * @return string lastname
     */
    public function getLastName(): string
    {
        return $this->m_sLastName;
    }

    /**
     * set firstname
     *
     * @param string $sLastName lastname
     * @return void
     */
    public function setLastName(string $sLastName): void
    {
        $this->m_sLastName = $sLastName;
    }

    /**
     * get city
     *
     * @return string city
     */
    public function getCity(): string
    {
        return $this->m_sCity;
    }

    /**
     * set city
     *
     * @param string $sCity city
     * @return void
     */
    public function setCity(string $sCity): void
    {
        $this->m_sCity = $sCity;
    }

    /**
     * get signature
     *
     * @return string signature
     */
    public function getSignature(): string
    {
        return $this->m_sSignature;
    }

    /**
     * set signature
     *
     * @param string $sSignature signature
     * @return void
     */
    public function setSignature(string $sSignature): void
    {
        $this->m_sSignature = $sSignature;
    }

    /**
     * get profile imagefilename
     *
     * @return string imagefilename
     */
    public function getImageFileName(): string
    {
        return $this->m_sImgFileName;
    }

    /**
     * set profile imagefilename
     *
     * @param string $sImgFileName imagefilename
     * @return void
     */
    public function setImageFileName(string $sImgFileName): void
    {
        $this->m_sImgFileName = $sImgFileName;
    }

    /**
     * add profile image
     *
     * @param string $sImageDir profile image directory
     * @param int $iSplitImageDir split profile image directory after x entries
     * @param string $sSrcFileName profile image sourcefile
     * @param string $sImageType filetype (jpg, gif, png)
     * @return bool success / failure
     */
    public function addImage(string $sImageDir, int $iSplitImageDir, string $sSrcFileName, string $sImageType): bool
    {
        $this->deleteImage($sImageDir);

        $sImageDir .= (floor($this->m_iId / $iSplitImageDir) * $iSplitImageDir).'/';

        if (!@is_dir($sImageDir)) {
            if (!mkdir($sImageDir, 0755)) {
                return false;
            }
        }

        if (@move_uploaded_file($sSrcFileName, $sImageDir.$this->m_iId.'.'.$sImageType)) {
            $this->m_sImgFileName = (floor($this->m_iId / $iSplitImageDir) * $iSplitImageDir).'/'.$this->m_iId.'.'.$sImageType;
            if (!cDB::getInstance()->executeQuery('UPDATE pxm_user SET u_imgfile='.cDB::getInstance()->quote($this->m_sImgFileName).' WHERE u_id='.$this->m_iId)) {
                return false;
            }
        } else {
            return false;
        }
        return true;
    }

    /**
     * add delete profile image
     *
     * @param string $sImageDir profile image directory
     * @return bool success / failure
     */
    public function deleteImage(string $sImageDir): bool
    {
        if (!empty($this->m_sImgFileName)) {
            if (!file_exists($sImageDir.$this->m_sImgFileName) || @unlink($sImageDir.$this->m_sImgFileName)) {
                $this->m_sImgFileName = '';
                if (cDB::getInstance()->executeQuery("UPDATE pxm_user SET u_imgfile='' WHERE u_id=".$this->m_iId)) {
                    return true;
                }
            }
        }
        return false;
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
     * increment the msgquantity and save it to the database
     *
     * @return void
     */
    public function incrementMessageQuantity(): void
    {
        cDB::getInstance()->executeQuery('UPDATE pxm_user SET u_msgquantity=u_msgquantity+1 WHERE u_id='.$this->m_iId);
        ++$this->m_iMessageQuantity;
    }

    /**
     * get registration timestamp
     *
     * @return int registration timestamp
     */
    public function getRegistrationTimestamp(): int
    {
        return $this->m_iRegistrationTimestamp;
    }

    /**
     * set registration timestamp
     *
     * @param int $iRegistrationTimestamp registration timestamp
     * @return void
     */
    public function setRegistrationTimestamp(int $iRegistrationTimestamp): void
    {
        $this->m_iRegistrationTimestamp = $iRegistrationTimestamp;
    }

    /**
     * get the password
     *
     * @return string password (bcrypt hash)
     */
    public function getPassword(): string
    {
        return $this->m_sPassword;
    }

    /**
     * set password
     *
     * @param string $sPassword password (bcrypt hash from password_hash())
     * @return void
     */
    public function setPassword(string $sPassword): void
    {
        $this->m_sPassword = $sPassword;
    }

    /**
     * change the password and update the database
     *
     * @param string $sNewPassword new password (not encrypted)
     * @param string $sNewPasswordConfirm new password confirm (not encrypted)
     * @return bool success / failure
     */
    public function changePassword(string $sNewPassword, string $sNewPasswordConfirm): bool
    {
        $bReturn = false;

        if ((strlen($sNewPassword) > 2) && (strcmp($sNewPassword, $sNewPasswordConfirm) == 0)) {

            $sNewPasswordHash = password_hash($sNewPassword, PASSWORD_DEFAULT);

            if (cDB::getInstance()->executeQuery('UPDATE pxm_user SET u_password='.cDB::getInstance()->quote($sNewPasswordHash).",u_passwordkey='' WHERE u_password=".cDB::getInstance()->quote($this->m_sPassword).' AND u_id='.$this->m_iId)) {
                $this->m_sPassword = $sNewPasswordHash;

                // Delete all login tickets for security
                cUserLoginTicketList::deleteAllTicketsForUser($this->m_iId);

                $bReturn = true;
            }
        }
        return $bReturn;
    }

    /**
     * get the user status
     *
     * @return eUserStatus user status
     */
    public function getStatus(): eUserStatus
    {
        return $this->m_eStatus;
    }

    /**
     * set the user status
     *
     * @param eUserStatus $status user status
     * @return void
     */
    public function setStatus(eUserStatus $status): void
    {
        $this->m_eStatus = $status;
    }

    /**
     * update the status of an user
     *
     * @return bool success / failure
     */
    public function updateStatus(): bool
    {
        if (!cDB::getInstance()->executeQuery('UPDATE pxm_user SET u_status='.$this->m_eStatus->value.' WHERE u_id='.$this->m_iId)) {
            return false;
        }
        return true;
    }

    /**
     * get registration mail
     *
     * @return string registration mail
     */
    public function getRegistrationMail(): string
    {
        return $this->m_sRegistrationMail;
    }

    /**
     * validate and set registration mail
     *
     * @param string $sRegistrationMail registration mail address
     * @param array<string> $arrForbiddenMails forbidden mail address parts
     * @return bool success / failure
     */
    public function setRegistrationMail(string $sRegistrationMail, array $arrForbiddenMails = []): bool
    {
        $bReturn = true;

        if ($this->_isValidEmail($sRegistrationMail)) {
            foreach ($arrForbiddenMails as $sMailPart) {
                if (preg_match('/'.$sMailPart.'$/', $sRegistrationMail)) {
                    $bReturn = false;
                }
            }
            if ($bReturn) {
                $this->m_sRegistrationMail = $sRegistrationMail;
            }
        } else {
            $bReturn = false;
        }
        return $bReturn;
    }

    /**
     * get private mail
     *
     * @return string private mail
     */
    public function getPrivateMail(): string
    {
        return $this->m_sPrivateMail;
    }

    /**
     * validate and set private mail
     *
     * @param string $sPrivateMail private mail address
     * @return bool success / failure
     */
    public function setPrivateMail(string $sPrivateMail): bool
    {
        if ($this->_isValidEmail($sPrivateMail)) {
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
    public function getPublicMail(): string
    {
        return $this->m_sPublicMail;
    }

    /**
     * validate and set public mail
     *
     * @param string $sPublicMail public mail address
     * @return bool success / failure
     */
    public function setPublicMail(string $sPublicMail): bool
    {
        if (empty($sPublicMail) || $this->_isValidEmail($sPublicMail)) {
            $this->m_sPublicMail = $sPublicMail;
            return true;
        }
        return false;
    }

    /**
     * get last online timestamp
     *
     * @return int last online timestamp
     */
    public function getLastOnlineTimestamp(): int
    {
        return $this->m_iLastOnlineTimestamp;
    }

    /**
     * set last online timestamp
     *
     * @param int $iLastOnlineTimestamp last online timestamp
     * @return void
     */
    public function setLastOnlineTimestamp(int $iLastOnlineTimestamp): void
    {
        $this->m_iLastOnlineTimestamp = $iLastOnlineTimestamp;
    }

    /**
     * update last online timestamp
     *
     * @param int $iLastOnlineTimestamp last online timestamp
     * @return bool success / failure
     */
    public function updateLastOnlineTimestamp(int $iLastOnlineTimestamp): bool
    {
        $iLastOnlineTimestamp = $iLastOnlineTimestamp;

        if (cDB::getInstance()->executeQuery('UPDATE pxm_user SET u_lastonlinetstmp='.$iLastOnlineTimestamp.' WHERE u_id='.$this->m_iId)) {
            //TODO $this->m_iLastOnlineTimestamp = $iLastOnlineTimestamp;
        } else {
            return false;
        }
        return true;
    }

    /**
     * should the user be highlighted?
     *
     * @return bool highlight / don't highlight
     */
    public function highlightUser(): bool
    {
        return $this->m_bHighlight;
    }

    /**
     * should the user be highlighted?
     *
     * @param  bool $bHighlight highlight / don't highlight
     * @return void
     */
    public function setHighlightUser(bool $bHighlight): void
    {
        $this->m_bHighlight = $bHighlight;
    }

    /**
     * validate email
     *
     * @param string $sEmail email address
     * @return bool is valid / is not valid
     */
    private function _isValidEmail(string $sEmail): bool
    {
        if (!preg_match("/^[0-9a-zA-Z_-]+(\.[0-9a-zA-Z_-]+)*@[0-9a-zA-Z_-]+(\.[0-9a-zA-Z_-]+)*\.[a-zA-Z]{2,4}$/", $sEmail)) {
            return false;
        }
        return true;
    }

    /**
     * generate a new 12 char password
     *
     * @return string password (not encrypted)
     */
    public function generatePassword(): string
    {
        $sAllowedChars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()-_=+[]{}<>?';
        $sPassword = '';
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
     * @return bool valid / invalid
     */
    public function validatePassword(string $sPassword): bool
    {
        $bReturn = false;

        if (strlen($sPassword) > 0) {
            // Check if it's a legacy MD5 hash (32 hex characters)
            if ($this->_isLegacyPasswordHash($this->m_sPassword)) {
                // Validate against MD5
                if (strcmp($this->m_sPassword, md5($sPassword)) == 0) {
                    $bReturn = true;
                    // Auto-migrate to modern hash
                    $this->_migratePasswordHash($sPassword);
                }
            } else {
                // Modern password_hash (bcrypt/argon2)
                if (password_verify($sPassword, $this->m_sPassword)) {
                    $bReturn = true;
                    // Check if hash needs rehashing (algorithm or cost changed)
                    if (password_needs_rehash($this->m_sPassword, PASSWORD_DEFAULT)) {
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
     * @return bool is legacy hash / is modern hash
     */
    private function _isLegacyPasswordHash(string $sHash): bool
    {
        // MD5 hashes are exactly 32 characters of hexadecimal
        return (strlen($sHash) == 32 && ctype_xdigit($sHash));
    }

    /**
     * migrate password from MD5 to modern hash (bcrypt)
     *
     * @param string $sPassword plain text password
     * @return bool success / failure
     */
    private function _migratePasswordHash(string $sPassword): bool
    {
        $sNewHash = password_hash($sPassword, PASSWORD_DEFAULT);

        if (cDB::getInstance()->executeQuery('UPDATE pxm_user SET u_password='.cDB::getInstance()->quote($sNewHash).' WHERE u_id='.$this->m_iId)) {
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
    public function createNewTicket(string $sUserAgent, string $sIpAddress): string
    {
        return cUserLoginTicket::createTicket($this->m_iId, $sUserAgent, $sIpAddress);
    }

    /**
     * create a password key for password retrival and store it in the database
     *
     * @return string password key
     */
    public function createNewPasswordKey(): string
    {
        $sPasswordKey = bin2hex(random_bytes(16));
        if (cDB::getInstance()->executeQuery('UPDATE pxm_user SET u_passwordkey='.cDB::getInstance()->quote($sPasswordKey).' WHERE u_id='.$this->m_iId)) {
            return $sPasswordKey;
        }
        return '';
    }

    /**
     * get membervariables as array
     *
     * @param int $iTimeOffset time offset in seconds
     * @param string $sDateFormat php date format
     * @param object|null $objParser message parser (for signature)
     * @return array<string, mixed> member variables
     */
    public function getDataArray(int $iTimeOffset, string $sDateFormat, ?object $objParser): array
    {
        return ['id'		=>	$this->m_iId,
                'username'	=>	$this->m_sUserName,
                'email'		=>	$this->m_sPublicMail,
                'fname'		=>	$this->m_sFirstName,
                'lname'		=>	$this->m_sLastName,
                'city'		=>	$this->m_sCity,
                '_signature' =>	$objParser ? $objParser->parse($this->m_sSignature) : $this->m_sSignature,
                'imgfile'	=>	$this->m_sImgFileName,
                'msgquan'	=>	$this->m_iMessageQuantity,
                'regdate'	=>	(($this->m_iRegistrationTimestamp > 0) ? date($sDateFormat, ($this->m_iRegistrationTimestamp + $iTimeOffset)) : 0),
                'highlight'	=>	$this->m_bHighlight,
                'status'    => $this->m_eStatus->value];
    }

    /**
     * Get unread notification count
     *
     * @copyright Torsten Rentsch 2001 - 2026
     * @return int Unread notification count
     */
    public function getUnreadNotificationCount(): int
    {
        return $this->m_iNotificationUnreadCount;
    }

    /**
     * Increment unread notification count
     *
     * @copyright Torsten Rentsch 2001 - 2026
     * @return bool Success / Failure
     */
    public function incrementNotificationCount(): bool
    {
        ++$this->m_iNotificationUnreadCount;

        $result = cDB::getInstance()->executeQuery(
            'UPDATE pxm_user SET u_notification_unread_count = u_notification_unread_count + 1 '.
            'WHERE u_id='.$this->m_iId
        );
        return $result !== null;
    }

    /**
     * Decrement unread notification count
     *
     * @copyright Torsten Rentsch 2001 - 2026
     * @return bool Success / Failure
     */
    public function decrementNotificationCount(): bool
    {
        if ($this->m_iNotificationUnreadCount > 0) {
            $this->m_iNotificationUnreadCount--;
        }

        $objResult = cDB::getInstance()->executeQuery(
            'UPDATE pxm_user SET u_notification_unread_count = GREATEST(0, u_notification_unread_count - 1) '.
            'WHERE u_id='.$this->m_iId
        );
        return $objResult !== null;
    }

    /**
     * Recalculate unread notification count from database
     *
     * @copyright Torsten Rentsch 2001 - 2026
     * @return bool Success / Failure
     */
    public function recalculateNotificationCount(): bool
    {
        // Count actual unread notifications
        $sCountQuery = 'SELECT COUNT(*) AS count FROM pxm_notification '.
                       'WHERE n_userid='.$this->m_iId." AND n_status='unread'";

        $iCount = 0;
        if ($objResultSet = cDB::getInstance()->executeQuery($sCountQuery)) {
            if ($objResultRow = $objResultSet->getNextResultRowObject()) {
                $iCount = (int) $objResultRow->count;
            }
        }

        // Update cache
        $this->m_iNotificationUnreadCount = $iCount;

        $objResult = cDB::getInstance()->executeQuery(
            'UPDATE pxm_user SET u_notification_unread_count='.intval($iCount).' '.
            'WHERE u_id='.$this->m_iId
        );
        return $objResult !== null;
    }

    /**
     * Get unread private message count
     *
     * @copyright Torsten Rentsch 2001 - 2026
     * @return int Unread private message count
     */
    public function getUnreadPrivMessageCount(): int
    {
        return $this->m_iPrivMessageUnreadCount;
    }

    /**
     * Increment unread private message count
     *
     * @copyright Torsten Rentsch 2001 - 2026
     * @return bool Success / Failure
     */
    public function incrementPrivMessageCount(): bool
    {
        ++$this->m_iPrivMessageUnreadCount;

        $result = cDB::getInstance()->executeQuery(
            'UPDATE pxm_user SET u_priv_message_unread_count = u_priv_message_unread_count + 1 '.
            'WHERE u_id='.$this->m_iId
        );
        return $result !== null;
    }

    /**
     * Decrement unread private message count
     *
     * @copyright Torsten Rentsch 2001 - 2026
     * @return bool Success / Failure
     */
    public function decrementPrivMessageCount(): bool
    {
        if ($this->m_iPrivMessageUnreadCount > 0) {
            $this->m_iPrivMessageUnreadCount--;
        }

        $result = cDB::getInstance()->executeQuery(
            'UPDATE pxm_user SET u_priv_message_unread_count = IF(u_priv_message_unread_count > 0, u_priv_message_unread_count - 1, 0) '.
            'WHERE u_id='.$this->m_iId
        );
        return $result !== null;
    }

    /**
     * Recalculate unread private message count from database
     *
     * @copyright Torsten Rentsch 2001 - 2026
     * @return bool Success / Failure
     */
    public function recalculatePrivMessageCount(): bool
    {
        // Count actual unread private messages
        $sCountQuery = 'SELECT COUNT(*) AS count FROM pxm_priv_message '.
                       'WHERE p_touserid='.$this->m_iId.' AND p_tostate='.ePrivateMessageStatus::UNREAD->value;

        $iCount = 0;
        if ($objResultSet = cDB::getInstance()->executeQuery($sCountQuery)) {
            if ($objResultRow = $objResultSet->getNextResultRowObject()) {
                $iCount = (int) $objResultRow->count;
            }
        }

        // Update cache
        $this->m_iPrivMessageUnreadCount = $iCount;

        $objResult = cDB::getInstance()->executeQuery(
            'UPDATE pxm_user SET u_priv_message_unread_count='.intval($iCount).' '.
            'WHERE u_id='.$this->m_iId
        );
        return $objResult !== null;
    }
}
