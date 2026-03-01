<?php
require_once(SRCDIR . '/Database/cDBFactory.php');
require_once(SRCDIR . '/Enum/eNotification.php');
require_once(SRCDIR . '/Model/cUser.php');
/**
 * User notification
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cNotification{

	protected int $m_iId;
	protected int $m_iUserId;
	protected string $m_sType;
	protected NotificationStatus $m_eStatus;
	protected string $m_sTitle;
	protected string $m_sMessage;
	protected string $m_sLink;
	protected int $m_iRelatedMessageId;
	protected int $m_iRelatedPmId;
	protected int $m_iCreatedTimestamp;
	protected int $m_iReadTimestamp;
	protected NotificationType $m_eType;
	protected string $m_sStatus;

	public function __construct(){
		$this->m_iId = 0;
		$this->m_iUserId = 0;
		$this->m_sType = '';
		$this->m_eStatus = NotificationStatus::UNREAD;
		$this->m_sTitle = '';
		$this->m_sMessage = '';
		$this->m_sLink = '';
		$this->m_iRelatedMessageId = 0;
		$this->m_iRelatedPmId = 0;
		$this->m_iCreatedTimestamp = 0;
		$this->m_iReadTimestamp = 0;
		$this->m_eType = NotificationType::REPLY;
		$this->m_sStatus = NotificationStatus::UNREAD->value;
	}

	/**
	 * Create a new notification
	 *
	 * @param int $iUserId User ID (recipient)
	 * @param NotificationType $eType Notification type
	 * @param string $sTitle Title
	 * @param string $sMessage Message
	 * @param string $sLink Link to target
	 * @param int $iRelatedMessageId Related message ID (optional)
	 * @param int $iRelatedPmId Related PM ID (optional)
	 * @return bool Success / Failure
	 */
	public static function createNotification(
		int $iUserId,
		NotificationType $eType,
		string $sTitle,
		string $sMessage,
		string $sLink = '',
		int $iRelatedMessageId = 0,
		int $iRelatedPmId = 0
	): bool{
		$objDb = cDBFactory::getInstance();
		$iUserId = intval($iUserId);
		$iTimestamp = time();

		if(($iUserId <= 0) || empty($sTitle)){
			return false;
		}

		$sQuery = "INSERT INTO pxm_notification (".
				  "n_userid, n_type, n_status, n_title, n_message, n_link, ".
				  "n_related_messageid, n_related_pmid, ".
				  "n_created_timestamp) ".
				  "VALUES (".
				  intval($iUserId).", ".
				  $objDb->quote($eType->value).", ".
				  $objDb->quote(NotificationStatus::UNREAD->value).", ".
				  $objDb->quote($sTitle).", ".
				  $objDb->quote($sMessage).", ".
				  $objDb->quote($sLink).", ".
				  (intval($iRelatedMessageId) > 0 ? intval($iRelatedMessageId) : "null").", ".
				  (intval($iRelatedPmId) > 0 ? intval($iRelatedPmId) : "null").", ".
				  intval($iTimestamp).
				  ")";

		if($objDb->executeQuery($sQuery)){
			// Update unread count in pxm_user
			$objUser = new cUser();
			if($objUser->loadDataById($iUserId)){
				$objUser->incrementNotificationCount();
			}
			return true;
		}
		return false;
	}

	/**
	 * Load notification by ID
	 *
	 * @param int $iId Notification ID
	 * @return bool Success / Failure
	 */
	public function loadDataById(int $iId): bool{
		$objDb = cDBFactory::getInstance();
		$iId = intval($iId);

		if($iId <= 0){
			return false;
		}

		$sQuery = "SELECT n_id, n_userid, n_type, n_status, n_title, n_message, n_link, ".
				  "n_related_messageid, n_related_pmid, ".
				  "n_created_timestamp, n_read_timestamp ".
				  "FROM pxm_notification ".
				  "WHERE n_id=".intval($iId);

		if($objResultSet = $objDb->executeQuery($sQuery)){
			if($objResultRow = $objResultSet->getNextResultRowObject()){
				$this->m_iId = intval($objResultRow->n_id);
				$this->m_iUserId = intval($objResultRow->n_userid);
				$this->m_eType = NotificationType::from($objResultRow->n_type);
				$this->m_eStatus = NotificationStatus::from($objResultRow->n_status);
				$this->m_sStatus = $objResultRow->n_status;
				$this->m_sTitle = $objResultRow->n_title;
				$this->m_sMessage = $objResultRow->n_message;
				$this->m_sLink = $objResultRow->n_link;
				$this->m_iRelatedMessageId = intval($objResultRow->n_related_messageid);
				$this->m_iRelatedPmId = intval($objResultRow->n_related_pmid);
				$this->m_iCreatedTimestamp = intval($objResultRow->n_created_timestamp);
				$this->m_iReadTimestamp = intval($objResultRow->n_read_timestamp);
				return true;
			}
		}
		return false;
	}

	/**
	 * Mark notification as read
	 *
	 * @return bool Success / Failure
	 */
	public function markAsRead(): bool{
		$objDb = cDBFactory::getInstance();

		if($this->m_iId <= 0){
			return false;
		}

		// Already read?
		if($this->m_sStatus == NotificationStatus::READ->value){
			return true;
		}

		$iTimestamp = time();
		$sQuery = "UPDATE pxm_notification SET ".
				  "n_status=".$objDb->quote(NotificationStatus::READ->value).", ".
				  "n_read_timestamp=".intval($iTimestamp)." ".
				  "WHERE n_id=".intval($this->m_iId);

		if($objDb->executeQuery($sQuery)){
			$this->m_sStatus = NotificationStatus::READ->value;
			$this->m_iReadTimestamp = $iTimestamp;

			// Update unread count in pxm_user
			$objUser = new cUser();
			if($objUser->loadDataById($this->m_iUserId)){
				$objUser->decrementNotificationCount();
			}
			return true;
		}
		return false;
	}

	// Getter methods
	public function getId(): int{ return $this->m_iId; }
	public function getUserId(): int{ return $this->m_iUserId; }
	public function getType(): NotificationType{ return $this->m_eType; }
	public function getStatus(): NotificationStatus{ return $this->m_eStatus; }
	public function getTitle(): string{ return $this->m_sTitle; }
	public function getMessage(): string{ return $this->m_sMessage; }
	public function getLink(): string{ return $this->m_sLink; }
	public function getRelatedMessageId(): int{ return $this->m_iRelatedMessageId; }
	public function getRelatedPmId(): int{ return $this->m_iRelatedPmId; }
	public function getCreatedTimestamp(): int{ return $this->m_iCreatedTimestamp; }
	public function getReadTimestamp(): int{ return $this->m_iReadTimestamp; }

	public function isUnread(): bool{
		return $this->m_eStatus == NotificationStatus::UNREAD;
	}
}
?>
