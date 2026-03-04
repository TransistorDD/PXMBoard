<?php

require_once(SRCDIR . '/Model/cUserProfile.php');
require_once(SRCDIR . '/Model/cBoard.php');
/**
 * admin user handling
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cUserAdmin extends cUserProfile
{
    protected bool $m_bPost;						// post allowed ?
    protected bool $m_bEdit;						// edit allowed ?
    protected bool $m_bIsAdmin;					// is administrator ?
    protected array $m_arrModeratedBoards;			// boards moderated by current user

    protected bool $m_bIsVisible;					// user visible? (online list)
    protected int $m_iSkinId;						// skin id
    protected string $m_sThreadListSortMode;			// sort mode for threadlist
    protected int $m_iTimeOffset;					// timeoffset
    protected bool $m_bEmbedExternal;				// externe Inhalte einbetten (Bilder, YouTube, Twitch)
    protected bool $m_bPrivateMessageNotification;	// send private message notification

    /**
     * Constructor
     *
     * @param array $arrAddFields additional profile fields
     * @return void
     */
    public function __construct(array $arrAddFields = [])
    {

        parent::__construct($arrAddFields);

        $this->m_bPost = false;
        $this->m_bEdit = false;

        $this->m_bIsAdmin = false;

        $this->m_arrModeratedBoards = [];

        $this->m_bIsVisible	= true;
        $this->m_iSkinId = 0;
        $this->m_sThreadListSortMode = '';
        $this->m_iTimeOffset = 0;
        $this->m_bEmbedExternal = false;
        $this->m_bPrivateMessageNotification = false;
    }

    /**
     * initalize the member variables with the resultset from the db
     *
     * @param object $objResultRow resultrow from db query
     * @return bool success / failure
     */
    protected function _setDataFromDb(object $objResultRow): bool
    {

        cUserProfile::_setDataFromDb($objResultRow);

        $this->m_bPost = $objResultRow->u_post ? true : false;
        $this->m_bEdit = $objResultRow->u_edit ? true : false;
        $this->m_bIsAdmin = $objResultRow->u_admin ? true : false;

        $this->m_bIsVisible	= $objResultRow->u_visible ? true : false;
        $this->m_iSkinId = intval($objResultRow->u_skinid);
        $this->m_sThreadListSortMode = $objResultRow->u_threadlistsort;
        $this->m_iTimeOffset = intval($objResultRow->u_timeoffset);
        $this->m_bEmbedExternal = $objResultRow->u_embed_external ? true : false;
        $this->m_bPrivateMessageNotification = $objResultRow->u_privatenotification ? true : false;

        return true;
    }

    /**
     * get additional database attributes for this object (template method)
     *
     * @return string additional database attributes for this object
     */
    protected function _getDbAttributes(): string
    {
        return cUserProfile::_getDbAttributes()
                .',u_post,u_edit,u_admin,u_visible,u_skinid,u_threadlistsort,'
                .'u_timeoffset,u_embed_external,u_privatenotification';
    }

    /**
     * update data in database
     *
     * @return bool success / failure
     */
    public function updateData(): bool
    {

        $bReturn = false;
        $sAddUpdateQuery = '';

        foreach ($this->m_arrAddData as $sFieldName => $mData) {
            if (is_integer($mData)) {
                $sAddUpdateQuery .= 'u_profile_'.$sFieldName.'='.$this->m_arrAddData[$sFieldName].',';
            } else {
                $sAddUpdateQuery .= 'u_profile_'.$sFieldName.'='.cDBFactory::getInstance()->quote($this->m_arrAddData[$sFieldName]).',';
            }
        }

        if (cDBFactory::getInstance()->executeQuery('UPDATE pxm_user SET '.
                                                                          'u_lastname='.cDBFactory::getInstance()->quote($this->m_sLastName).','.
                                                                          'u_city='.cDBFactory::getInstance()->quote($this->m_sCity).','.
                                                                          'u_publicmail='.cDBFactory::getInstance()->quote($this->m_sPublicMail).','.
                                                                          'u_privatemail='.cDBFactory::getInstance()->quote($this->m_sPrivateMail).','.
                                                                          'u_signature='.cDBFactory::getInstance()->quote($this->m_sSignature).','.
                                                                          $sAddUpdateQuery.
                                                                          'u_highlight='.intval($this->m_bHighlight).','.
                                                                          'u_status='.$this->m_eStatus->value.','.
                                                                          'u_post='.intval($this->m_bPost).','.
                                                                          'u_edit='.intval($this->m_bEdit).','.
                                                                          'u_admin='.intval($this->m_bIsAdmin).','.
                                                                          'u_visible='.intval($this->m_bIsVisible).','.
                                                                          'u_skinid='.intval($this->m_iSkinId).','.
                                                                          'u_threadlistsort='.cDBFactory::getInstance()->quote($this->m_sThreadListSortMode).','.
                                                                          'u_timeoffset='.intval($this->m_iTimeOffset).','.
                                                                          'u_embed_external='.intval($this->m_bEmbedExternal).','.
                                                                          'u_privatenotification='.intval($this->m_bPrivateMessageNotification).
                                                                 ' WHERE u_id='.intval($this->m_iId))) {
            $bReturn = true;
        }
        return $bReturn;
    }

    /**
     * load moderator data from database
     *
     * @return bool success / failure
     */
    public function loadModData(): bool
    {


        $this->m_arrModeratedBoards = [];

        if ($objResultSet = cDBFactory::getInstance()->executeQuery("SELECT b_id,b_name FROM pxm_moderator,pxm_board WHERE mod_boardid=b_id AND mod_userid=$this->m_iId")) {
            while ($objResultRow = $objResultSet->getNextResultRowObject()) {

                $objBoard = new cBoard();
                $objBoard->setId($objResultRow->b_id);
                $objBoard->setName($objResultRow->b_name);

                $this->m_arrModeratedBoards[] = $objBoard;
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


        if (cDBFactory::getInstance()->executeQuery("DELETE FROM pxm_moderator WHERE mod_userid=$this->m_iId")) {
            foreach ($this->m_arrModeratedBoards as $objBoard) {
                cDBFactory::getInstance()->executeQuery('INSERT INTO pxm_moderator (mod_boardid,mod_userid) VALUES ('.$objBoard->getId().",$this->m_iId)");
            }
        } else {
            return false;
        }

        return true;
    }

    /**
     * allowed to post messages?
     *
     * @return bool posting new messages allowed?
     */
    public function isPostAllowed(): bool
    {
        return $this->m_bPost;
    }

    /**
     * set allowed to post messages?
     *
     * @param bool $bPost posting new messages allowed?
     * @return void
     */
    public function setPostAllowed(bool $bPost): void
    {
        $this->m_bPost = $bPost ? true : false;
    }

    /**
     * allowed to edit messages?
     *
     * @return bool edit messages allowed?
     */
    public function isEditAllowed(): bool
    {
        return $this->m_bEdit;
    }

    /**
     * set allowed to edit messages?
     *
     * @param bool $bEdit edit messages allowed?
     * @return void
     */
    public function setEditAllowed(bool $bEdit): void
    {
        $this->m_bEdit = $bEdit ? true : false;
    }

    /**
     * is an admin?
     *
     * @return bool is admin?
     */
    public function isAdmin(): bool
    {
        return $this->m_bIsAdmin;
    }

    /**
     * set admin flag
     *
     * @param bool $bIsAdmin is admin?
     * @return void
     */
    public function setAdmin(bool $bIsAdmin): void
    {
        $this->m_bIsAdmin = $bIsAdmin ? true : false;
    }

    /**
     * is visible in online list?
     *
     * @return bool visible / invisible
     */
    public function isVisible(): bool
    {
        return $this->m_bIsVisible;
    }

    /**
     * set the visibility of the user in the onlinelist
     *
     * @param bool $bIsVisible visible / invisible
     * @return void
     */
    public function setIsVisible(bool $bIsVisible): void
    {
        $this->m_bIsVisible = $bIsVisible ? true : false;
    }

    /**
     * get skin id
     *
     * @return int skin id
     */
    public function getSkinId(): int
    {
        return $this->m_iSkinId;
    }

    /**
     * set skin id
     *
     * @param int $iSkinId skin id
     * @return void
     */
    public function setSkinId(int $iSkinId): void
    {
        $iSkinId = intval($iSkinId);
        if ($objResultSet = cDBFactory::getInstance()->executeQuery('SELECT s_id FROM pxm_skin WHERE s_id='.$iSkinId." AND s_fieldname='name'")) {
            if ($objResultSet->getNumRows() > 0) {
                $this->m_iSkinId = $iSkinId;
            }
        }
    }

    /**
     * get sort mode for threadlist
     *
     * @return string sort mode for threadlist
     */
    public function getThreadListSortMode(): string
    {
        return $this->m_sThreadListSortMode;
    }

    /**
     * set sort mode for threadlist
     *
     * @param string $sThreadListSortMode sort mode for threadlist
     * @return void
     */
    public function setThreadListSortMode(string $sThreadListSortMode): void
    {
        $this->m_sThreadListSortMode = $sThreadListSortMode;
    }

    /**
     * get time offset
     *
     * @return int time offset
     */
    public function getTimeOffset(): int
    {
        return $this->m_iTimeOffset;
    }

    /**
     * set time offset
     *
     * @param int $iTimeOffset time offset
     * @return void
     */
    public function setTimeOffset(int $iTimeOffset): void
    {
        $iTimeOffset = intval($iTimeOffset);
        if (($iTimeOffset < 13) && ($iTimeOffset > -13)) {
            $this->m_iTimeOffset = $iTimeOffset;
        }
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
     * send private message notification?
     *
     * @return bool send a notification?
     */
    public function sendPrivateMessageNotification(): bool
    {
        return $this->m_bPrivateMessageNotification;
    }

    /**
     * set send private message notification
     *
     * @param bool $bPrivateMessageNotification send a notification?
     * @return void
     */
    public function setSendPrivateMessageNotification(bool $bPrivateMessageNotification): void
    {
        $this->m_bPrivateMessageNotification = $bPrivateMessageNotification ? true : false;
    }

    /**
     * get moderated boards
     *
     * @return array moderated boards
     */
    public function getModeratedBoards(): array
    {
        return $this->m_arrModeratedBoards;
    }

    /**
     * set moderated boards
     *
     * @param array $arrModeratedBoards moderated boards
     * @return void
     */
    public function setModeratedBoardsById(array $arrModeratedBoards): void
    {

        $this->m_arrModeratedBoards = [];

        foreach ($arrModeratedBoards as $iBoardId) {
            $objBoard = new cBoard();
            if ($objBoard->loadDataById($iBoardId)) {
                $this->m_arrModeratedBoards[] = $objBoard;
            }
        }
    }
}
