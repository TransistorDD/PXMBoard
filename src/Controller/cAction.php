<?php
require_once(SRCDIR . '/Model/cSkin.php');
require_once(SRCDIR . '/Model/cUserConfig.php');
require_once(SRCDIR . '/Enum/eUser.php');
require_once(SRCDIR . '/Validation/cInputHandler.php');
require_once(SRCDIR . '/Validation/cServerHandler.php');
require_once(SRCDIR . '/Parser/cPxmParser.php');
require_once(SRCDIR . '/Skin/cSkinTemplateFactory.php');
require_once(SRCDIR . '/Exception/SkinInitializationException.php');
/**
 * base class for the board actions
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
 abstract class cAction{

	protected mixed $m_objConfig;
	protected mixed $m_objTemplate;
	protected mixed $m_objInputHandler;
	protected cServerHandler $m_objServerHandler;
	protected ?cUserConfig $m_objActiveUser;
	protected mixed $m_objActiveBoard;
	protected mixed $m_objActiveSkin;
	protected ?string $m_sCsrfToken = null;

	/**
	 * Constructor
	 *
	 * @param cConfig $objConfig configuration data of the board
	 * @param int $iUserId user id from session (0 = guest)
	 * @param int $iBoardId board id from request (0 = no board)
	 * @return void
	 */
	public function __construct(cConfig $objConfig, int $iUserId = 0, int $iBoardId = 0){

		$this->m_objConfig = $objConfig;
		$this->m_objTemplate = null;
		$this->m_objInputHandler = new cInputHandler();
		$this->m_objServerHandler = new cServerHandler();

		$this->m_objActiveUser = null;
		$this->m_objActiveBoard = null;
		$this->m_objActiveSkin = null;

		// Load user if UserID provided
		if($iUserId > 0){
			$this->_loadActiveUser($iUserId);
		}

		// Load board if BoardID provided
		if($iBoardId > 0){
			$this->_loadActiveBoard($iBoardId);
		}

		// Initialize skin (with user preferences if user is logged in)
		if(!$this->initSkin()){
			throw new SkinInitializationException("Could not initialize skin. Check configuration.");
		}
	}


	/**
	 * Validate all base permissions and conditions required for this action
	 *
	 * MUST be implemented by every action class.
	 * Sets error template via _getErrorTemplateObject() on failure.
	 * Called by pxmboard.php before the action lifecycle begins.
	 *
	 * @return bool true if all permissions and conditions are met, false otherwise
	 */
	abstract public function validateBasePermissionsAndConditions(): bool;

	/**
	 * Require user to be authenticated (logged in)
	 *
	 * @return bool true if user is authenticated, false otherwise
	 */
	protected function _requireAuthentication(): bool {
		if(!is_object($this->m_objActiveUser)){
			$this->m_objTemplate = $this->_getErrorTemplateObject(eError::NOT_LOGGED_IN);
			return false;
		}
		return true;
	}

	/**
	 * Require user to NOT be authenticated (for registration, password reset)
	 *
	 * @return bool true if user is not authenticated, false otherwise
	 */
	protected function _requireNotAuthenticated(): bool {
		if(is_object($this->m_objActiveUser)){
			$this->m_objTemplate = $this->_getErrorTemplateObject(eError::ALREADY_LOGGED_IN);
			return false;
		}
		return true;
	}

	/**
	 * Require board to be readable (checks authentication if needed)
	 * PUBLIC and READONLY_PUBLIC: always readable
	 * MEMBERS_ONLY and READONLY_MEMBERS: requires authentication
	 * CLOSED: requires moderator/admin
	 *
	 * @return bool true if board is readable, false otherwise
	 */
	protected function _requireReadableBoard(): bool {
		if(!is_object($this->m_objActiveBoard)){
			$this->m_objTemplate = $this->_getErrorTemplateObject(eError::BOARD_ID_MISSING);
			return false;
		}

		$eStatus = $this->m_objActiveBoard->getStatus();

		// Closed boards: only mods/admins
		if($eStatus === BoardStatus::CLOSED){
			if(!$this->m_objActiveUser?->isAdmin() && !$this->m_objActiveUser?->isModerator($this->m_objActiveBoard->getId())){
				$this->m_objTemplate = $this->_getErrorTemplateObject(eError::BOARD_CLOSED);
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
	 * PUBLIC and MEMBERS_ONLY: writable for users with post permission
	 * READONLY_*: only mods/admins can write
	 * CLOSED: only mods/admins can write
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
				$this->m_objTemplate = $this->_getErrorTemplateObject(eError::BOARD_READONLY);
				return false;
			}
		}

		return true;
	}

	/**
	 * Require an active board to be set (exists and is active)
	 * @deprecated Use _requireReadableBoard() instead
	 *
	 * @return bool true if board is set and active, false otherwise
	 */
	protected function _requireActiveBoard(): bool {
		return $this->_requireReadableBoard();
	}

	/**
	 * Require board to be set (regardless of active status)
	 *
	 * @return bool true if board is set, false otherwise
	 */
	protected function _requireBoard(): bool {
		if(!is_object($this->m_objActiveBoard)){
			$this->m_objTemplate = $this->_getErrorTemplateObject(eError::BOARD_ID_MISSING);
			return false;
		}
		return true;
	}

	/**
	 * Require user to have posting permission
	 * Automatically checks authentication first
	 *
	 * @return bool true if user can post, false otherwise
	 */
	protected function _requirePostPermission(): bool {
		if(!$this->_requireAuthentication()){
			return false;
		}
		if(!$this->m_objActiveUser?->isPostAllowed()){
			$this->m_objTemplate = $this->_getErrorTemplateObject(eError::NOT_AUTHORIZED);
			return false;
		}
		return true;
	}

	/**
	 * Require user to be moderator of current board or admin
	 * Automatically checks authentication and board first
	 *
	 * @return bool true if user is moderator or admin, false otherwise
	 */
	protected function _requireModeratorPermission(): bool {
		if(!$this->_requireAuthentication() || !$this->_requireBoard()){
			return false;
		}
		if(!$this->m_objActiveUser?->isAdmin() && !$this->m_objActiveUser?->isModerator($this->m_objActiveBoard->getId())){
			$this->m_objTemplate = $this->_getErrorTemplateObject(eError::NOT_AUTHORIZED);
			return false;
		}
		return true;
	}

	/**
	 * Require user to be admin
	 * Automatically checks authentication first
	 *
	 * @return bool true if user is admin, false otherwise
	 */
	protected function _requireAdminPermission(): bool {
		if(!$this->_requireAuthentication()){
			return false;
		}
		if(!$this->m_objActiveUser?->isAdmin()){
			$this->m_objTemplate = $this->_getErrorTemplateObject(eError::NOT_AUTHORIZED);
			return false;
		}
		return true;
	}

	/**
	 * Set the CSRF token that was issued for the current session.
	 * Called by pxmboard.php after instantiating the action.
	 *
	 * @param string $sToken token from the session
	 * @return void
	 */
	public function setCsrfToken(string $sToken): void {
		$this->m_sCsrfToken = $sToken;
	}

	/**
	 * Validate the CSRF token submitted with the current request.
	 * Checks the X-CSRF-Token HTTP header first; falls back to POST field.
	 *
	 * @return bool true if the token is valid, false otherwise
	 */
	protected function _requireValidCsrfToken(): bool {
		$sToken = $this->m_objServerHandler->getHttpCsrfToken();
		if(empty($sToken)){
			$sToken = $this->m_objInputHandler->getStringFormVar(
				'csrf_token', 'csrf_token', true, false, 'trim'
			);
		}
		if(empty($sToken) || empty($this->m_sCsrfToken)
			|| !hash_equals($this->m_sCsrfToken, $sToken)
		){
			$this->m_objTemplate = $this->_getErrorTemplateObject(eError::CSRF_TOKEN_INVALID);
			return false;
		}
		return true;
	}

	/**
	 * do the pre actions (manipulate GET and POST data etc.)
	 *
	 * @return void
	 */
	public function doPreActions(): void{
	}

	/**
	 * perform the action
	 *
	 * @return void
	 */
	public function performAction(): void{
	}

	/**
	 * do the post actions (what should happen after performin the action?)
	 *
	 * @return void
	 */
	public function doPostActions(): void{
	}

	/**
	 * get the output of this action
	 *
	 * @return string output of this action
	 */
	public function getOutput(): string{
		if(is_object($this->m_objTemplate)){
			return $this->m_objTemplate->getOutput();
		}
		else{
			return "Overwrite getOutput() for actions that don't use templates";
		}
	}

	/**
	 * get the template object
	 *
	 * @param string $sTemplateName name of the template
	 * @return cSkinTemplate template
	 */
	protected function _getTemplateObject(string $sTemplateName): cSkinTemplate {
		$objTemplate = cSkinTemplateFactory::getTemplateObject($this->m_objConfig->getActiveTemplateEngine(),$this->m_objConfig->getSkinDirectory().$this->m_objActiveSkin->getDirectory());
		$objTemplate->setTemplateName($sTemplateName);
		return $objTemplate;
	}

	/**
	 * get the error template object
	 *
	 * @param eError $error error enum
	 * @return cSkinTemplate template
	 */
	protected function _getErrorTemplateObject(eError $error): cSkinTemplate {
		require_once(SRCDIR . '/Enum/eError.php');
		$objTemplate = cSkinTemplateFactory::getTemplateObject($this->m_objConfig->getActiveTemplateEngine(),$this->m_objConfig->getSkinDirectory().$this->m_objActiveSkin->getDirectory());
		$sTemplateName = "error-".strtolower(get_class($this));
		if(!$objTemplate->isTemplateValid($sTemplateName)){
			$sTemplateName = "error";
		}
		$objTemplate->setTemplateName($sTemplateName);
		$objTemplate->addData($this->getContextDataArray());
		$objTemplate->addData(array("error" => array("text" => $error->value)));
		return $objTemplate;
	}

	/**
	 * Load active user by ID
	 *
	 * @param int $iUserId user id
	 * @return void
	 */
	protected function _loadActiveUser(int $iUserId): void {

		$objUser = new cUserConfig();
		if($objUser->loadDataById($iUserId)){
			// Only set as active user if status is active
			if($objUser->getStatus() === UserStatus::ACTIVE){
				$this->m_objActiveUser = $objUser;

				// Update online timestamp
				if($this->m_objConfig->getOnlineTime() > 0){
					$this->m_objActiveUser->updateLastOnlineTimestamp($this->m_objConfig->getAccessTimestamp());
				}
			}
		}
	}

	/**
	 * Load active board by ID
	 *
	 * @param int $iBoardId board id
	 * @return void
	 */
	protected function _loadActiveBoard(int $iBoardId): void {
		require_once(SRCDIR . '/Model/cBoard.php');

		$objBoard = new cBoard();
		if($objBoard->loadDataById($iBoardId)){
			$this->m_objActiveBoard = $objBoard;
		}
	}

	/**
	 * Get active user
	 *
	 * @return cUserConfig|null active user object or null
	 */
	public function getActiveUser():? cUserConfig {
		return $this->m_objActiveUser;
	}

	/**
	 * Get active board
	 *
	 * @return cBoard active board object or null
	 */
	public function getActiveBoard():? cBoard {
		return $this->m_objActiveBoard;
	}

	/**
	 * Get active skin
	 *
	 * @return cSkin active skin object or null
	 */
	public function getActiveSkin():? cSkin {
		return $this->m_objActiveSkin;
	}

	/**
	 * Initialize the skin for output
	 *
	 * @return boolean success / failure
	 */
	public function initSkin(): bool {

		$bReturn = true;

		if(is_object($this->m_objActiveUser) && $this->m_objActiveUser->getSkinId() > 0){
			$iSkinId = $this->m_objActiveUser->getSkinId();
		}
		else{
			$iSkinId = $this->m_objConfig->getDefaultSkinId();
		}

		$this->m_objActiveSkin = new cSkin();

		$arrValidTemplateEngines = array();

		if(!$this->m_objActiveSkin->loadDataById($iSkinId)
		|| !($arrValidTemplateEngines = array_intersect($this->m_objConfig->getAvailableTemplateEngines(),$this->m_objActiveSkin->getSupportedTemplateEngines()))){
			if($iSkinId == $this->m_objConfig->getDefaultSkinId()
			|| !$this->m_objActiveSkin->loadDataById($this->m_objConfig->getDefaultSkinId())
			|| !($arrValidTemplateEngines = array_intersect($this->m_objConfig->getAvailableTemplateEngines(),$this->m_objActiveSkin->getSupportedTemplateEngines()))){
				$bReturn = false;
			}
		}

		if($bReturn){
			reset($arrValidTemplateEngines);
			$sActiveTemplateEngine = current($arrValidTemplateEngines);
			$this->m_objConfig->setActiveTemplateEngine($sActiveTemplateEngine);
		}
		return $bReturn;
	}

	/**
	 * Externe Inhalte einbetten? (Bilder, YouTube, Twitch)
	 *
	 * @return boolean externe Inhalte einbetten?
	 */
	public function embedExternal(): bool{
		$bEmbedExternal = false;
		if(is_object($this->m_objActiveUser)){
			$bEmbedExternal = $this->m_objActiveUser->embedExternal();
		}
		else if(is_object($this->m_objActiveBoard)){
			$bEmbedExternal = $this->m_objActiveBoard->embedExternal();
		}
		return $bEmbedExternal;
	}

	/**
	 * do textreplacements (smilies etc.)?
	 *
	 * @return boolean do / don't do textreplacements
	 */
	public function doTextReplacements(): bool{
		$bDoTextReplacements = false;
		if(is_object($this->m_objActiveBoard)){
			$bDoTextReplacements = $this->m_objActiveBoard->doTextReplacements();
		}
		return $bDoTextReplacements;
	}

	/**
	 * Get context data array for templates
	 *
	 * @param array $arrAdditionalData additional data
	 * @return array context data
	 */
	protected function getContextDataArray(array $arrAdditionalData = array()): array{
		$arrContext = array(
			"logedin" => is_object($this->m_objActiveUser) ? "1" : "0",
			"admin" => "0",
			"moderator" => "0",
			 "timespan" => "0"
		);

		if(is_object($this->m_objActiveBoard)){
			$arrContext["board"] = array(
				"id" => $this->m_objActiveBoard->getId(),
				"name" => $this->m_objActiveBoard->getName()
			);
			$arrContext["timespan"] = $this->m_objActiveBoard->getThreadListTimeSpan();
		}

		if(is_object($this->m_objActiveUser)){
			$arrContext["admin"] = $this->m_objActiveUser->isAdmin() ? "1" : "0";
			if(is_object($this->m_objActiveBoard)){
				$arrContext["moderator"] = $this->m_objActiveUser->isModerator($this->m_objActiveBoard->getId()) ? "1" : "0";
			}
			$arrContext["user"] = array(
				"id" => $this->m_objActiveUser->getId(),
				"username" => $this->m_objActiveUser->getUserName(),
				"imgfile" => $this->m_objActiveUser->getImageFileName(),
				"notification_unread_count" => $this->m_objActiveUser->getUnreadNotificationCount(),
				"priv_message_unread_count" => $this->m_objActiveUser->getUnreadPrivMessageCount()
			);
		}

		if(is_object($this->m_objActiveSkin)){
			$arrContext["skin"] = $this->m_objActiveSkin->getDataArray();
		}

		$arrContext["input_sizes"] = $this->m_objInputHandler->getInputSizes();
		$arrContext["csrf_token"] = $this->m_sCsrfToken ?? '';

		return array(
			"config" => array_merge_recursive(
				$this->m_objConfig->getDataArray()["config"] ?? array(),
				$arrContext,
				$arrAdditionalData
			)
		);
	}

	/**
	 * Get board list array for templates
	 *
	 * @return array board list data array
	 */
	protected function _getBoardListArray(): array {
		require_once(SRCDIR . '/Model/cBoardList.php');
		require_once(SRCDIR . '/Parser/cParser.php');

		$objParser = new cParser();
		$objBoardList = new cBoardList();
		$objBoardList->loadBasicData();

		return $objBoardList->getDataArray(
			$this->m_objConfig->getTimeOffset() * 3600,
			$this->m_objConfig->getDateFormat(),
			0,
			$objParser
		);
	}

	/**
	 * Get a predefined Pxm parser object
	 *
	 * @param bool $bDoTextReplacements should textreplacements be done?
	 * @param bool $bDoQuote should the data be enclosed in quotes?
	 * @return cPxmParser parser object
	 */
	protected function _getPredefinedPxmParser(bool $bDoTextReplacements = false, bool $bDoQuote = false): cPxmParser {
		$objPxmParser = new cPxmParser();
		$objPxmParser->setIsLoggedIn($this->m_objActiveUser !== null);
		$objPxmParser->setQuoteTag($this->m_objConfig->getQuoteTag());
		$objPxmParser->setEmbedExternal($this->embedExternal());
		$objPxmParser->setDoQuote($bDoQuote);
		$objPxmParser->setHttpHost($this->m_objServerHandler->getHttpHost());
		if($bDoTextReplacements){
			require_once(SRCDIR . '/Model/cTextreplacementList.php');
			$objTextreplacementList = new cTextreplacementList();
			$objPxmParser->setReplacements($objTextreplacementList->getList());
		}
		return $objPxmParser;
	}
}
?>