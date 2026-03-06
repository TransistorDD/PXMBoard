<?php

namespace PXMBoard\Model;

use PXMBoard\Database\cDB;
use PXMBoard\Enum\eErrorKeys;
use PXMBoard\Enum\ePrivateMessageStatus;
use PXMBoard\Parser\cParser;

/**
 * private message handling
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cPrivateMessage extends cMessage
{
    protected int $m_iToUserId = 0;					                            // destination user id
    protected ePrivateMessageStatus $m_eToState = ePrivateMessageStatus::UNREAD;	// state for the recipient
    protected ePrivateMessageStatus $m_eFromState = ePrivateMessageStatus::READ;	// state for the sender

    /**
     * get data from database by message id
     *
     * @param int $iMessageId message id
     * @return bool success / failure
     */
    public function loadDataById(int $iMessageId): bool
    {
        $bReturn = false;

        if ($iMessageId > 0) {
            if ($objResultSet = cDB::getInstance()->executeQuery('SELECT p_id,'.
                                                            'p_subject,'.
                                                            'p_body,'.
                                                            'p_tstmp,'.
                                                            'p_touserid,'.
                                                            'p_tostate,'.
                                                            'u_id,'.
                                                            'u_username,'.
                                                            'u_publicmail,'.
                                                            'u_highlight,'.
                                                            'u_firstname,'.
                                                            'u_lastname,'.
                                                            'u_city,'.
                                                            'u_signature,'.
                                                            'u_imgfile,'.
                                                            'u_registrationtstmp,'.
                                                            'u_lastonlinetstmp,'.
                                                            'u_msgquantity,'.
                                                            'p_fromstate,'.
                                                            'p_ip'.
                                                            $this->_getDbAttributes().
                                                            ' FROM pxm_priv_message,pxm_user'.
                                                            $this->_getDbTables().
                                                            ' WHERE p_fromuserid=u_id'.
                                                            ' AND ('.
                                                            '(p_touserid='.$this->m_iToUserId.' AND p_tostate!='.ePrivateMessageStatus::DELETED->value.')'.
                                                            ' OR '.
                                                            '(p_fromuserid='.$this->m_objAuthor->getId().' AND p_fromstate!='.ePrivateMessageStatus::DELETED->value.')'.
                                                            ') AND p_id='.$iMessageId.
                                                            $this->_getDbJoin())) {
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
        $this->m_iId = (int) $objResultRow->p_id;
        $this->m_sSubject = $objResultRow->p_subject;
        $this->m_sBody = $objResultRow->p_body;
        $this->m_iMessageTimestamp = (int) $objResultRow->p_tstmp;
        $this->m_sIp = $objResultRow->p_ip;

        // recipient data
        $this->m_eToState = ePrivateMessageStatus::from((int) $objResultRow->p_tostate);
        $this->m_iToUserId = (int) $objResultRow->p_touserid;

        // author data
        $this->m_objAuthor->setId($objResultRow->u_id);
        $this->m_objAuthor->setUserName($objResultRow->u_username);
        $this->m_objAuthor->setPublicMail($objResultRow->u_publicmail);
        $this->m_objAuthor->setHighlightUser($objResultRow->u_highlight);
        $this->m_objAuthor->setFirstName($objResultRow->u_firstname);
        $this->m_objAuthor->setLastName($objResultRow->u_lastname);
        $this->m_objAuthor->setCity($objResultRow->u_city);
        $this->m_objAuthor->setImageFileName($objResultRow->u_imgfile);
        $this->m_objAuthor->setRegistrationTimestamp($objResultRow->u_registrationtstmp);
        $this->m_objAuthor->setLastOnlineTimestamp($objResultRow->u_lastonlinetstmp);
        $this->m_objAuthor->setMessageQuantity($objResultRow->u_msgquantity);
        $this->m_objAuthor->setSignature($objResultRow->u_signature);

        $this->m_eFromState = ePrivateMessageStatus::from((int) $objResultRow->p_fromstate);

        return true;
    }

    /**
     * get additional database attributes for this object (template method)
     *
     * @return string additional database attributes for this object
     */
    protected function _getDbAttributes(): string
    {
        return '';
    }

    /**
     * get additional database tables for this object (template method)
     *
     * @return string additional database tables for this object
     */
    protected function _getDbTables(): string
    {
        return '';
    }

    /**
     * get additional database tables for this object (template method)
     *
     * @return string additional database join for this object
     */
    protected function _getDbJoin(): string
    {
        return '';
    }

    /**
     * insert new data into database
     *
     * @return ?eErrorKeys null on success, eErrorKeys enum on failure
     */
    public function insertData(): ?eErrorKeys
    {
        $eError = eErrorKeys::COULD_NOT_INSERT_DATA;

        if ($this->m_iToUserId > 0 && $this->m_objAuthor->getId() > 0) {
            if (!empty($this->m_sSubject)) {
                if ($objResultSet = cDB::getInstance()->executeQuery('INSERT INTO pxm_priv_message (p_touserid,p_fromuserid,p_subject,p_body,p_tstmp,p_ip)'.
                                                                   " values ($this->m_iToUserId,".
                                                                             $this->m_objAuthor->getId().','.
                                                                             cDB::getInstance()->quote($this->m_sSubject).','.
                                                                             cDB::getInstance()->quote($this->m_sBody).','.
                                                                             $this->m_iMessageTimestamp.','.
                                                                             cDB::getInstance()->quote($this->m_sIp).')')) {
                    if ($objResultSet->getAffectedRows() > 0) {
                        $eError = null;
                        $this->m_iId = cDB::getInstance()->getInsertId('pxm_priv_message', 'p_id');

                        // Update unread count in pxm_user
                        $objUser = new cUser();
                        if ($objUser->loadDataById($this->m_iToUserId)) {
                            $objUser->incrementPrivMessageCount();
                        }
                    }
                }
            } else {
                $eError = eErrorKeys::SUBJECT_MISSING;
            }
        } else {
            $eError = eErrorKeys::INVALID_USER_ID;
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
        $bReturn = false;

        // set the message to deleted if we are the recipient
        if ($objResultSet = cDB::getInstance()->executeQuery('UPDATE pxm_priv_message SET p_tostate='.ePrivateMessageStatus::DELETED->value.
                                                           " WHERE p_touserid=$this->m_iToUserId AND p_id=$this->m_iId")) {
            if ($objResultSet->getAffectedRows() > 0) {
                $bReturn = true;

                // Decrement unread count if message was unread
                if ($this->m_eToState->isUnread()) {
                    $objUser = new cUser();
                    if ($objUser->loadDataById($this->m_iToUserId)) {
                        $objUser->decrementPrivMessageCount();
                    }
                }
            }
        }

        // set the message to deleted if we are the author
        if (!$bReturn && ($objResultSet = cDB::getInstance()->executeQuery('UPDATE pxm_priv_message SET p_fromstate='.ePrivateMessageStatus::DELETED->value.
                                                                ' WHERE p_fromuserid='.$this->m_objAuthor->getId()." AND p_id=$this->m_iId"))) {
            if ($objResultSet->getAffectedRows() > 0) {
                $bReturn = true;
            }
        }

        // remove all deleted messages from db
        cDB::getInstance()->executeQuery('DELETE FROM pxm_priv_message WHERE p_tostate='.ePrivateMessageStatus::DELETED->value.' AND p_fromstate='.ePrivateMessageStatus::DELETED->value);

        return $bReturn;
    }

    /**
     * get the id of the destination user
     *
     * @return int id of the destination user
     */
    public function getDestinationUserId(): int
    {
        return $this->m_iToUserId;
    }

    /**
     * set the id of the destination user
     *
     * @param int $iToUserId id of the destination user
     * @return void
     */
    public function setDestinationUserId(int $iToUserId): void
    {
        $this->m_iToUserId = $iToUserId;
    }

    /**
     * get the message state for the destination user
     *
     * @return ePrivateMessageStatus message state for the destination user
     */
    public function getDestinationState(): ePrivateMessageStatus
    {
        return $this->m_eToState;
    }

    /**
     * set the message state for the destination user
     *
     * @param ePrivateMessageStatus $eToState message state for the destination user
     * @return void
     */
    public function setDestinationState(ePrivateMessageStatus $eToState): void
    {
        $this->m_eToState = $eToState;
    }

    /**
     * set the message state to read for an unread message of the recipient
     *
     * @return void
     */
    public function setMessageRead(): void
    {
        if ($this->m_eToState->isUnread()) {

            cDB::getInstance()->executeQuery('UPDATE pxm_priv_message SET p_tostate='.ePrivateMessageStatus::READ->value." WHERE p_id=$this->m_iId");

            // Update unread count in pxm_user
            $objUser = new cUser();
            if ($objUser->loadDataById($this->m_iToUserId)) {
                $objUser->decrementPrivMessageCount();
            }
        }
    }

    /**
     * get the message state for the author
     *
     * @return ePrivateMessageStatus message state for the author
     */
    public function getAuthorState(): ePrivateMessageStatus
    {
        return $this->m_eFromState;
    }

    /**
     * set the message state for the author
     *
     * @param ePrivateMessageStatus $eFromState message state for the author
     * @return void
     */
    public function setAuthorState(ePrivateMessageStatus $eFromState): void
    {
        $this->m_eFromState = $eFromState;
    }

    /**
     * get membervariables as array
     *
     * @param int $iTimeOffset time offset in seconds
     * @param string $sDateFormat php date format
     * @param int $iLastOnlineTimestamp last online timestamp for user
     * @param string $sSubjectQuotePrefix prefix for quoted subject
     * @param ?cParser $objParser message parser
     * @return array<string, mixed> member variables
     */
    public function getDataArray(int $iTimeOffset, string $sDateFormat, int $iLastOnlineTimestamp, string $sSubjectQuotePrefix = '', ?cParser $objParser = null): array
    {
        return array_merge(
            cMessage::getDataArray($iTimeOffset, $sDateFormat, $iLastOnlineTimestamp, $sSubjectQuotePrefix, $objParser),
            ['read' => ($this->m_eToState->isRead() ? '1' : '0')]
        );
    }
}
