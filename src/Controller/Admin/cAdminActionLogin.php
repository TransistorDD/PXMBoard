<?php

namespace PXMBoard\Controller\Admin;

use PXMBoard\Enum\eUserStatus;
use PXMBoard\Model\cUserConfig;

/**
 * displays the admin login page
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cAdminActionLogin extends cAdminAction{

	/**
	 * Validate admin permissions - login page is accessible without admin rights
	 *
	 * @return bool always true
	 */
	public function validateBasePermissionsAndConditions(): bool {
		return true;
	}

	/**
	 * perform the action
	 *
	 * @return void
	 */
	public function performAction(): void{

		// Already logged in as admin → redirect to intro
		if($objActiveUser = $this->getActiveUser()){
			if($objActiveUser->isAdmin()){
				$this->m_sOutput = $this->_getRedirect('pxmboard.php?mode=admintro');
				return;
			}
			$this->m_sOutput = $this->_getHead(false)
				. $this->_getAlert('Access denied. Admin privileges required.')
				. $this->_getFooter();
			return;
		}

		$sUserName = $this->m_objInputHandler->getStringFormVar("nick","username",true,true,"trim");
		$sPassword = $this->m_objInputHandler->getStringFormVar("pass","password",true,true,"trim");
		$sError    = "";

		if(!empty($sUserName)){

			$objUser = new cUserConfig();

			if($objUser->loadDataByUserName($sUserName)){

				if($objUser->validatePassword($sPassword)){
					if($objUser->getStatus() === eUserStatus::ACTIVE){
						if($objUser->isAdmin()){
							// Set active user - pxmboard.php will update session, then getOutput() redirects
							$this->m_objActiveUser = $objUser;
							$this->m_sOutput = $this->_getRedirect('pxmboard.php?mode=admintro');
							return;
						}
						else{
							$sError = 'Access denied. Admin privileges required.';
						}
					}
					else{
						$sError = 'Account is not active.';
					}
				}
				else{
					$sError = 'Invalid password.';
				}
			}
			else{
				$sError = 'Username not found.';
			}
		}

		$this->m_sOutput  = $this->_getHead(false);
		$this->m_sOutput .= "<div class=\"pxm-admin-card\">\n";
		$this->m_sOutput .= "<div class=\"pxm-admin-card__header\">Admin Login</div>\n";
		$this->m_sOutput .= "<div class=\"pxm-admin-card__body\">\n";

		if(!empty($sError)){
			$this->m_sOutput .= $this->_getAlert($sError);
		}

		$this->m_sOutput .= "<form action=\"pxmboard.php\" method=\"post\">\n";
		$this->m_sOutput .= $this->_getHiddenField("mode","admlogin");
		$this->m_sOutput .= $this->_getTextField("nick", $this->m_objInputHandler->getInputSize("username"), "", "Username");
		$this->m_sOutput .= $this->_getPasswordField("pass", $this->m_objInputHandler->getInputSize("password"), "Password");
		$this->m_sOutput .= "<div class=\"pxm-btn-row\">\n";
		$this->m_sOutput .= "<button type=\"submit\" class=\"pxm-btn pxm-btn--primary\">Login</button>\n";
		$this->m_sOutput .= "</div>\n";
		$this->m_sOutput .= "</form>\n";
		$this->m_sOutput .= "</div>\n</div>\n";
		$this->m_sOutput .= $this->_getFooter();
	}

	/**
	 * get a meta-refresh redirect response
	 *
	 * @param string $sUrl target URL
	 * @return string redirect HTML
	 */
	private function _getRedirect(string $sUrl): string {
		return "<!DOCTYPE html>\n<html lang=\"en\">\n<head>\n"
			. "<meta charset=\"UTF-8\">\n"
			. "<meta http-equiv=\"refresh\" content=\"0; url=" . htmlspecialchars($sUrl) . "\">\n"
			. "</head>\n<body></body>\n</html>\n";
	}
}
?>