<?php

namespace PXMBoard\Model;

use PXMBoard\Database\cDB;
use PXMBoard\Enum\eErrorKeys;
use PXMBoard\Exception\cCircularReferenceException;
use PXMBoard\Exception\cInvalidBoardException;
use PXMBoard\Exception\cInvalidParentException;
use PXMBoard\Exception\cSelfReferenceException;
use PXMBoard\Parser\cParser;
use PXMBoard\Search\cSearchEngineFactory;

/**
 * boardmessage handling
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cBoardMessage extends cMessage
{
    protected int $m_iBoardId = 0;				    // board id
    protected int $m_iThreadId = 0;				    // thread id
    protected bool $m_bThreadIsActive = true;		// thread status
    protected bool $m_bNotifyOnReply = false;		// notify author on reply
    protected ?bool $m_bIsRead = null;				// is message read (for logged-in users)?

    protected cMessageHeader $m_objReplyMsg;		// reply to message

    /**
     * Constructor
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->m_objReplyMsg = new cMessageHeader();
    }

    /**
     * get data from database by message id
     *
     * @param  int  $iMessageId  message id
     * @param  int  $iBoardId  board id (will be checked for more security)
     * @return bool success / failure
     */
    public function loadDataById(int $iMessageId, int $iBoardId = -1): bool
    {
        // TODO: bessere Lösung für die übergabe von $iBoardId finden bei Vererbung von cMessageList
        return cMessage::loadDataById($iMessageId) && $this->m_iBoardId == $iBoardId;
    }

    /**
     * initalize the member variables with the resultset from the db
     *
     * @param  object  $objResultRow  resultrow from db query
     * @return bool success / failure
     */
    protected function _setDataFromDb(object $objResultRow): bool
    {
        cMessage::_setDataFromDb($objResultRow);

        $this->m_iBoardId = (int) $objResultRow->t_boardid;
        $this->m_iThreadId = (int) $objResultRow->t_id;
        $this->m_bThreadIsActive = $objResultRow->t_active ? true : false;

        // author data
        $this->m_objAuthor->setFirstName((string) ($objResultRow->u_firstname ?? ''));
        $this->m_objAuthor->setLastName((string) ($objResultRow->u_lastname ?? ''));
        $this->m_objAuthor->setCity((string) ($objResultRow->u_city ?? ''));
        $this->m_objAuthor->setImageFileName((string) ($objResultRow->u_imgfile ?? ''));
        $this->m_objAuthor->setRegistrationTimestamp((int) ($objResultRow->u_registrationtstmp ?? 0));
        $this->m_objAuthor->setLastOnlineTimestamp((int) ($objResultRow->u_lastonlinetstmp ?? 0));
        $this->m_objAuthor->setMessageQuantity((int) ($objResultRow->u_msgquantity ?? 0));
        $this->m_objAuthor->setSignature((string) ($objResultRow->u_signature ?? ''));

        $this->m_objReplyMsg = new cMessageHeader();
        $this->m_objReplyMsg->loadDataById($objResultRow->m_parentid);
        $this->m_bNotifyOnReply = $objResultRow->m_notify_on_reply ? true : false;

        return true;
    }

    /**
     * get additional database attributes for this object (template method)
     *
     * @return string additional database attributes for this object
     */
    protected function _getDbAttributes(): string
    {
        return cMessage::_getDbAttributes()
                .',t_id,t_active,t_boardid,m_parentid,m_notify_on_reply'
                .',u_firstname,u_lastname,u_city,u_imgfile,u_registrationtstmp'
                .',u_lastonlinetstmp,u_msgquantity,u_signature';
    }

    /**
     * get additional database tables for this object (template method).
     * will perform a left outer join and needs pxm_message as last table from parent class!
     *
     * @return string additional database tables for this object
     */
    protected function _getDbTables(): string
    {
        return 'pxm_thread,'.cMessage::_getDbTables().' LEFT OUTER JOIN pxm_user ON (m_userid=u_id)';
    }

    /**
     * get additional database tables for this object (template method)
     *
     * @return string additional database join for this object
     */
    protected function _getDbJoin(): string
    {
        return cMessage::_getDbJoin().' AND t_id=m_threadid';
    }

    /**
     * insert new data into database and set $this->m_iThreadId if successfull
     *
     * @param  int  $iParentId  parent id
     * @param  int  $iAutoClose  message limit per thread (thread will be closed when reached)
     * @return ?eErrorKeys null on success, eErrorKeys enum on failure
     */
    public function insertData(int $iParentId, int $iAutoClose): ?eErrorKeys
    {
        $eError = eErrorKeys::COULD_NOT_INSERT_DATA;

        if (! empty($this->m_sSubject)) {
            // dupcheck
            $objResultSet = cDB::getInstance()->executeQuery('SELECT 1 FROM pxm_message'.
                                                                " WHERE   m_parentid=$iParentId".
                                                                    ' AND m_userid='.$this->m_objAuthor->getId().
                                                                    ' AND m_tstmp>'.($this->m_iMessageTimestamp - 259200).
                                                                    ' AND m_subject='.cDB::getInstance()->quote($this->m_sSubject), 1);
            if ($objResultSet) {
                if (!$objResultSet->getNextResultRowObject()) {
                    if ($iParentId < 1) {							// new thread
                        if (cDB::getInstance()->executeQuery("INSERT INTO pxm_thread (t_boardid,t_active,t_lastmsgtstmp) VALUES ($this->m_iBoardId,1,$this->m_iMessageTimestamp)")) {
                            if (($this->m_iThreadId = cDB::getInstance()->getInsertId('pxm_thread', 't_id')) > 0) {
                                if ($objResultSet = cDB::getInstance()->executeQuery('INSERT INTO pxm_message (m_threadid,m_parentid,m_userid,m_username,m_usermail,m_userhighlight,m_subject,m_body,m_tstmp,m_ip,m_notify_on_reply,m_status)'.
                                                                                       " VALUES ($this->m_iThreadId,".
                                                                                                 '0,'.
                                                                                                 $this->m_objAuthor->getId().','.
                                                                                                 cDB::getInstance()->quote($this->m_objAuthor->getUserName()).','.
                                                                                                 cDB::getInstance()->quote($this->m_objAuthor->getPublicMail()).','.
                                                                                                 intval($this->m_objAuthor->highlightUser()).','.
                                                                                                 cDB::getInstance()->quote($this->m_sSubject).','.
                                                                                                 cDB::getInstance()->quote($this->m_sBody).','.
                                                                                                 $this->m_iMessageTimestamp.','.
                                                                                                 cDB::getInstance()->quote($this->m_sIp).','.
                                                                                                 intval($this->m_bNotifyOnReply).','.
                                                                                                 $this->m_eStatus->value.')')) {

                                    if ($objResultSet->getAffectedRows() > 0) {

                                        $this->m_iId = (int) cDB::getInstance()->getInsertId('pxm_message', 'm_id');

                                        // update board list
                                        cDB::getInstance()->executeQuery("UPDATE pxm_board SET b_lastmsgtstmp=$this->m_iMessageTimestamp WHERE b_id=$this->m_iBoardId");

                                        // Index message in search engine
                                        cSearchEngineFactory::getInstance()->indexMessage(
                                            $this->m_iId,
                                            $this->m_iThreadId,
                                            $this->m_iBoardId,
                                            0, // parent_id = 0 for root messages
                                            $this->m_objAuthor->getId(),
                                            $this->m_objAuthor->getUserName(),
                                            $this->m_sSubject,
                                            $this->m_sBody,
                                            $this->m_iMessageTimestamp,
                                            $this->m_eStatus->value
                                        );

                                        // success
                                        $eError = null;
                                    } else {
                                        cDB::getInstance()->executeQuery("DELETE FROM pxm_thread WHERE t_id=$this->m_iThreadId");
                                    }
                                } else {
                                    cDB::getInstance()->executeQuery("DELETE FROM pxm_thread WHERE t_id=$this->m_iThreadId");
                                }
                            }
                        }
                    } else {						// reply
                        if ($objResultSet = cDB::getInstance()->executeQuery("SELECT m_threadid,t_active FROM pxm_thread,pxm_message WHERE t_id=m_threadid AND t_boardid=$this->m_iBoardId AND m_id=$iParentId")) {
                            if ($objResultRow = $objResultSet->getNextResultRowObject()) {
                                $objResultSet->freeResult();
                                if ($objResultRow->t_active == 1) {

                                    $this->m_iThreadId = (int) $objResultRow->m_threadid;

                                    if ($objResultSet = cDB::getInstance()->executeQuery('INSERT INTO pxm_message (m_threadid,m_parentid,m_userid,m_username,m_usermail,m_userhighlight,m_subject,m_body,m_tstmp,m_ip,m_notify_on_reply,m_status)'.
                                                                                       " VALUES ($this->m_iThreadId,".
                                                                                                 $iParentId.','.
                                                                                                 $this->m_objAuthor->getId().','.
                                                                                                 cDB::getInstance()->quote($this->m_objAuthor->getUserName()).','.
                                                                                                 cDB::getInstance()->quote($this->m_objAuthor->getPublicMail()).','.
                                                                                                 intval($this->m_objAuthor->highlightUser()).','.
                                                                                                 cDB::getInstance()->quote($this->m_sSubject).','.
                                                                                                 cDB::getInstance()->quote($this->m_sBody).','.
                                                                                                 $this->m_iMessageTimestamp.','.
                                                                                                 cDB::getInstance()->quote($this->m_sIp).','.
                                                                                                 intval($this->m_bNotifyOnReply).','.
                                                                                                 $this->m_eStatus->value.')')) {
                                        if ($objResultSet->getAffectedRows() > 0) {

                                            $this->m_iId = (int) cDB::getInstance()->getInsertId('pxm_message', 'm_id');

                                            // update thread list
                                            cDB::getInstance()->executeQuery("UPDATE pxm_thread SET t_lastmsgtstmp=$this->m_iMessageTimestamp,t_lastmsgid=$this->m_iId,t_msgquantity=t_msgquantity+1 WHERE t_id=$this->m_iThreadId");

                                            // update board list
                                            cDB::getInstance()->executeQuery("UPDATE pxm_board SET b_lastmsgtstmp=$this->m_iMessageTimestamp WHERE b_id=$this->m_iBoardId");

                                            // close the thread when the messagelimit is reached
                                            if ($iAutoClose > 0) {
                                                cDB::getInstance()->executeQuery("UPDATE pxm_thread SET t_active=0 WHERE t_id=$this->m_iThreadId AND t_msgquantity>=$iAutoClose");
                                            }

                                            // Index message in search engine
                                            cSearchEngineFactory::getInstance()->indexMessage(
                                                $this->m_iId,
                                                $this->m_iThreadId,
                                                $this->m_iBoardId,
                                                $iParentId, // reply has parent_id set
                                                $this->m_objAuthor->getId(),
                                                $this->m_objAuthor->getUserName(),
                                                $this->m_sSubject,
                                                $this->m_sBody,
                                                $this->m_iMessageTimestamp,
                                                $this->m_eStatus->value
                                            );

                                            // success
                                            $eError = null;
                                        }
                                    }
                                } else {
                                    $eError = eErrorKeys::THREAD_CLOSED;
                                }
                            } else {
                                $eError = eErrorKeys::INVALID_MESSAGE_ID;
                            }
                        }
                    }
                } else {
                    $eError = eErrorKeys::MESSAGE_ALREADY_EXISTS;
                }
            }
        } else {
            $eError = eErrorKeys::SUBJECT_MISSING;
        }

        return $eError;
    }

    /**
     * update data in database
     *
     * @return ?eErrorKeys null on success, eErrorKeys enum on failure
     */
    public function updateData(): ?eErrorKeys
    {
        $eError = eErrorKeys::COULD_NOT_UPDATE_DATA;

        if (! empty($this->m_sSubject)) {
            if ($this->m_iId > 0) {
                if ($objResultSet = cDB::getInstance()->executeQuery('UPDATE pxm_message SET m_subject='.cDB::getInstance()->quote($this->m_sSubject).','.
                                                                                'm_body='.cDB::getInstance()->quote($this->m_sBody).','.
                                                                                'm_notify_on_reply='.intval($this->m_bNotifyOnReply).','.
                                                                                'm_status='.$this->m_eStatus->value.','.
                                                                                'm_tstmp='.$this->m_iMessageTimestamp.
                                                                            " WHERE m_id=$this->m_iId")) {
                    if ($objResultSet->getAffectedRows() > 0) {
                        // Update search engine index if something changed
                        cSearchEngineFactory::getInstance()->indexMessage(
                            $this->m_iId,
                            $this->m_iThreadId,
                            $this->m_iBoardId,
                            $this->m_objReplyMsg->getId(), // parent_id
                            $this->m_objAuthor->getId(),
                            $this->m_objAuthor->getUserName(),
                            $this->m_sSubject,
                            $this->m_sBody,
                            $this->m_iMessageTimestamp,
                            $this->m_eStatus->value
                        );
                    }
                    $eError = null;
                }
            } else {
                $eError = eErrorKeys::INVALID_MESSAGE_ID;
            }
        } else {
            $eError = eErrorKeys::SUBJECT_MISSING;
        }

        return $eError;
    }

    /**
     * delete data from database
     *
     * @return bool success / failure
     */
    public function deleteData(): bool
    {
        $iParentId = $this->m_objReplyMsg->getId();
        if ($this->m_iId > 0 && $iParentId > 0) {
            // remove message
            cDB::getInstance()->executeQuery("DELETE FROM pxm_message WHERE m_id=$this->m_iId");

            // change parent message for replys to this message
            cDB::getInstance()->executeQuery("UPDATE pxm_message SET m_parentid=$iParentId WHERE m_parentid=$this->m_iId");

            // Remove message from search engine index
            cSearchEngineFactory::getInstance()->removeMessage($this->m_iId);

            if ($objResultSet = cDB::getInstance()->executeQuery("SELECT count(*) AS count,MAX(m_tstmp) AS maxd,MAX(m_id) AS maxid FROM pxm_message WHERE m_threadid=$this->m_iThreadId")) {
                if ($objResultRow = $objResultSet->getNextResultRowObject()) {
                    cDB::getInstance()->executeQuery("UPDATE pxm_thread SET t_msgquantity=$objResultRow->count-1,t_lastmsgid=$objResultRow->maxid,t_lastmsgtstmp=$objResultRow->maxd WHERE t_id=$this->m_iThreadId");
                }
            }
        } else {
            return false;
        }

        return true;
    }

    /**
     * get the number of replies to this message from database
     *
     * @return int number of replies
     */
    public function getReplyQuantity(): int
    {

        if ($objResultSet = cDB::getInstance()->executeQuery("SELECT count(*) AS count FROM pxm_message WHERE m_threadid=$this->m_iThreadId AND m_parentid=$this->m_iId")) {
            if ($objResultRow = $objResultSet->getNextResultRowObject()) {
                return $objResultRow->count;
            }
        }

        return 0;
    }

    /**
     * get thread id
     *
     * @return int thread id
     */
    public function getThreadId(): int
    {
        return $this->m_iThreadId;
    }

    /**
     * set thread id
     *
     * @param  int  $iThreadId  thread id
     */
    public function setThreadId(int $iThreadId): void
    {
        $this->m_iThreadId = $iThreadId;
    }

    /**
     * is thread active?
     *
     * @return bool thread is active / inactive
     */
    public function isThreadActive(): bool
    {
        return $this->m_bThreadIsActive;
    }

    /**
     * set is thread active?
     *
     * @param  bool  $bThreadIsActive  thread is active / inactive
     */
    public function setIsThreadActive(bool $bThreadIsActive): void
    {
        $this->m_bThreadIsActive = $bThreadIsActive ? true : false;
    }

    /**
     * get board id
     *
     * @return int board id
     */
    public function getBoardId(): int
    {
        return $this->m_iBoardId;
    }

    /**
     * set board id
     *
     * @param  int  $iBoardId  board id
     */
    public function setBoardId(int $iBoardId): void
    {
        $this->m_iBoardId = $iBoardId;
    }

    /**
     * get the id of the parent message
     *
     * @return int parent message id
     */
    public function getParentId(): int
    {
        return $this->m_objReplyMsg->getId();
    }

    /**
     * set the id of the parent message
     *
     * @param  int  $iParentId  parent message id
     */
    public function setParentId(int $iParentId): void
    {
        $this->m_objReplyMsg->setId($iParentId);
    }

    /**
     * should notify on reply?
     *
     * @return bool notify on reply
     */
    public function shouldNotifyOnReply(): bool
    {
        return $this->m_bNotifyOnReply;
    }

    /**
     * set notify on reply flag
     *
     * @param  bool  $bNotifyOnReply  notify on reply
     */
    public function setNotifyOnReply(bool $bNotifyOnReply): void
    {
        $this->m_bNotifyOnReply = $bNotifyOnReply ? true : false;
    }

    /**
     * is message read?
     *
     * @return bool is message read
     */
    public function isRead(): bool
    {
        return (bool)$this->m_bIsRead;
    }

    /**
     * set message read status
     *
     * @param  bool  $bIsRead  is message read
     */
    public function setIsRead(?bool $bIsRead): void
    {
        $this->m_bIsRead = $bIsRead;
    }

    /**
     * update the notify on reply flag
     *
     * @param  bool  $bNotifyOnReply  notify on reply
     * @return bool success / failure
     */
    public function updateNotifyOnReply(bool $bNotifyOnReply): bool
    {

        if (! cDB::getInstance()->executeQuery('UPDATE pxm_message SET m_notify_on_reply='.intval($bNotifyOnReply)." WHERE m_id=$this->m_iId")) {
            return false;
        }
        $this->m_bNotifyOnReply = $bNotifyOnReply ? true : false;

        return true;
    }

    /**
     * get membervariables as array
     *
     * @param  int  $iTimeOffset  time offset in seconds
     * @param  string  $sDateFormat  php date format
     * @param  int  $iLastLoginTimestamp  last login timestamp for user
     * @param  string  $sSubjectQuotePrefix  prefix for quoted subject
     * @param  ?cParser  $objParser  message parser
     * @return array<string, mixed> member variables
     */
    public function getDataArray(int $iTimeOffset, string $sDateFormat, int $iLastLoginTimestamp, string $sSubjectQuotePrefix = '', ?cParser $objParser = null): array
    {
        // TODO: Vererbung mit unterschiedlicher Methodensignatur optimieren
        return array_merge(
            cMessage::getDataArray($iTimeOffset, $sDateFormat, $iLastLoginTimestamp, $sSubjectQuotePrefix, $objParser),
            ['notify_on_reply' => $this->m_bNotifyOnReply,
                'thread' => ['id' => $this->m_iThreadId,
                'active' => (int) $this->m_bThreadIsActive,
                'brdid' => $this->m_iBoardId],
                'replyto' => $this->m_objReplyMsg->getDataArray($iTimeOffset, $sDateFormat, $iLastLoginTimestamp, '', $objParser),
                'is_read' => (int) $this->m_bIsRead,
                'is_draft' => $this->isDraft(),
                'status_label' => $this->m_eStatus->getLabel()]
        );
    }

    /**
     * Get all message IDs in subtree (including this message)
     *
     * @return array<int> Message IDs
     */
    public function getSubtreeMessageIds(): array
    {
        $arrIds = [];

        $sQuery = 'WITH RECURSIVE subtree AS (
					  SELECT m_id
					  FROM pxm_message
					  WHERE m_id = '.intval($this->m_iId).'
					  UNION ALL
					  SELECT m.m_id
					  FROM pxm_message m
					  INNER JOIN subtree s ON m.m_parentid = s.m_id
					)
					SELECT m_id FROM subtree';

        if ($objResultSet = cDB::getInstance()->executeQuery($sQuery)) {
            while ($objResultRow = $objResultSet->getNextResultRowObject()) {
                $arrIds[] = (int) $objResultRow->m_id;
            }
            $objResultSet->freeResult();
        }

        return $arrIds;
    }

    /**
     * Move this message (including subtree) to new parent
     * Validates all constraints and throws exceptions on error
     *
     * @param  int  $iNewParentId  New parent message ID
     * @return bool Success
     *
     * @throws cInvalidParentException When parent ID is invalid or message can't be loaded
     * @throws cSelfReferenceException When attempting to move message to itself
     * @throws cCircularReferenceException When target is in source's subtree
     * @throws cInvalidBoardException When messages are in different boards
     */
    public function moveToParent(int $iNewParentId): bool
    {
        $objDb = cDB::getInstance();

        // Validate parent ID
        if ($iNewParentId <= 0) {
            throw new cInvalidParentException('Invalid parent message ID: '.$iNewParentId);
        }

        // Check for self-reference
        if ($iNewParentId == $this->m_iId) {
            throw new cSelfReferenceException('Cannot move message to itself (ID: '.$this->m_iId.')');
        }

        // Load target parent message to get new thread ID
        $objTargetMessage = new cBoardMessage();
        if (! $objTargetMessage->loadDataById($iNewParentId, $this->m_iBoardId)) {
            throw new cInvalidParentException('Could not load target parent message (ID: '.$iNewParentId.')');
        }

        // Validate both messages are in same board
        if ($this->m_iBoardId != $objTargetMessage->getBoardId()) {
            throw new cInvalidBoardException('Cannot move message between different boards (source board: '.$this->m_iBoardId.', target board: '.$objTargetMessage->getBoardId().')');
        }

        // Get subtree IDs once for both validation and move operation
        $arrSubtreeIds = $this->getSubtreeMessageIds();
        $iSubtreeCount = count($arrSubtreeIds);

        // Safety check: ensure we have at least one ID
        if ($iSubtreeCount < 1) {
            throw new cInvalidParentException('Empty subtree for message ID: '.$this->m_iId);
        }

        // Prevent circular references: target must not be in source's subtree
        if (in_array($iNewParentId, $arrSubtreeIds)) {
            throw new cCircularReferenceException('Cannot move message into its own subtree (circular reference)');
        }

        $iNewThreadId = $objTargetMessage->getThreadId();
        $iOldThreadId = $this->m_iThreadId;

        // Start transaction
        $objDb->executeQuery('START TRANSACTION');

        try {
            // 1. Update parent ID for this message
            $sQuery = 'UPDATE pxm_message SET m_parentid='.intval($iNewParentId).
                      ' WHERE m_id='.intval($this->m_iId);
            if (! $objDb->executeQuery($sQuery)) {
                throw new \Exception('Failed to update parent ID');
            }

            // 2. Update thread ID for entire subtree (if moving to different thread)
            if ($iOldThreadId != $iNewThreadId) {
                $sIds = implode(',', array_map('intval', $arrSubtreeIds));
                $sQuery = 'UPDATE pxm_message SET m_threadid='.intval($iNewThreadId).
                          ' WHERE m_id IN ('.$sIds.')';
                if (! $objDb->executeQuery($sQuery)) {
                    throw new \Exception('Failed to update thread IDs');
                }

                // 3. Update message count in old thread (decrease)
                $sQuery = 'UPDATE pxm_thread SET t_msgquantity = t_msgquantity - '.intval($iSubtreeCount).
                          ' WHERE t_id='.intval($iOldThreadId);
                $objDb->executeQuery($sQuery);

                // 4. Update message count in new thread (increase)
                $sQuery = 'UPDATE pxm_thread SET t_msgquantity = t_msgquantity + '.intval($iSubtreeCount).
                          ' WHERE t_id='.intval($iNewThreadId);
                $objDb->executeQuery($sQuery);
            }

            // Commit transaction
            $objDb->executeQuery('COMMIT');

            // Update object state
            $this->m_objReplyMsg->setId($iNewParentId);
            $this->m_iThreadId = $iNewThreadId;

            return true;

        } catch (\Exception $e) {
            // Rollback on error
            $objDb->executeQuery('ROLLBACK');

            return false;
        }
    }

    /**
     * Check if notification is active for a specific user
     *
     * @param  int  $iUserId  User ID to check
     * @return bool True if notification is active, false otherwise
     */
    public function isNotificationActiveForUser(int $iUserId): bool
    {
        if ($iUserId <= 0 || $this->m_iId <= 0) {
            return false;
        }

        $sQuery = 'SELECT 1 FROM pxm_message_notification '.
                  'WHERE mn_messageid = ' . (int)$this->m_iId . ' ' .
                  'AND mn_userid = ' . (int)$iUserId;

        $objResultSet = cDB::getInstance()->executeQuery($sQuery, 1);

        return (bool)$objResultSet->getNextResultRowObject();
    }

    /**
     * Set notification status for a specific user
     *
     * Subscribes (INSERT) or unsubscribes (DELETE) a user from message notifications
     *
     * @param  int  $iUserId  User ID
     * @param  bool  $bActive  True to subscribe, false to unsubscribe
     * @return bool True on success, false on failure
     */
    public function setNotificationForUser(int $iUserId, bool $bActive): bool
    {
        if ($iUserId <= 0 || $this->m_iId <= 0) {
            return false;
        }

        if ($bActive) {
            // Subscribe: INSERT IGNORE (no error if already exists)
            $sQuery = 'INSERT IGNORE INTO pxm_message_notification (mn_messageid, mn_userid) '.
                      'VALUES ('.intval($this->m_iId).', '.intval($iUserId).')';
        } else {
            // Unsubscribe: DELETE
            $sQuery = 'DELETE FROM pxm_message_notification '.
                      'WHERE mn_messageid='.intval($this->m_iId).' AND mn_userid='.intval($iUserId);
        }

        cDB::getInstance()->executeQuery($sQuery);

        return true;
    }

    /**
     * Get all user IDs with active notification for this message
     *
     * @return array<int> Array of user IDs
     */
    public function getNotificationUserIds(): array
    {
        if ($this->m_iId <= 0) {
            return [];
        }

        $sQuery = 'SELECT mn_userid FROM pxm_message_notification '.
                  'WHERE mn_messageid='.intval($this->m_iId);
        $objResultSet = cDB::getInstance()->executeQuery($sQuery);

        $arrUserIds = [];
        while ($objRow = $objResultSet->getNextResultRowObject()) {
            $arrUserIds[] = (int) $objRow->mn_userid;
        }

        return $arrUserIds;
    }
}
