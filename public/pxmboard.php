<?php
/**
 * pxmboard mainfile
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
define('PUBLICDIR', __DIR__);						// web root (public/)

if(!file_exists(__DIR__ . '/pxmboard-basedir.php')){
	die('Configuration missing: pxmboard-basedir.php not found. Run public/install/install.php first.');
}
require_once(__DIR__ . '/pxmboard-basedir.php');	// defines BASEDIR
define('SRCDIR', BASEDIR . '/src');

if(!file_exists(BASEDIR . '/config/pxmboard-config.php')){
	die('This board is not properly installed. Run public/install/install.php first.');
}
// read configuration
$arrConfig        = require(BASEDIR . '/config/pxmboard-config.php');
$arrDatabase      = $arrConfig['database'];
$arrTemplateTypes = $arrConfig['template_types'];
$arrSearchEngine  = $arrConfig['search_engine'] ?? ['type' => 'MySql'];
$sSessionName     = $arrConfig['session_name'] ?? 'brdsid';

require_once(BASEDIR . '/vendor/autoload.php');
require_once(SRCDIR . '/Database/cDBFactory.php');
require_once(SRCDIR . '/Validation/cInputHandler.php');
require_once(SRCDIR . '/Model/cConfig.php');
require_once(SRCDIR . '/Model/cBoard.php');
require_once(SRCDIR . '/Model/cSession.php');
require_once(SRCDIR . '/Enum/eError.php');
require_once(SRCDIR . '/Search/cSearchEngineFactory.php');

// establish db connection via singleton
try {
	cDBFactory::getInstance($arrDatabase);
} catch (cDatabaseException $e) {
	die('Database error: ' . htmlspecialchars($e->getMessage()));
}

// Extract server variables for cConfig
$sUserAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
$sRemoteAddr = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';

// load general configuration
$objConfig = new cConfig($arrTemplateTypes, $sUserAgent, $sRemoteAddr);

// Initialize search engine singleton from configuration
if (!isset($arrSearchEngine)) {
	$arrSearchEngine = array('type' => 'MySql');
}
try {
	cSearchEngineFactory::getInstance($arrSearchEngine);
} catch (cSearchEngineException $e) {
	die('Search engine error: ' . htmlspecialchars($e->getMessage()));
}

// input handler for user input
$objInputHandler = new cInputHandler();

// configure cookie params
$sBaseDir = dirname($_SERVER['SCRIPT_NAME']); // path retrival
$sEncodedPath = rtrim(implode('/', array_map('rawurlencode', explode('/', $sBaseDir))), '/') . '/'; // encode special chars

$arrSessionCookieParams = ['lifetime' => 90 * (24 * 60 * 60),	// 90 Tage
							'path' => $sEncodedPath,
							'domain' => '',	// browser deaulft: Host-only
							'secure' => true,
							'httponly' => true,
							'samesite' => 'Strict'];

session_set_cookie_params($arrSessionCookieParams);

// Initialize session
$objSession = new cSession($sSessionName);

// Get UserID from session
$iUserId = 0;
$sCsrfToken = '';
if($objSession->sessionDataAvailable()){
	$objSession->startSession();
	$iUserId = intval($objSession->getSessionVar('userid'));
	$sCsrfToken = $objSession->ensureCsrfToken();
}
else{
	// Try auto-login via ticket cookie
	if($sLoginTicket = cSession::getCookieVar('ticket')){
		require_once(SRCDIR . '/Model/cUserConfig.php');
		require_once(SRCDIR . '/Enum/eUser.php');
		$objUser = new cUserConfig();
		if($objUser->loadDataByTicket($sLoginTicket) && ($objUser->getStatus() === UserStatus::ACTIVE)){
			$objSession->startSession();
			$objSession->setSessionVar('userid', $objUser->getId());
			$iUserId = $objUser->getId();
			$sCsrfToken = $objSession->ensureCsrfToken();
		}
		else{
			// Invalid ticket - delete cookie
			cSession::setCookieVar('ticket', '', time() - 3600);
		}
	}
}

// Get BoardID from request
$iBoardId = $objInputHandler->getIntFormVar('brdid',TRUE,TRUE,TRUE);

// switch board modes
$sBoardMode = $objInputHandler->getStringFormVar('mode','boardmode',TRUE,TRUE,'trim');

$sPath = '';
$arrBoardMode = array();
if(preg_match('/^(adm|ajax)?([a-zA-Z]+)$/',$sBoardMode,$arrBoardMode)){
	if($arrBoardMode[1] === 'adm'){
		$sClassName = 'cAdminAction';
		$sPath = 'Admin/';
	}
	elseif($arrBoardMode[1] === 'ajax'){
		$sClassName = 'cActionAjax';
		$sPath = 'Ajax/';
	}
	else{
		$sClassName = 'cAction';
	}
	$sClassName .= ucfirst(strtolower($arrBoardMode[2]));
}
else{
	$sClassName = 'cActionLogin';							// default mode
}

// include action class and instantiate object
try{
	if(file_exists(SRCDIR . '/Controller/'.$sPath.$sClassName.'.php')){
		include_once(SRCDIR . '/Controller/'.$sPath.$sClassName.'.php');
		$objAction = new $sClassName($objConfig, $iUserId, $iBoardId);
		$objAction->setCsrfToken($sCsrfToken);
	}
	else{														// invalid action -> show error
		include_once(SRCDIR . '/Controller/cActionError.php');
		$objAction = new cActionError($objConfig, $iUserId, $iBoardId);
	}
}
catch(SkinInitializationException $e){
	// Skin initialization failed - display error and exit
	die('ERROR: ' . htmlspecialchars($e->getMessage()));
}

// Validate base permissions and conditions before executing the action lifecycle
if($objAction->validateBasePermissionsAndConditions()){

	// execute the pre-actions
	$objAction->doPreActions();

	// do the action
	$objAction->performAction();

	// execute the post-actions
	$objAction->doPostActions();

	// Update session based on action result
	$objActiveUser = $objAction->getActiveUser();
	if($objActiveUser){
		// User is logged in - store UserID in session
		$objSession->startSession();
		$objSession->setSessionVar('userid', $objActiveUser->getId());
	}
	elseif($objSession->sessionDataAvailable()){
		// No active user but session exists - destroy it
		$objSession->destroySession();
	}
}

// close the session before parsing the template to unlock the sessionfile
$objSession->writeCloseSession();

// output the result
echo $objAction->getOutput();
?>