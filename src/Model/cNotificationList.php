<?php

require_once(SRCDIR . '/Model/cScrollList.php');
require_once(SRCDIR . '/Model/cNotification.php');
require_once(SRCDIR . '/Model/cUser.php');

/**
 * Notification list
 *
 * @author Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright Torsten Rentsch 2001 - 2026
 */
class cNotificationList extends cScrollList
{
    protected int $m_iUserId = 0;
    protected string $m_sStatus = '';

    /**
     * Set user ID for notifications
     *
     * @param int $iUserId User ID
     * @return void
     */
    public function setUserId(int $iUserId): void
    {
        $this->m_iUserId = $iUserId;
    }

    /**
     * Set notification status filter
     *
     * @param string $sStatus Notification status
     * @return void
     */
    public function setStatus(string $sStatus): void
    {
        $this->m_sStatus = $sStatus;
    }

    /**
     * Get database query for notifications
     *
     * @return string SQL query string
     */
    protected function _getQuery(): string
    {
        $sQuery = 'SELECT n_id, n_userid, n_type, n_status, n_title, n_message, n_link, '.
                  'n_related_messageid, n_related_pmid, '.
                  'n_created_timestamp, n_read_timestamp '.
                  'FROM pxm_notification '.
                  'WHERE n_userid='.intval($this->m_iUserId);

        if (!empty($this->m_sStatus)) {
            $sQuery .= ' AND n_status='.cDBFactory::getInstance()->quote($this->m_sStatus);
        }

        $sQuery .= ' ORDER BY n_created_timestamp DESC';

        return $sQuery;
    }

    /**
     * Set data from database result row
     *
     * @param object $objResultRow Database result row
     * @return bool Success / Failure
     */
    protected function _setDataFromDb(object $objResultRow): bool
    {
        $this->m_arrResultList[] = [
            'id' => (int) $objResultRow->n_id,
            'userid' => (int) $objResultRow->n_userid,
            'type' => $objResultRow->n_type,
            'status' => $objResultRow->n_status,
            'title' => $objResultRow->n_title,
            'message' => $objResultRow->n_message,
            'link' => $objResultRow->n_link,
            'related_messageid' => (int) $objResultRow->n_related_messageid,
            'related_pmid' => (int) $objResultRow->n_related_pmid,
            'created_timestamp' => (int) $objResultRow->n_created_timestamp,
            'read_timestamp' => (int) $objResultRow->n_read_timestamp,
            'is_unread' => ($objResultRow->n_status == 'unread')
        ];
        return true;
    }

    /**
     * Mark all notifications as read for user
     *
     * @param int $iUserId User ID
     * @return bool Success / Failure
     */
    public static function markAllAsRead(int $iUserId): bool
    {
        $objDb = cDBFactory::getInstance();

        $iTimestamp = time();

        if ($iUserId <= 0) {
            return false;
        }

        $sQuery = 'UPDATE pxm_notification SET '.
                  "n_status='read', ".
                  'n_read_timestamp='.intval($iTimestamp).' '.
                  'WHERE n_userid='.intval($iUserId).' '.
                  "AND n_status='unread'";

        if ($objDb->executeQuery($sQuery)) {
            // Reset unread count to 0
            $objDb->executeQuery(
                'UPDATE pxm_user SET u_notification_unread_count=0 '.
                'WHERE u_id='.intval($iUserId)
            );
            return true;
        }
        return false;
    }

    /**
     * Delete old notifications
     *
     * @param int $iDaysOld Delete notifications older than X days
     * @return int Number of deleted notifications
     */
    public static function deleteOldNotifications(int $iDaysOld = 90): int
    {
        $iCutoffTimestamp = time() - ($iDaysOld * 86400);

        $sQuery = 'DELETE FROM pxm_notification '.
                  'WHERE n_created_timestamp < '.intval($iCutoffTimestamp);

        if ($objResult = cDBFactory::getInstance()->executeQuery($sQuery)) {
            return $objResult->getAffectedRows();
        }
        return 0;
    }
}
