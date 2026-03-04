<?php

require_once(SRCDIR . '/Model/cMessage.php');
require_once(SRCDIR . '/Model/cUser.php');
require_once(SRCDIR . '/Enum/ePrivateMessage.php');
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
    protected int $m_iToUserId;					// destination user id
    protected PrivateMessageStatus $m_eToState;		// state for the recipient
    protected PrivateMessageStatus $m_eFromState;	// state for the sender

    /**
     * Constructor
     *
     * @return void
     */
    public function __construct()
    {

        parent::__construct();

        $this->m_iToUserId = 0;
        $this->m_eToState = PrivateMessageStatus::UNREAD;

        $this->m_eFromState = PrivateMessageStatus::READ;
    }

    /**
     * get data from database by message id
     *
     * @param int $iMessageId message id
     * @return bool success / failure
     */
    public function loadDataById(int $iMessageId): bool
    {

        $bReturn = false;
        $iMessageId = intval($iMessageId);

        if ($iMessageId > 0) {


            if ($objResultSet = cDBFactory::getInstance()->executeQuery('SELECT p_id,'.
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
                                                            '(p_touserid='.$this->m_iToUserId.' AND p_tostate!='.PrivateMessageStatus::DELETED->value.')'.
                                                            ' OR '.
                                                            '(p_fromuserid='.$this->m_objAuthor->getId().' AND p_fromstate!='.PrivateMessageStatus::DELETED->value.')'.
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

        $this->m_iId = intval($objResultRow->p_id);
        $this->m_sSubject = $objResultRow->p_subject;
        $this->m_sBody = $objResultRow->p_body;
        $this->m_iMessageTimestamp = intval($objResultRow->p_tstmp);
        $this->m_sIp = $objResultRow->p_ip;

        // recipient data
        $this->m_eToState = PrivateMessageStatus::from(intval($objResultRow->p_tostate));
        $this->m_iToUserId = intval($objResultRow->p_touserid);

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

        $this->m_eFromState = PrivateMessageStatus::from(intval($objResultRow->p_fromstate));

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
     * @return int error id
     */
    public function insertData(): int
    {

        $iErrorId = 8;												// could not insert data

        if ($this->m_iToUserId > 0 && $this->m_objAuthor->getId() > 0) {
            if (!empty($this->m_sSubject)) {
                if ($objResultSet = cDBFactory::getInstance()->executeQuery('INSERT INTO pxm_priv_message (p_touserid,p_fromuserid,p_subject,p_body,p_tstmp,p_ip)'.
                                                                   " values ($this->m_iToUserId,".
                                                                             $this->m_objAuthor->getId().','.
                                                                             cDBFactory::getInstance()->quote($this->m_sSubject).','.
                                                                             cDBFactory::getInstance()->quote($this->m_sBody).','.
                                                                             $this->m_iMessageTimestamp.','.
                                                                             cDBFactory::getInstance()->quote($this->m_sIp).')')) {
                    if ($objResultSet->getAffectedRows() > 0) {
                        $iErrorId = 0;
                        $this->m_iId = cDBFactory::getInstance()->getInsertID('pxm_priv_message', 'p_id');

                        // Update unread count in pxm_user
                        $objUser = new cUser();
                        if ($objUser->loadDataById($this->m_iToUserId)) {
                            $objUser->incrementPrivMessageCount();
                        }
                    }
                }
            } else {
                $iErrorId = 7;
            }										// missing subject
        } else {
            $iErrorId = 20;
        }										// invalid user id

        return $iErrorId;
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
        if ($objResultSet = cDBFactory::getInstance()->executeQuery('UPDATE pxm_priv_message SET p_tostate='.PrivateMessageStatus::DELETED->value.
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
        if (!$bReturn && ($objResultSet = cDBFactory::getInstance()->executeQuery('UPDATE pxm_priv_message SET p_fromstate='.PrivateMessageStatus::DELETED->value.
                                                                ' WHERE p_fromuserid='.$this->m_objAuthor->getId()." AND p_id=$this->m_iId"))) {
            if ($objResultSet->getAffectedRows() > 0) {
                $bReturn = true;
            }
        }

        // remove all deleted messages from db
        cDBFactory::getInstance()->executeQuery('DELETE FROM pxm_priv_message WHERE p_tostate='.PrivateMessageStatus::DELETED->value.' AND p_fromstate='.PrivateMessageStatus::DELETED->value);

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
        $this->m_iToUserId = intval($iToUserId);
    }

    /**
     * get the message state for the destination user
     *
     * @return PrivateMessageStatus message state for the destination user
     */
    public function getDestinationState(): PrivateMessageStatus
    {
        return $this->m_eToState;
    }

    /**
     * set the message state for the destination user
     *
     * @param PrivateMessageStatus $eToState message state for the destination user
     * @return void
     */
    public function setDestinationState(PrivateMessageStatus $eToState): void
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


            cDBFactory::getInstance()->executeQuery('UPDATE pxm_priv_message SET p_tostate='.PrivateMessageStatus::READ->value." WHERE p_id=$this->m_iId");

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
     * @return PrivateMessageStatus message state for the author
     */
    public function getAuthorState(): PrivateMessageStatus
    {
        return $this->m_eFromState;
    }

    /**
     * set the message state for the author
     *
     * @param PrivateMessageStatus $eFromState message state for the author
     * @return void
     */
    public function setAuthorState(PrivateMessageStatus $eFromState): void
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
     * @return array member variables
     */
    public function getDataArray(int $iTimeOffset, string $sDateFormat, int $iLastOnlineTimestamp, string $sSubjectQuotePrefix = '', ?cParser $objParser = null): array
    {
        return array_merge(
            cMessage::getDataArray($iTimeOffset, $sDateFormat, $iLastOnlineTimestamp, $sSubjectQuotePrefix, $objParser),
            ['read' => ($this->m_eToState->isRead() ? '1' : '0')]
        );
    }
}
