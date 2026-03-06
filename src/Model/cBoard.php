<?php

namespace PXMBoard\Model;

use PXMBoard\Database\cDB;
use PXMBoard\Enum\eBoardStatus;

/**
 * board handling
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cBoard
{
    protected int $m_iId = 0;						    // board id
    protected string $m_sName = '';					    // board name
    protected string $m_sDescription = '';			    // board description
    protected int $m_iPosition = 0;					    // position in boardlist
    protected eBoardStatus $m_eStatus = eBoardStatus::PUBLIC;			// board status (PUBLIC, MEMBERS_ONLY, READONLY_PUBLIC, READONLY_MEMBERS, CLOSED)
    protected int $m_iLastMessageTimestamp = 0;		    // timestamp of last message
    protected int $m_iThreadListTimeSpan = 365;		    // timespan for threadlist in days
    protected string $m_sThreadListSortMode = 'last';	// sortmode for threadlist
    protected bool $m_bEmbedExternal = false;			// externe Inhalte einbetten (Bilder, YouTube, Twitch)
    protected bool $m_bDoTextReplacements = false;		// do textreplacements
    protected int $m_iThreadsPerPage = 50;			    // threads per page

    /** @var array<cUser> */
    protected array $m_arrModerators = [];			    // array of moderatores (id and name)

    /**
     * get data from database by board id
     *
     * @param int $iBoardId board id
     * @return bool success / failure
     */
    public function loadDataById(int $iBoardId): bool
    {
        $bReturn = false;
        if ($iBoardId > 0) {
            if ($objResultSet = cDB::getInstance()->executeQuery('SELECT b_id,'.
                                                                      'b_name,'.
                                                                      'b_description,'.
                                                                      'b_position,'.
                                                                      'b_status,'.
                                                                      'b_lastmsgtstmp,'.
                                                                      'b_timespan,'.
                                                                      'b_threadlistsort,'.
                                                                      'b_embed_external,'.
                                                                      'b_replacetext '.
                                                                "FROM pxm_board WHERE b_id=$iBoardId")) {
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
    private function _setDataFromDb(object $objResultRow): bool
    {
        $this->m_iId = (int) $objResultRow->b_id;
        $this->m_sName = $objResultRow->b_name;
        $this->m_sDescription = $objResultRow->b_description;
        $this->m_iPosition = (int) $objResultRow->b_position;
        $this->m_eStatus = eBoardStatus::from($objResultRow->b_status);
        $this->m_iLastMessageTimestamp = (int) $objResultRow->b_lastmsgtstmp;
        $this->m_iThreadListTimeSpan = (int) $objResultRow->b_timespan;
        $this->m_sThreadListSortMode = $objResultRow->b_threadlistsort;
        $this->m_bEmbedExternal = (bool) $objResultRow->b_embed_external;
        $this->m_bDoTextReplacements = (bool) $objResultRow->b_replacetext;

        return true;
    }

    /**
     * load moderator data from database
     *
     * @return bool success / failure
     */
    public function loadModData(): bool
    {
        $this->m_arrModerators = [];

        if ($objResultSet = cDB::getInstance()->executeQuery("SELECT u_id,u_username,u_publicmail,u_highlight FROM pxm_moderator,pxm_user WHERE mod_userid=u_id AND mod_boardid=$this->m_iId")) {
            while ($objResultRow = $objResultSet->getNextResultRowObject()) {

                $objUser = new cUser();
                $objUser->setId($objResultRow->u_id);
                $objUser->setUserName($objResultRow->u_username);
                $objUser->setPublicMail($objResultRow->u_publicmail);
                $objUser->setHighlightUser($objResultRow->u_highlight);

                $this->m_arrModerators[] = $objUser;
            }
            $objResultSet->freeResult();
        } else {
            return false;
        }
        return true;
    }

    /**
     * save moderator data to database
     *
     * @return bool success / failure
     */
    public function updateModData(): bool
    {
        if (cDB::getInstance()->executeQuery("DELETE FROM pxm_moderator WHERE mod_boardid=$this->m_iId")) {
            foreach ($this->m_arrModerators as $objUser) {
                cDB::getInstance()->executeQuery('INSERT INTO pxm_moderator (mod_userid,mod_boardid) VALUES (' . $objUser->getId() . ",$this->m_iId)");
            }
        } else {
            return false;
        }
        return true;
    }

    /**
     * insert new data into database
     *
     * @return bool success / failure
     */
    public function insertData(): bool
    {
        if (cDB::getInstance()->executeQuery('INSERT INTO pxm_board (b_name,b_description,b_status,b_timespan,b_threadlistsort,b_embed_external,b_replacetext) '
                                         .'VALUES ('.cDB::getInstance()->quote($this->m_sName).','.cDB::getInstance()->quote($this->m_sDescription).','.intval($this->m_eStatus->value).",$this->m_iThreadListTimeSpan,"
                                                 .cDB::getInstance()->quote($this->m_sThreadListSortMode).','.intval($this->m_bEmbedExternal).','.intval($this->m_bDoTextReplacements).')')) {
            $this->m_iId = cDB::getInstance()->getInsertId('pxm_board', 'b_id');
            cDB::getInstance()->executeQuery('UPDATE pxm_board SET b_position=b_id WHERE b_id='.$this->m_iId);
        } else {
            return false;
        }
        return true;
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
            if (cDB::getInstance()->executeQuery('UPDATE pxm_board SET b_name='.cDB::getInstance()->quote($this->m_sName).','
                                                        .'b_description='.cDB::getInstance()->quote($this->m_sDescription).','
                                                        ."b_position=$this->m_iPosition,"
                                                        .'b_status='.intval($this->m_eStatus->value).','
                                                        ."b_timespan=$this->m_iThreadListTimeSpan,"
                                                        .'b_threadlistsort='.cDB::getInstance()->quote($this->m_sThreadListSortMode).','
                                                        .'b_embed_external='.intval($this->m_bEmbedExternal).','
                                                        .'b_replacetext='.intval($this->m_bDoTextReplacements)." WHERE b_id=$this->m_iId")) {
                $bReturn = true;
            }
        }
        return $bReturn;
    }

    /**
     * delete data from database
     *
     * @return bool success / failure
     */
    public function deleteData(): bool
    {
        if ($this->m_iId > 0) {
            if ($objResultSet = cDB::getInstance()->executeQuery("SELECT t_id FROM pxm_thread WHERE t_boardid=$this->m_iId")) {
                while ($objResultRow = $objResultSet->getNextResultRowObject()) {
                    cDB::getInstance()->executeQuery("DELETE FROM pxm_message WHERE m_threadid=$objResultRow->t_id");
                }
                cDB::getInstance()->executeQuery("DELETE FROM pxm_thread WHERE t_boardid=$this->m_iId");
                cDB::getInstance()->executeQuery("DELETE FROM pxm_moderator WHERE mod_boardid=$this->m_iId");
                cDB::getInstance()->executeQuery("DELETE FROM pxm_board WHERE b_id=$this->m_iId");
            }
        } else {
            return false;
        }
        return true;
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
     * get name
     *
     * @return string name
     */
    public function getName(): string
    {
        return $this->m_sName;
    }

    /**
     * set name
     *
     * @param string $sName name
     * @return void
     */
    public function setName(string $sName): void
    {
        $this->m_sName = $sName;
    }

    /**
     * get description
     *
     * @return string description
     */
    public function getDescription(): string
    {
        return $this->m_sDescription;
    }

    /**
     * set description
     *
     * @param string $sDescription description
     * @return void
     */
    public function setDescription(string $sDescription): void
    {
        $this->m_sDescription = $sDescription;
    }

    /**
     * get position
     *
     * @return int position
     */
    public function getPosition(): int
    {
        return $this->m_iPosition;
    }

    /**
     * set position
     *
     * @param int $iPosition position
     * @return void
     */
    public function setPosition(int $iPosition): void
    {
        $this->m_iPosition = $iPosition;
    }

    /**
     * update position
     *
     * @param int $iPosition position
     * @return void
     */
    public function updatePosition(int $iPosition): void
    {
        if ($iPosition > 0 && $this->m_iPosition != $iPosition) {
            if ($this->m_iPosition > $iPosition) {
                cDB::getInstance()->executeQuery("UPDATE pxm_board SET b_position = b_position+1 WHERE b_position >= $iPosition AND b_position < $this->m_iPosition");
            } else {
                cDB::getInstance()->executeQuery("UPDATE pxm_board SET b_position = b_position-1 WHERE b_position <= $iPosition AND b_position > $this->m_iPosition");
            }
            $this->m_iPosition = $iPosition;
            cDB::getInstance()->executeQuery("UPDATE pxm_board SET b_position = $this->m_iPosition WHERE b_id = $this->m_iId");
        }
    }

    /**
     * Get board status
     *
     * @return eBoardStatus current status
     */
    public function getStatus(): eBoardStatus
    {
        return $this->m_eStatus;
    }

    /**
     * Set board status
     *
     * @param eBoardStatus $eStatus new status
     * @return void
     */
    public function setStatus(eBoardStatus $eStatus): void
    {
        $this->m_eStatus = $eStatus;
    }

    /**
     * Update board status in database
     *
     * @param eBoardStatus $eStatus new status
     * @return bool success / failure
     */
    public function updateStatus(eBoardStatus $eStatus): bool
    {
        if (!cDB::getInstance()->executeQuery('UPDATE pxm_board SET b_status='.intval($eStatus->value)." WHERE b_id=$this->m_iId")) {
            return false;
        }
        $this->m_eStatus = $eStatus;
        return true;
    }

    /**
     * Check if board is readable for public (non-authenticated users)
     *
     * @return bool true if public can read
     */
    public function isPublicReadable(): bool
    {
        return $this->m_eStatus->isPublicReadable();
    }

    /**
     * Check if board is writable by regular users
     *
     * @return bool true if regular users can write
     */
    public function isWritable(): bool
    {
        return $this->m_eStatus->isWritable();
    }

    /**
     * Check if board requires authentication
     *
     * @return bool true if authentication required
     */
    public function requiresAuthentication(): bool
    {
        return $this->m_eStatus->requiresAuthentication();
    }

    /**
     * Check if board is closed (only mods/admins can access)
     *
     * @return bool true if closed
     */
    public function isClosed(): bool
    {
        return $this->m_eStatus->isClosed();
    }

    /**
     * get last message timestamp
     *
     * @return int last message timestamp
     */
    public function getLastMessageTimestamp(): int
    {
        return $this->m_iLastMessageTimestamp;
    }

    /**
     * set last message timestamp
     *
     * @param int $iLastMessageTimestamp last message timestamp
     * @return void
     */
    public function setLastMessageTimestamp(int $iLastMessageTimestamp): void
    {
        $this->m_iLastMessageTimestamp = $iLastMessageTimestamp;
    }

    /**
     * get threads per page
     *
     * @return int threads per page
     */
    public function getThreadsPerPage(): int
    {
        return $this->m_iThreadsPerPage;
    }

    /**
     * set threads per page
     *
     * @param int $iThreadsPerPage threads per page
     * @return void
     */
    public function setThreadsPerPage(int $iThreadsPerPage): void
    {
        $this->m_iThreadsPerPage = $iThreadsPerPage;
    }

    /**
     * get threadlist timespan
     *
     * @return int threadlist timespan
     */
    public function getThreadListTimeSpan(): int
    {
        return $this->m_iThreadListTimeSpan;
    }

    /**
     * set threadlist timespan
     *
     * @param int $iThreadListTimeSpan threadlist timespan
     * @return void
     */
    public function setThreadListTimeSpan(int $iThreadListTimeSpan): void
    {
        $this->m_iThreadListTimeSpan = $iThreadListTimeSpan;
    }

    /**
     * get threadlist sort mode
     *
     * @return string threadlist sort mode
     */
    public function getThreadListSortMode(): string
    {
        return $this->m_sThreadListSortMode;
    }

    /**
     * set threadlist sort mode
     *
     * @param string $sThreadListSortMode threadlist sort mode
     * @return void
     */
    public function setThreadListSortMode(string $sThreadListSortMode): void
    {
        $this->m_sThreadListSortMode = $sThreadListSortMode;
    }

    /**
     * Externe Inhalte einbetten? (Bilder, YouTube, Twitch)
     *
     * @return bool externe Inhalte einbetten?
     */
    public function embedExternal(): bool
    {
        return $this->m_bEmbedExternal;
    }

    /**
     * Externe Inhalte einbetten setzen
     *
     * @param bool $bEmbedExternal externe Inhalte einbetten?
     * @return void
     */
    public function setEmbedExternal(bool $bEmbedExternal): void
    {
        $this->m_bEmbedExternal = $bEmbedExternal ? true : false;
    }

    /**
     * do textreplacements?
     *
     * @return bool do textreplacements?
     */
    public function doTextReplacements(): bool
    {
        return $this->m_bDoTextReplacements;
    }

    /**
     * set do textreplacements
     *
     * @param bool $bDoTextReplacements do textreplacements?
     * @return void
     */
    public function setDoTextReplacements(bool $bDoTextReplacements): void
    {
        $this->m_bDoTextReplacements = $bDoTextReplacements ? true : false;
    }

    /**
     * get moderators
     *
     * @return array<mixed> moderators
     */
    public function getModerators(): array
    {
        return $this->m_arrModerators;
    }

    /**
     * set moderators
     *
     * @param array<string> $arrModeratorUserNames usernames of moderators
     * @return void
     */
    public function setModeratorsByUserName(array $arrModeratorUserNames): void
    {
        $this->m_arrModerators = [];

        foreach ($arrModeratorUserNames as $sUserName) {
            $sUserName = trim($sUserName);
            if (!empty($sUserName)) {
                $objUser = new cUser();
                if ($objUser->loadDataByUserName($sUserName)) {
                    $this->m_arrModerators[] = $objUser;
                }
            }
        }
    }

    /**
     * get membervariables as array
     *
     * @param int $iTimeOffset time offset in seconds
     * @param string $sDateFormat php date format
     * @param int $iLastOnlineTimestamp last online timestamp for user
     * @param object $objParser message parser (for signature)
     * @return array<string, mixed> member variables
     */
    public function getDataArray(int $iTimeOffset, string $sDateFormat, int $iLastOnlineTimestamp, object $objParser): array
    {
        $arrModerators = [];
        reset($this->m_arrModerators);
        foreach ($this->m_arrModerators as $objUser) {
            $arrModerators[] = $objUser->getDataArray($iTimeOffset, $sDateFormat, $objParser);
        }

        return ['id'		=>	$this->m_iId,
                'name'		=>	$this->m_sName,
                'desc'		=>	$this->m_sDescription,
                'position'	=>	$this->m_iPosition,
                'lastmsg'	=>	(($this->m_iLastMessageTimestamp > 0) ? date($sDateFormat, ($this->m_iLastMessageTimestamp + $iTimeOffset)) : 0),
                'new'		=>	(($iLastOnlineTimestamp > $this->m_iLastMessageTimestamp) ? 0 : 1),
                'status'	=>	$this->m_eStatus->value,
                'status_label'	=>	$this->m_eStatus->getLabel(),
                'moderator' =>	$arrModerators];
    }
}
