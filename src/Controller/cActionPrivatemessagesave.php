<?php
require_once(SRCDIR . '/Controller/cAction.php');
require_once(SRCDIR . '/Model/cPrivateMessage.php');
require_once(SRCDIR . '/Model/cBadwordList.php');
require_once(SRCDIR . '/Model/cUserConfig.php');
require_once(SRCDIR . '/Model/cNotification.php');
require_once(SRCDIR . '/Enum/eNotification.php');
/**
 * saves a private message
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cActionPrivatemessagesave extends cAction{

	/**
	 * Validate permissions for this action
	 *
	 * @return bool true if all permissions granted
	 */
	public function validateBasePermissionsAndConditions(): bool {
		return $this->_requireValidCsrfToken() && $this->_requirePostPermission();
	}

	/**
	 * perform the action
	 *
	 * @return void
	 */
	public function performAction(): void{

		$objActiveUser = $this->getActiveUser();
		$iLastOnline = $objActiveUser->getLastOnlineTimestamp();

		$iDestinationId = $this->m_objInputHandler->getIntFormVar("toid",true,true,true);
		if($iDestinationId>0){

			$objDestinationUser = new cUserConfig();
			if($objDestinationUser->loadDataById($iDestinationId)){

				$sSubject = $this->m_objInputHandler->getStringFormVar("subject","subject",true,false,"trim");
				$sBody = $this->m_objInputHandler->getStringFormVar("body","body",true,false,"rtrim");

				if(empty($sSubject)){
					$this->m_objTemplate = $this->_getTemplateObject("privatemessageform");
					$this->m_objTemplate->addData($this->getContextDataArray(array("type"=>"outbox")));
					$this->m_objTemplate->addData(array("error" => array("text" => eError::SUBJECT_MISSING->value)));
					$this->m_objTemplate->addData(array("touser"	=>array("id"		=>$objDestinationUser->getId(),
																					"username"	=>$objDestinationUser->getUserName())));
					$this->m_objTemplate->addData(array("msg"		=>array("subject"	=>$sSubject,
																					"_body"		=>htmlspecialchars($sBody))));
				}
				else{
					// replace badwords
					$objBadwordList = new cBadwordList();
					$arrBadwords = $objBadwordList->getList();
					$sSubject = str_replace($arrBadwords["search"],$arrBadwords["replace"],$sSubject);
					$sBody = str_replace($arrBadwords["search"],$arrBadwords["replace"],$sBody);

					$objPrivateMessage = new cPrivateMessage();
					$objPrivateMessage->setDestinationUserId($objDestinationUser->getId());
					$objPrivateMessage->setAuthor($objActiveUser);
					$objPrivateMessage->setSubject($sSubject);
					$objPrivateMessage->setBody($sBody);
					$objPrivateMessage->setMessageTimestamp($this->m_objConfig->getAccessTimestamp());
					$objPrivateMessage->setIp($this->m_objServerHandler->getRemoteAddr());

					$iErrorId = $objPrivateMessage->insertData();
					if($iErrorId==0){

						// Create in-app notification
						$sNotificationTitle = "Neue private Nachricht";
						$sNotificationMessage = $objActiveUser->getUserName().' hat dir eine PM gesendet: "'.$sSubject.'"';
						$sNotificationLink = "pxmboard.php?mode=privatemessage&type=inbox&msgid=".$objPrivateMessage->getId();

						cNotification::createNotification(
							$objDestinationUser->getId(),
							NotificationType::PRIVATE_MESSAGE,
							$sNotificationTitle,
							$sNotificationMessage,
							$sNotificationLink,
							0,
							$objPrivateMessage->getId()
						);

						// Send email notification
						if($objDestinationUser->sendPrivateMessageNotification() && ($sMail = $objDestinationUser->getPrivateMail())){

							require_once(SRCDIR . '/Model/cTemplate.php');
							$objPrivateMessageMailSubject = new cTemplate();
							$objPrivateMessageMailSubject->loadDataById(11);
							$objPrivateMessageMailBody = new cTemplate();
							$objPrivateMessageMailBody->loadDataById(12);

							@mail($sMail,
									$objPrivateMessageMailSubject->getMessage(),
									str_replace("%username%",$objActiveUser->getUserName(),$objPrivateMessageMailBody->getMessage()),
									"From: ".$this->m_objConfig->getMailWebmaster()."\nReply-To: ".$this->m_objConfig->getMailWebmaster());
						}
						// Use consolidated confirm template (Story 18)
						$this->m_objTemplate = $this->_getTemplateObject("confirm");
						$this->m_objTemplate->addData($this->getContextDataArray(array(
							"show_pm_tabs" => true,
							"type" => "outbox"
						)));
						$this->m_objTemplate->addData(array(
							"message" => "Ihre private Nachricht wurde erfolgreich gesendet."
						));
					}
					else $this->m_objTemplate = $this->_getErrorTemplateObject(eError::COULD_NOT_INSERT_DATA);
				}
			}
			else $this->m_objTemplate = $this->_getErrorTemplateObject(eError::INVALID_USER_ID);// invalid user id
		}
		else $this->m_objTemplate = $this->_getErrorTemplateObject(eError::INVALID_USER_ID);	// invalid user id
	}
}
?>