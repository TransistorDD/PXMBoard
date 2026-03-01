<?php
require_once(SRCDIR . '/Controller/cAction.php');
require_once(SRCDIR . '/Model/cProfileConfig.php');
require_once(SRCDIR . '/Model/cUserProfile.php');
require_once(SRCDIR . '/Parser/cPlainTextParser.php');
/**
 * shows the user profile form
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cActionUserProfileForm extends cAction{

	/**
	 * Validate permissions for this action
	 *
	 * @return bool true if all permissions granted
	 */
	public function validateBasePermissionsAndConditions(): bool {
		return $this->_requireAuthentication();
	}

	/**
	 * perform the action
	 *
	 * @return void
	 */
	public function performAction(): void{

		$objProfileConfig = new cProfileConfig();

		$objUserProfile = new cUserProfile($objProfileConfig->getSlotList());

		if($objUserProfile->loadDataById($this->getActiveUser()->getId())){

			$objPlainTextParser = new cPlainTextParser();
			$this->m_objTemplate = $this->_getTemplateObject("userprofileform");
			$this->m_objTemplate->addData($this->getContextDataArray(array("propicdir"=>$this->m_objConfig->getProfileImgDirectory())));
			$this->m_objTemplate->addData(array("user"=>$objUserProfile->getDataArray($this->m_objConfig->getTimeOffset()*3600,
																								$this->m_objConfig->getDateFormat(),
																								$objPlainTextParser)));
		}
		else $this->m_objTemplate = $this->_getErrorTemplateObject(eError::INVALID_USER_ID);// invalid user id
	}
}
?>