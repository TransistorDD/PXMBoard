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
class cUserPermissions extends cUser{

	var	$m_bPost;					// post allowed ?
	var	$m_bEdit;					// edit allowed ?

	var	$m_bIsAdmin;				// is administrator ?
	var	$m_arrModBoards;			// is moderator for

	/**
	 * Constructor
	 *
	 * @return void
	 */
	public function __construct(){

		parent::__construct();

		$this->m_bPost = false;
		$this->m_bEdit = false;

		$this->m_bIsAdmin = false;
		$this->m_arrModBoards = null;
	}

	/**
	 * initalize the member variables with the resultset from the db
	 *
	 * @param object $objResultRow resultrow from db query
	 * @return boolean success / failure
	 */
	protected function _setDataFromDb($objResultRow){

		cUser::_setDataFromDb($objResultRow);

		$this->m_bPost = $objResultRow->u_post?true:false;
		$this->m_bEdit = $objResultRow->u_edit?true:false;
		$this->m_bIsAdmin = $objResultRow->u_admin?true:false;

		return true;
	}

	/**
	 * initalize an array with board ids where current user is moderator
	 *
	 * @return boolean success / failure
	 */
	private function _loadModBoards(){

		$this->m_arrModBoards = array();

		if($objResultSet = cDBFactory::getInstance()->executeQuery("SELECT mod_boardid FROM pxm_moderator WHERE mod_userid=".$this->m_iId)){
			while($objResultRow = $objResultSet->getNextResultRowObject()){
				$this->m_arrModBoards[] = intval($objResultRow->mod_boardid);
			}
			$objResultSet->freeResult();
		}
		else{
			return false;
		}
		return true;
	}

	/**
	 * get additional database attributes for this object (template method)
	 *
	 * @return string additional database attributes for this object
	 */
	protected function _getDbAttributes(){
	 	return cUser::_getDbAttributes().",u_post,u_edit,u_admin";
	 }

	/**
	 * refresh the member rights and status variables from database
	 *
	 * @return void
	 */
	public function refreshRights(){


		if($objResultSet = cDBFactory::getInstance()->executeQuery("SELECT u_status,u_post,u_edit,u_admin FROM pxm_user WHERE u_id=".$this->m_iId)){
			if($objResultRow = $objResultSet->getNextResultRowObject()){
				$this->m_eStatus = UserStatus::tryFrom($objResultRow->u_status) ?? UserStatus::NOT_ACTIVATED;
				$this->m_bPost = $objResultRow->u_post?true:false;
				$this->m_bEdit = $objResultRow->u_edit?true:false;
				$this->m_bIsAdmin = $objResultRow->u_admin?true:false;
			}
			$objResultSet->freeResult();
		}
	}

	/**
	 * allowed to post messages?
	 *
	 * @return boolean posting new messages allowed?
	 */
	public function isPostAllowed(){
		return $this->m_bPost;
	}

	/**
	 * set allowed to post messages?
	 *
	 * @param boolean $bPost posting new messages allowed?
	 * @return void
	 */
	public function setPostAllowed($bPost){
		$this->m_bPost = $bPost?true:false;
	}

	/**
	 * allowed to edit messages?
	 *
	 * @return boolean edit messages allowed?
	 */
	public function isEditAllowed(){
		return $this->m_bEdit;
	}

	/**
	 * set allowed to edit messages?
	 *
	 * @param boolean $bEdit edit messages allowed?
	 * @return void
	 */
	public function setEditAllowed($bEdit){
		$this->m_bEdit = $bEdit?true:false;
	}

	/**
	 * is an admin?
	 *
	 * @return boolean is admin?
	 */
	public function isAdmin(){
		return $this->m_bIsAdmin;
	}

	/**
	 * set admin flag
	 *
	 * @param boolean $bIsAdmin is admin?
	 * @return void
	 */
	public function setAdmin($bIsAdmin){
		$this->m_bIsAdmin = $bIsAdmin?true:false;
	}

	/**
	 * is an moderator for the given board?
	 *
	 * @param integer $iBoardId board id
	 * @return boolean is moderator for the given board?
	 */
	public function isModerator($iBoardId){
		if(!is_array($this->m_arrModBoards)){
			$this->_loadModBoards();
		}
		return in_array(intval($iBoardId),$this->m_arrModBoards);
	}

	/**
	 * get the board ids of where this user is moderator
	 *
	 * @return array board ids
	 */
	public function getModeratorBoardIds(){
		if(!is_array($this->m_arrModBoards)){
			$this->_loadModBoards();
		}
		return $this->m_arrModBoards;
	}

	/**
	 * update data in database
	 *
	 * @return boolean success / failure
	 */
	public function updateData(){


		if(!cDBFactory::getInstance()->executeQuery("UPDATE pxm_user SET u_status=".$this->m_eStatus->value.
															 ",u_post=".intval($this->m_bPost).
															 ",u_edit=".intval($this->m_bEdit).
															 ",u_admin=".intval($this->m_bIsAdmin).
															 " WHERE u_id=".$this->m_iId)){
			return false;
		}
		return true;
	}
}
?>