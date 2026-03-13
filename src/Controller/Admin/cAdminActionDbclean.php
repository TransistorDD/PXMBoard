<?php

namespace PXMBoard\Controller\Admin;

use PXMBoard\Database\cDB;
use PXMBoard\Model\cBoardList;
use PXMBoard\Model\cNotificationList;
use PXMBoard\Model\cUser;
use PXMBoard\Model\cUserLoginTicketList;

/**
 * run db clean tool
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cAdminActionDbclean extends cAdminAction
{
    /**
     * Validate permissions - requires admin rights and valid CSRF token.
     *
     * @return bool true if admin and CSRF valid, false otherwise
     */
    public function validateBasePermissionsAndConditions(): bool
    {
        return $this->_requireValidCsrfToken() && $this->_requireAdminPermission();
    }

    /**
     * perform the action
     *
     * @return void
     */
    public function performAction(): void
    {


        $this->m_sOutput .= $this->_getHead();

        $bDeleteNoUserMsgs = ($this->m_objInputHandler->getIntFormVar('nousr', true, true, true) > 0);
        $iAccessTime = $this->m_objConfig->getAccessTimestamp();

        $objBoardList = new cBoardList();
        $arrClosedBoardIds = $objBoardList->closeAllBoards(); 	// close boards

        // delete moderators with invalid boardid or userid ///////////////////////////

        if ($this->m_objInputHandler->getIntFormVar('nomod', true, true, true) > 0) {
            if ($objResultSet = cDB::getInstance()->executeQuery('SELECT DISTINCT mod_userid FROM pxm_moderator LEFT JOIN pxm_user ON mod_userid=u_id WHERE u_id IS null')) {
                while ($objResultRow = $objResultSet->getNextResultRowObject()) {
                    cDB::getInstance()->executeQuery("DELETE FROM pxm_moderator WHERE mod_userid=$objResultRow->mod_userid");
                }
                $this->m_sOutput .= $this->_getAlert('moderatores with invalid userid deleted', 'success');
            }

            if ($objResultSet = cDB::getInstance()->executeQuery('SELECT DISTINCT mod_boardid FROM pxm_moderator LEFT JOIN pxm_board ON mod_boardid=b_id WHERE b_id IS null')) {
                while ($objResultRow = $objResultSet->getNextResultRowObject()) {
                    cDB::getInstance()->executeQuery("DELETE FROM pxm_moderator WHERE mod_boardid=$objResultRow->mod_boardid");
                }
                $this->m_sOutput .= $this->_getAlert('moderatores with invalid boardid deleted', 'success');
            }
        }

        // delete threads with invalid boardid ////////////////////////////////////////

        if ($this->m_objInputHandler->getIntFormVar('nobrd', true, true, true) > 0) {
            if ($objResultSet = cDB::getInstance()->executeQuery('SELECT t_id FROM pxm_thread LEFT JOIN pxm_board ON t_boardid=b_id WHERE b_id IS null')) {
                while ($objResultRow = $objResultSet->getNextResultRowObject()) {
                    cDB::getInstance()->executeQuery("DELETE FROM pxm_message WHERE m_threadid=$objResultRow->t_id");
                    cDB::getInstance()->executeQuery("DELETE FROM pxm_thread WHERE t_id=$objResultRow->t_id");
                }
                $this->m_sOutput .= $this->_getAlert('threads with invalid boardid deleted', 'success');
            }
        }

        // delete messages with invalid users /////////////////////////////////////////

        if ($bDeleteNoUserMsgs > 0) {
            if ($objResultSet = cDB::getInstance()->executeQuery('SELECT m_id FROM pxm_message LEFT JOIN pxm_user ON m_userid=u_id WHERE m_userid>0 AND u_id IS null')) {
                while ($objResultRow = $objResultSet->getNextResultRowObject()) {
                    cDB::getInstance()->executeQuery("DELETE FROM pxm_message WHERE m_id=$objResultRow->m_id");
                }
                $this->m_sOutput .= $this->_getAlert('threads with invalid userid deleted', 'success');
            }
        }

        // delete empty threads ///////////////////////////////////////////////////////

        if ($this->m_objInputHandler->getIntFormVar('nomsg', true, true, true) > 0) {
            if ($objResultSet = cDB::getInstance()->executeQuery('SELECT t_id FROM pxm_thread LEFT JOIN pxm_message ON t_id=m_threadid WHERE m_threadid IS null AND t_lastmsgtstmp<'.($iAccessTime - 300))) {
                while ($objResultRow = $objResultSet->getNextResultRowObject()) {
                    cDB::getInstance()->executeQuery("DELETE FROM pxm_thread WHERE t_id=$objResultRow->t_id");
                }
                $this->m_sOutput .= $this->_getAlert('empty threads deleted', 'success');
            }
        }

        // restore data ///////////////////////////////////////////////////////////////

        if (($this->m_objInputHandler->getIntFormVar('restrd', true, true, true) > 0) || $bDeleteNoUserMsgs) {

            // restore threads with more or less than 1 root message //////////////////////

            if ($objResultSet = cDB::getInstance()->executeQuery('SELECT m_threadid,COUNT(*) AS cou FROM pxm_message WHERE m_parentid=0 GROUP BY m_threadid HAVING COUNT(*)!=1')) {
                while ($objResultRow = $objResultSet->getNextResultRowObject()) {
                    if ($objResultRow->cou !== 1) {
                        if ($objResultSet = cDB::getInstance()->executeQuery("SELECT m_id FROM pxm_message WHERE m_threadid=$objResultRow->m_threadid ORDER BY m_tstmp ASC")) {
                            if ($objResultRow2 = $objResultSet->getNextResultRowObject()) {
                                if ($objResultRow->cou < 1) {
                                    cDB::getInstance()->executeQuery("UPDATE pxm_message SET m_parentid=0 WHERE m_id=$objResultRow2->m_id");
                                } else {
                                    cDB::getInstance()->executeQuery("UPDATE pxm_message SET m_parentid=$objResultRow2->m_id WHERE m_id!=$objResultRow2->m_id AND m_parentid=0 AND m_threadid=$objResultRow->m_threadid");
                                }
                            }
                        }
                    }
                }
            }

            // restore messages without thread ////////////////////////////////////////////

            if ($objResultSet = cDB::getInstance()->executeQuery('SELECT DISTINCT m_threadid FROM pxm_message LEFT JOIN pxm_thread ON m_threadid=t_id WHERE t_id IS null AND m_parentid=0 AND m_tstmp<'.($iAccessTime - 300))) {
                while ($objResultRow = $objResultSet->getNextResultRowObject()) {
                    cDB::getInstance()->executeQuery('INSERT INTO pxm_thread VALUES()');
                    cDB::getInstance()->executeQuery('UPDATE pxm_message SET m_threadid='.cDB::getInstance()->getInsertId('pxm_thread', 't_id')." WHERE m_threadid=$objResultRow->m_threadid");
                }
            }

            // restore messages without parentmessage /////////////////////////////////////

            if ($objResultSet = cDB::getInstance()->executeQuery('SELECT DISTINCT m1.m_threadid AS threadid,m1.m_parentid AS parentid FROM pxm_message AS m1 LEFT JOIN pxm_message AS m2 ON m1.m_parentid=m2.m_id WHERE m2.m_id IS null AND m1.m_parentid>0')) {
                while ($objResultRow = $objResultSet->getNextResultRowObject()) {
                    if (cDB::getInstance()->executeQuery("SELECT m_id FROM pxm_message WHERE m_threadid=$objResultRow->threadid AND m_parentid=0")) {
                        if ($objResultRow2 = $objResultSet->getNextResultRowObject()) {
                            cDB::getInstance()->executeQuery("UPDATE pxm_message SET m_parentid=$objResultRow2->m_id WHERE m_parentid=$objResultRow->parentid");
                        }
                    }
                }
            }

            // update last msgid and date /////////////////////////////////////////////////

            if ($objResultSet = cDB::getInstance()->executeQuery('SELECT t_id,t_lastmsgid,t_lastmsgtstmp,m_id,m_tstmp FROM pxm_message,pxm_thread WHERE m_threadid=t_id ORDER BY t_id,m_tstmp DESC')) {

                $iThreadId = -1;

                while ($objResultRow = $objResultSet->getNextResultRowObject()) {
                    if ($iThreadId != $objResultRow->t_id) {
                        $iThreadId = $objResultRow->t_id;

                        if (($objResultRow->t_lastmsgid != $objResultRow->m_id) || ($objResultRow->t_lastmsgtstmp != $objResultRow->m_tstmp)) {
                            cDB::getInstance()->executeQuery("UPDATE pxm_thread SET t_lastmsgid=$objResultRow->m_id,t_lastmsgtstmp=$objResultRow->m_tstmp WHERE t_id=$objResultRow->t_id");
                        }
                    }
                }
            }

            if ($objResultSet = cDB::getInstance()->executeQuery('SELECT t_boardid,MAX(t_lastmsgtstmp) AS bmsgdate FROM pxm_thread GROUP BY t_boardid')) {
                while ($objResultRow = $objResultSet->getNextResultRowObject()) {
                    cDB::getInstance()->executeQuery("UPDATE pxm_board SET b_lastmsgtstmp=$objResultRow->bmsgdate WHERE b_id=$objResultRow->t_boardid");
                }
            }

            // update msg quantity /////////////////////////////////////////////////////////

            if ($objResultSet = cDB::getInstance()->executeQuery('SELECT m_threadid,COUNT(*) AS cou FROM pxm_message GROUP BY m_threadid')) {
                while ($objResultRow = $objResultSet->getNextResultRowObject()) {
                    cDB::getInstance()->executeQuery('UPDATE pxm_thread SET t_msgquantity='.($objResultRow->cou - 1)." WHERE t_id=$objResultRow->m_threadid");
                }
                $this->m_sOutput .= $this->_getAlert('messages and threads restored', 'success');
            }
        }
        $objBoardList->openBoards($arrClosedBoardIds);			// open boards

        // cleanup old login tickets //////////////////////////////////////////////////

        if ($this->m_objInputHandler->getIntFormVar('logintickets', true, true, true) > 0) {
            $iDeleted = cUserLoginTicketList::deleteInactiveTickets(180); // 6 months
            $this->m_sOutput .= $this->_getAlert('old login tickets deleted: '.$iDeleted, 'success');
        }

        // cleanup old notifications //////////////////////////////////////////////////

        if ($this->m_objInputHandler->getIntFormVar('cleannotifications', true, true, true) > 0) {

            // Auto-Aging: Mark notifications older than 7 days as read
            $iAutoAgeDays = 7;
            $iAutoAgeTimestamp = time() - ($iAutoAgeDays * 86400);

            $sQueryAge = 'UPDATE pxm_notification SET '.
                            "n_status='read', ".
                            'n_read_timestamp=UNIX_TIMESTAMP() '.
                            "WHERE n_status='unread' ".
                            'AND n_created_timestamp < '.$iAutoAgeTimestamp;

            $iAgedCount = 0;
            if ($objResultSet = cDB::getInstance()->executeQuery($sQueryAge)) {
                $iAgedCount = $objResultSet->getAffectedRows();
            }

            // Auto-Cleanup: Delete notifications older than 90 days
            $iAutoDeleteDays = 90;
            $iDeletedCount = cNotificationList::deleteOldNotifications($iAutoDeleteDays);

            // Recalculate all unread counts (safety measure)
            $sQueryUsers = 'SELECT u_id FROM pxm_user WHERE u_notification_unread_count > 0';
            $iRecalculated = 0;
            if ($objResultSet = cDB::getInstance()->executeQuery($sQueryUsers)) {
                while ($objResultRow = $objResultSet->getNextResultRowObject()) {
                    $objUser = new cUser();
                    if ($objUser->loadDataById($objResultRow->u_id)) {
                        $objUser->recalculateNotificationCount();
                        $iRecalculated++;
                    }
                }
            }

            $this->m_sOutput .= $this->_getAlert('notifications cleaned: '.$iAgedCount.' aged, '.$iDeletedCount.' deleted, '.$iRecalculated.' recalculated', 'success');
        }

        // cleanup private message cache //////////////////////////////////////////////

        if ($this->m_objInputHandler->getIntFormVar('cleanprivmsgcache', true, true, true) > 0) {

            // Recalculate all unread private message counts (safety measure)
            $sQueryUsers = 'SELECT u_id FROM pxm_user WHERE u_priv_message_unread_count > 0';
            $iRecalculated = 0;
            if ($objResultSet = cDB::getInstance()->executeQuery($sQueryUsers)) {
                while ($objResultRow = $objResultSet->getNextResultRowObject()) {
                    $objUser = new cUser();
                    if ($objUser->loadDataById($objResultRow->u_id)) {
                        $objUser->recalculatePrivMessageCount();
                        $iRecalculated++;
                    }
                }
            }

            $this->m_sOutput .= $this->_getAlert('private message cache recalculated: '.$iRecalculated.' users', 'success');
        }
        $this->m_sOutput .= $this->_getFooter();
    }
}
