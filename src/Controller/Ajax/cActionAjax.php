<?php
require_once(SRCDIR . '/Controller/cAction.php');
require_once(SRCDIR . '/Enum/eError.php');
require_once(SRCDIR . '/Enum/eSuccessMessage.php');
/**
 * Base class for AJAX actions with JSON response
 *
 * IMPORTANT: Do not use exit()! The framework lifecycle must remain intact.
 * JSON response is returned via getOutput().
 *
 * @author Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright Torsten Rentsch 2001 - 2026
 */
abstract class cActionAjax extends cAction {
	protected array $m_arrJsonData = [];
	protected int $m_iHttpStatusCode = 200;

	/**
	 * Initialize skin - AJAX actions don't use template engine
	 *
	 * @return bool always true (no template needed)
	 */
	public function initSkin(): bool {
		return true;
	}

	/**
	 * Require user to be authenticated (logged in)
	 * Overrides parent to set JSON error instead of template
	 *
	 * @return bool true if user is authenticated, false otherwise
	 */
	protected function _requireAuthentication(): bool {
		if(!is_object($this->m_objActiveUser)){
			$this->_setJsonError(eError::NOT_LOGGED_IN, 401);
			return false;
		}
		return true;
	}

	/**
	 * Require user to NOT be authenticated (for registration, password reset)
	 * Overrides parent to set JSON error instead of template
	 *
	 * @return bool true if user is not authenticated, false otherwise
	 */
	protected function _requireNotAuthenticated(): bool {
		if(is_object($this->m_objActiveUser)){
			$this->_setJsonError(eError::ALREADY_LOGGED_IN, 400);
			return false;
		}
		return true;
	}

	/**
	 * Require board to be readable (checks authentication if needed)
	 * Overrides parent to set JSON error instead of template
	 *
	 * @return bool true if board is readable, false otherwise
	 */
	protected function _requireReadableBoard(): bool {
		if(!is_object($this->m_objActiveBoard)){
			$this->_setJsonError(eError::BOARD_ID_MISSING, 400);
			return false;
		}

		$eStatus = $this->m_objActiveBoard->getStatus();

		// Closed boards: only mods/admins
		if($eStatus === BoardStatus::CLOSED){
			if(!$this->m_objActiveUser?->isAdmin() && !$this->m_objActiveUser?->isModerator($this->m_objActiveBoard->getId())){
				$this->_setJsonError(eError::BOARD_CLOSED, 403);
				return false;
			}
		}

		// Members-only boards: require authentication
		if($eStatus->requiresAuthentication()){
			if(!$this->_requireAuthentication()){
				return false;
			}
		}

		return true;
	}

	/**
	 * Require board to be writable (for posting messages)
	 * Overrides parent to set JSON error instead of template
	 *
	 * @return bool true if board is writable, false otherwise
	 */
	protected function _requireWritableBoard(): bool {
		if(!$this->_requireReadableBoard()){
			return false; // Must be readable first
		}

		$eStatus = $this->m_objActiveBoard->getStatus();

		// Read-only or closed: only mods/admins
		if(!$eStatus->isWritable()){
			if(!$this->m_objActiveUser?->isAdmin() && !$this->m_objActiveUser?->isModerator($this->m_objActiveBoard->getId())){
				$this->_setJsonError(eError::BOARD_READONLY, 403);
				return false;
			}
		}

		return true;
	}

	/**
	 * Require an active board to be set (exists and is active)
	 * Overrides parent to set JSON error instead of template
	 * @deprecated Use _requireReadableBoard() instead
	 *
	 * @return bool true if board is set and active, false otherwise
	 */
	protected function _requireActiveBoard(): bool {
		return $this->_requireReadableBoard();
	}

	/**
	 * Require board to be set (regardless of active status)
	 * Overrides parent to set JSON error instead of template
	 *
	 * @return bool true if board is set, false otherwise
	 */
	protected function _requireBoard(): bool {
		if(!is_object($this->m_objActiveBoard)){
			$this->_setJsonError(eError::BOARD_ID_MISSING, 400);
			return false;
		}
		return true;
	}

	/**
	 * Require user to have posting permission
	 * Automatically checks authentication first
	 * Overrides parent to set JSON error instead of template
	 *
	 * @return bool true if user can post, false otherwise
	 */
	protected function _requirePostPermission(): bool {
		if(!$this->_requireAuthentication()){
			return false;
		}
		if(!$this->m_objActiveUser?->isPostAllowed()){
			$this->_setJsonError(eError::NOT_AUTHORIZED, 403);
			return false;
		}
		return true;
	}

	/**
	 * Require user to be moderator of current board or admin
	 * Automatically checks authentication and board first
	 * Overrides parent to set JSON error instead of template
	 *
	 * @return bool true if user is moderator or admin, false otherwise
	 */
	protected function _requireModeratorPermission(): bool {
		if(!$this->_requireAuthentication() || !$this->_requireBoard()){
			return false;
		}
		if(!$this->m_objActiveUser?->isAdmin() && !$this->m_objActiveUser?->isModerator($this->m_objActiveBoard->getId())){
			$this->_setJsonError(eError::NOT_AUTHORIZED, 403);
			return false;
		}
		return true;
	}

	/**
	 * Require user to be admin
	 * Automatically checks authentication first
	 * Overrides parent to set JSON error instead of template
	 *
	 * @return bool true if user is admin, false otherwise
	 */
	protected function _requireAdminPermission(): bool {
		if(!$this->_requireAuthentication()){
			return false;
		}
		if(!$this->m_objActiveUser?->isAdmin()){
			$this->_setJsonError(eError::NOT_AUTHORIZED, 403);
			return false;
		}
		return true;
	}

	/**
	 * Set JSON response data (instead of template)
	 *
	 * @param array $arrData data for JSON response
	 * @param int $iHttpCode HTTP status code (200, 400, 404, 500)
	 */
	protected function _setJsonResponse(array $arrData, int $iHttpCode = 200): void {
		$this->m_arrJsonData = $arrData;
		$this->m_iHttpStatusCode = $iHttpCode;
	}

	/**
	 * Set error response using error enum
	 *
	 * @param eError $eErrorCode error enum constant
	 * @param int $iHttpCode HTTP status code (400, 401, 403, 404, 500)
	 */
	protected function _setJsonError(eError $eErrorCode, int $iHttpCode = 400): void {
		$this->_setJsonResponse(['error' => $eErrorCode->value], $iHttpCode);
	}

	/**
	 * Set success response using success message enum
	 *
	 * @param eSuccessMessage $eSuccessMessage success message enum constant
	 * @param array $arrAdditionalData optional additional data (e.g. count, isActive, etc.)
	 * @param int $iHttpCode HTTP status code (default 200)
	 */
	protected function _setJsonSuccess(eSuccessMessage $eSuccessMessage, array $arrAdditionalData = [], int $iHttpCode = 200): void {
		$arrResponse = array_merge(['success' => true, 'message' => $eSuccessMessage->value], $arrAdditionalData);
		$this->_setJsonResponse($arrResponse, $iHttpCode);
	}

	/**
	 * Generate output - returns JSON
	 *
	 * @return string JSON response
	 */
	public function getOutput(): string {
		header('Content-Type: application/json; charset=utf-8');
		http_response_code($this->m_iHttpStatusCode);
		return json_encode($this->m_arrJsonData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	}
}
?>
