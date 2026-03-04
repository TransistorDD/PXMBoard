<?php

require_once(SRCDIR . '/Model/cUser.php');
/**
 * messageheader handling
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cMessageHeader
{
    protected int $m_iId = 0;					// message id
    protected mixed $m_objAuthor;			    // author (user)
    protected string $m_sSubject = '';			// message subject
    protected int $m_iMessageTimestamp = 0;		// date of the message

    /**
     * Constructor
     *
     * @return void
     */
    public function __construct()
    {
        $this->m_objAuthor = new cUser();
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
        if ($iMessageId > 0) {
            if ($objResultSet = cDBFactory::getInstance()->executeQuery('SELECT m_id,'.
                                                             'm_subject,'.
                                                             'm_tstmp,'.
                                                             'm_userid,'.
                                                             'm_username,'.
                                                             'm_usermail,'.
                                                             'm_userhighlight'.
                                                             $this->_getDbAttributes().
                                                             ' FROM '.
                                                             $this->_getDbTables().
                                                             ' WHERE m_id='.$iMessageId.
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
        $this->m_iId = intval($objResultRow->m_id);
        $this->m_sSubject = $objResultRow->m_subject;
        $this->m_iMessageTimestamp = intval($objResultRow->m_tstmp);

        $this->m_objAuthor->setId($objResultRow->m_userid);
        $this->m_objAuthor->setUserName($objResultRow->m_username);
        $this->m_objAuthor->setPublicMail($objResultRow->m_usermail);
        $this->m_objAuthor->setHighlightUser($objResultRow->m_userhighlight);

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
        return 'pxm_message';
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
     * get subject
     *
     * @param string $sSubjectQuotePrefix prefix for quoted subject
     * @return string subject
     */
    public function getSubject(string $sSubjectQuotePrefix = ''): string
    {
        if (!empty($sSubjectQuotePrefix) && (strncasecmp($this->m_sSubject, $sSubjectQuotePrefix, strlen($sSubjectQuotePrefix)) != 0)) {
            return $sSubjectQuotePrefix.$this->m_sSubject;
        }
        return $this->m_sSubject;
    }

    /**
     * set subject
     *
     * @param string $sSubject subject
     * @return void
     */
    public function setSubject(string $sSubject): void
    {
        $this->m_sSubject = $sSubject;
    }

    /**
     * get message timestamp
     *
     * @return int message timestamp
     */
    public function getMessageTimestamp(): int
    {
        return $this->m_iMessageTimestamp;
    }

    /**
     * set message timestamp
     *
     * @param int $iMessageTimestamp message timestamp
     * @return void
     */
    public function setMessageTimestamp(int $iMessageTimestamp): void
    {
        $this->m_iMessageTimestamp = intval($iMessageTimestamp);
    }

    /**
     * get author (user)
     *
     * @return object author (user)
     */
    public function getAuthor(): object
    {
        return $this->m_objAuthor;
    }

    /**
     * set author (user)
     *
     * @param object $objAuthor author (user)
     * @return void
     */
    public function setAuthor(object $objAuthor): void
    {
        if (strcasecmp(get_class($objAuthor), 'cuser') == 0 || is_subclass_of($objAuthor, 'cUser')) {
            $this->m_objAuthor = $objAuthor;
        }
    }

    /**
     * get author id
     *
     * @return int author id
     */
    public function getAuthorId(): int
    {
        return $this->m_objAuthor->getId();
    }

    /**
     * set author id
     *
     * @param int $iAuthorId author id
     * @return void
     */
    public function setAuthorId(int $iAuthorId): void
    {
        $this->m_objAuthor->setId($iAuthorId);
    }

    /**
     * set author username
     *
     * @param string $sAuthorUserName author username
     * @return void
     */
    public function setAuthorUserName(string $sAuthorUserName): void
    {
        $this->m_objAuthor->setUserName($sAuthorUserName);
    }

    /**
     * set author public mail
     *
     * @param string $sAuthorPublicMail author public mail
     * @return void
     */
    public function setAuthorPublicMail(string $sAuthorPublicMail): void
    {
        $this->m_objAuthor->setPublicMail($sAuthorPublicMail);
    }

    /**
     * set author highlight user
     *
     * @param bool $bAuthorHighlightUser author highlight user
     * @return void
     */
    public function setAuthorHighlightUser(bool $bAuthorHighlightUser): void
    {
        $this->m_objAuthor->setHighlightUser($bAuthorHighlightUser);
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
        // TODO: Vererbung mit unterschiedlicher Methodensignatur optimieren
        return ['id'		=>	$this->m_iId,
                'subject'	=>	$this->getSubject($sSubjectQuotePrefix),
                'date'		=>	(($this->m_iMessageTimestamp > 0) ? date($sDateFormat, ($this->m_iMessageTimestamp + $iTimeOffset)) : 0),
                'new'		=>	(($iLastOnlineTimestamp > $this->m_iMessageTimestamp) ? 0 : 1),
                'user'		=>	$this->m_objAuthor->getDataArray($iTimeOffset, $sDateFormat, $objParser)];
    }
}
