<?php

require_once(SRCDIR . '/Model/cUser.php');
/**
 * user permission handling
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cUserPermissions extends cUser
{
    public bool $m_bPost = false;					// post allowed ?
    public bool $m_bEdit = false ;					// edit allowed ?

    public bool $m_bIsAdmin = false;				// is administrator ?
    /** @var array<int> */
    public ?array $m_arrModBoards = null;			// is moderator for

    /**
     * initalize the member variables with the resultset from the db
     *
     * @param object $objResultRow resultrow from db query
     * @return bool success / failure
     */
    protected function _setDataFromDb(object $objResultRow): bool
    {
        cUser::_setDataFromDb($objResultRow);

        $this->m_bPost = $objResultRow->u_post ? true : false;
        $this->m_bEdit = $objResultRow->u_edit ? true : false;
        $this->m_bIsAdmin = $objResultRow->u_admin ? true : false;

        return true;
    }

    /**
     * initalize an array with board ids where current user is moderator
     *
     * @return bool success / failure
     */
    private function _loadModBoards(): bool
    {
        $this->m_arrModBoards = [];

        if ($objResultSet = cDBFactory::getInstance()->executeQuery('SELECT mod_boardid FROM pxm_moderator WHERE mod_userid='.$this->m_iId)) {
            while ($objResultRow = $objResultSet->getNextResultRowObject()) {
                $this->m_arrModBoards[] = (int) $objResultRow->mod_boardid;
            }
            $objResultSet->freeResult();
        } else {
            return false;
        }
        return true;
    }

    /**
     * get additional database attributes for this object (template method)
     *
     * @return string additional database attributes for this object
     */
    protected function _getDbAttributes(): string
    {
        return cUser::_getDbAttributes().',u_post,u_edit,u_admin';
    }

    /**
     * refresh the member rights and status variables from database
     *
     * @return void
     */
    public function refreshRights(): void
    {
        if ($objResultSet = cDBFactory::getInstance()->executeQuery('SELECT u_status,u_post,u_edit,u_admin FROM pxm_user WHERE u_id='.$this->m_iId)) {
            if ($objResultRow = $objResultSet->getNextResultRowObject()) {
                $this->m_eStatus = eUserStatus::tryFrom($objResultRow->u_status) ?? eUserStatus::NOT_ACTIVATED;
                $this->m_bPost = $objResultRow->u_post ? true : false;
                $this->m_bEdit = $objResultRow->u_edit ? true : false;
                $this->m_bIsAdmin = $objResultRow->u_admin ? true : false;
            }
            $objResultSet->freeResult();
        }
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
        $this->m_bPost = $bPost;
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
        $this->m_bEdit = $bEdit;
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
        $this->m_bIsAdmin = $bIsAdmin;
    }

    /**
     * is an moderator for the given board?
     *
     * @param int $iBoardId board id
     * @return bool is moderator for the given board?
     */
    public function isModerator(int $iBoardId): bool
    {
        if (!is_array($this->m_arrModBoards)) {
            $this->_loadModBoards();
        }
        return in_array($iBoardId, $this->m_arrModBoards);
    }

    /**
     * get the board ids of where this user is moderator
     *
     * @return array<int> board ids
     */
    public function getModeratorBoardIds(): array
    {
        if (!is_array($this->m_arrModBoards)) {
            $this->_loadModBoards();
        }
        return $this->m_arrModBoards;
    }

    /**
     * update data in database
     *
     * @return bool success / failure
     */
    public function updateData(): bool
    {
        if (!cDBFactory::getInstance()->executeQuery('UPDATE pxm_user SET u_status='.$this->m_eStatus->value.
                                                             ',u_post='.intval($this->m_bPost).
                                                             ',u_edit='.intval($this->m_bEdit).
                                                             ',u_admin='.intval($this->m_bIsAdmin).
                                                             ' WHERE u_id='.$this->m_iId)) {
            return false;
        }
        return true;
    }
}
