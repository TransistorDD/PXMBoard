<?php
require_once(SRCDIR . '/Controller/cAction.php');
require_once(SRCDIR . '/Model/cUserProfile.php');
require_once(SRCDIR . '/Model/cProfileConfig.php');
require_once(SRCDIR . '/Enum/eSuccessMessage.php');
/**
 * saves a user profile
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cActionUserProfileSave extends cAction{

	/**
	 * Validate permissions for this action
	 *
	 * @return bool true if all permissions granted
	 */
	public function validateBasePermissionsAndConditions(): bool {
		return $this->_requireValidCsrfToken() && $this->_requireAuthentication();
	}

	/**
	 * perform the action
	 *
	 * @return void
	 */
	public function performAction(): void{

		$objProfileConfig = new cProfileConfig();
		$arrSlotList = $objProfileConfig->getSlotList();

		$objUserProfile = new cUserProfile($arrSlotList);

		if($objUserProfile->loadDataById($this->getActiveUser()->getId())){
			$objUserProfile->setCity($this->m_objInputHandler->getStringFormVar("city","city",true,true,"trim"));
			$objUserProfile->setPublicMail($this->m_objInputHandler->getStringFormVar("email","email",true,true,"trim"));
			$objUserProfile->setSignature($this->m_objInputHandler->getStringFormVar("signature","signature",true,true,"rtrim"));
			$objUserProfile->setFirstName($this->m_objInputHandler->getStringFormVar("fname","firstname",true,true,"trim"));
			$objUserProfile->setLastName($this->m_objInputHandler->getStringFormVar("lname","lastname",true,true,"trim"));

			foreach($arrSlotList as $sKey=>$arrVal){
				if($arrVal[0]=='i'){
					$objUserProfile->setAdditionalDataElement($sKey,$this->m_objInputHandler->getIntFormVar($sKey,true,true,false));
				}
				else{
					$sValue = $this->m_objInputHandler->getStringFormVar($sKey,"",true,true,"trim");
					if(mb_strlen($sValue) > $arrVal[1]){
						$sValue = mb_substr($sValue, 0, $arrVal[1]);
					}
					$objUserProfile->setAdditionalDataElement($sKey,$sValue);
				}
			}

			$objUserProfile->setLastUpdateTimestamp($this->m_objConfig->getAccessTimestamp());

			$bSuccess = false;

			if($objUserProfile->updateData()){
				if($this->m_objConfig->getMaxProfileImgSize()>0){		// file upload
					$objFileUpload = $this->m_objInputHandler->getFileFormObject("pic");
					if($objFileUpload->isUploadedFile()){
						$arrAllowedImgTypes = $this->m_objConfig->getProfileImgTypes();

						if(($objFileUpload->getFileSize()<=$this->m_objConfig->getMaxProfileImgSize()) &&
							in_array($objFileUpload->getFileType(),array_keys($arrAllowedImgTypes))){

							$arrImgSize = getimagesize($objFileUpload->getFileTmpName());
							if(($arrImgSize[0]>0) && ($arrImgSize[0]<=$this->m_objConfig->getMaxProfileImgWidth()) &&
								($arrImgSize[1]>0) && ($arrImgSize[1]<=$this->m_objConfig->getMaxProfileImgHeight())){

								$objUserProfile->addImage($this->m_objConfig->getProfileImgFsDirectory(),
															$this->m_objConfig->getProfileImgDirectorySplit(),
															$objFileUpload->getFileTmpName(),
															$arrAllowedImgTypes[$objFileUpload->getFileType()]);
								$bSuccess = true;
							}
							else $this->m_objTemplate = $this->_getErrorTemplateObject(eError::IMAGE_UPLOAD_ERROR);// file upload error
						}
						else $this->m_objTemplate = $this->_getErrorTemplateObject(eError::IMAGE_UPLOAD_ERROR);	// file upload error
					}
					else{
						if($this->m_objInputHandler->getIntFormVar("delpic",true,true)==1){
							$objUserProfile->deleteImage($this->m_objConfig->getProfileImgFsDirectory());
						}
						$bSuccess = true;
					}
				}
				else $bSuccess = true;

				if($bSuccess){
					$this->m_objTemplate = $this->_getTemplateObject("confirm");
					$this->m_objTemplate->addData($this->getContextDataArray());
					$this->m_objTemplate->addData(array("message" => eSuccessMessage::USER_PROFILE_SAVED->value));
				}
			}
			else $this->m_objTemplate = $this->_getErrorTemplateObject(eError::COULD_NOT_INSERT_DATA);	// could not insert data
		}
		else $this->m_objTemplate = $this->_getErrorTemplateObject(eError::INVALID_USER_ID);// user id invalid
	}
}
?>