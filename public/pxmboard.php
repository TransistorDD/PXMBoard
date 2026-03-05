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

if (!file_exists(__DIR__ . '/pxmboard-basedir.php')) {
    die('Configuration missing: pxmboard-basedir.php not found. Run public/install/install.php first.');
}
require_once(__DIR__ . '/pxmboard-basedir.php');	// defines BASEDIR
define('SRCDIR', BASEDIR . '/src');

if (!file_exists(BASEDIR . '/config/pxmboard-config.php')) {
    die('This board is not properly installed. Run public/install/install.php first.');
}
// read configuration
$arrConfig        = require(BASEDIR . '/config/pxmboard-config.php');
$arrDatabase      = $arrConfig['database'];
$arrTemplateTypes = $arrConfig['template_types'];
$arrSearchEngine  = $arrConfig['search_engine'] ?? ['type' => 'MySql'];
$sSessionName     = $arrConfig['session_name'] ?? 'brdsid';

require_once(BASEDIR . '/vendor/autoload.php');

use PXMBoard\Database\cDBFactory;
use PXMBoard\Validation\cInputHandler;
use PXMBoard\Validation\cServerHandler;
use PXMBoard\Model\cConfig;
use PXMBoard\Model\cSession;
use PXMBoard\Model\cUserConfig;
use PXMBoard\I18n\cTranslator;
use PXMBoard\Search\cSearchEngineFactory;
use PXMBoard\Exception\cDatabaseException;
use PXMBoard\Exception\cSearchEngineException;
use PXMBoard\Exception\SkinInitializationException;
use PXMBoard\Enum\eUserStatus;
use PXMBoard\Controller\Board\cActionError;

// establish db connection via singleton
try {
    cDBFactory::getInstance($arrDatabase);
} catch (cDatabaseException $e) {
    die('Database error: ' . htmlspecialchars($e->getMessage()));
}

// load general configuration
$objConfig = new cConfig($arrTemplateTypes);

// Load language strings (locale can be extended from config or session later)
cTranslator::load('de');

// Initialize search engine singleton from configuration
try {
    cSearchEngineFactory::getInstance($arrSearchEngine);
} catch (cSearchEngineException $e) {
    die('Search engine error: ' . htmlspecialchars($e->getMessage()));
}

// input handler for server environment variables
$objServerHandler = new cServerHandler();

// input handler for user input
$objInputHandler = new cInputHandler();

// configure cookie params
$sBaseDir = dirname($objServerHandler->getScriptName());
$sEncodedPath = rtrim(implode('/', array_map('rawurlencode', explode('/', $sBaseDir))), '/') . '/'; // encode special chars

$arrSessionCookieParams = ['lifetime' => 90 * (24 * 60 * 60),	// 90 days
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
if ($objSession->sessionDataAvailable()) {
    $objSession->startSession();
    $iUserId = (int) $objSession->getSessionVar('userid');
    $sCsrfToken = $objSession->ensureCsrfToken();
} else {
    // Try auto-login via ticket cookie
    if ($sLoginTicket = cSession::getCookieVar('ticket')) {
        $objUser = new cUserConfig();
        if ($objUser->loadDataByTicket($sLoginTicket) && ($objUser->getStatus() === eUserStatus::ACTIVE)) {
            $objSession->startSession();
            $objSession->setSessionVar('userid', $objUser->getId());
            $iUserId = $objUser->getId();
            $sCsrfToken = $objSession->ensureCsrfToken();
        } else {
            // Invalid ticket - delete cookie
            cSession::setCookieVar('ticket', '', time() - 3600);
        }
    }
}

// Get BoardID from request
$iBoardId = $objInputHandler->getIntFormVar('brdid', true, true, true);

// switch board modes
$sBoardMode = $objInputHandler->getStringFormVar('mode', 'boardmode', true, true, 'trim');

$sPath = 'Board/';
$arrBoardMode = [];
if (preg_match('/^(adm|ajax)?([a-zA-Z]+)$/', $sBoardMode, $arrBoardMode)) {
    if ($arrBoardMode[1] === 'adm') {
        $sClassName = 'cAdminAction';
        $sPath = 'Admin/';
    } elseif ($arrBoardMode[1] === 'ajax') {
        $sClassName = 'cAjaxAction';
        $sPath = 'Ajax/';
    } else {
        $sClassName = 'cAction';
        $sPath = 'Board/';
    }
    $sClassName .= ucfirst(strtolower($arrBoardMode[2]));
} else {
    $sClassName = 'cActionLogin';							// default mode
    $sPath = 'Board/';
}

// For non-HTMX direct browser requests to partial actions: substitute with the fullpage Board action.
// HTMX automatically sets the HX-Request header on all partial loads; its absence means full-page browser access.
// Admin and Ajax actions are excluded from substitution via $sPath check.
if ($sPath === 'Board/' && !$objServerHandler->isHtmxRequest()) {
    $arrPartialRoutes = [
        'message'         => 'Board',
        'thread'          => 'Board',
        'threadlist'      => 'Board'
    ];
    $sResolvedMode = strtolower($arrBoardMode[2] ?? '');
    if (isset($arrPartialRoutes[$sResolvedMode])) {
        $sClassName = 'cAction' . $arrPartialRoutes[$sResolvedMode];
    }
}

// Construct FQCN and instantiate action object via PSR-4 autoloader
$sNamespace = match ($sPath) {
    'Admin/' => 'PXMBoard\\Controller\\Admin\\',
    'Ajax/'  => 'PXMBoard\\Controller\\Ajax\\',
    default  => 'PXMBoard\\Controller\\Board\\',
};
$sFqcn = $sNamespace . $sClassName;

try {
    if (class_exists($sFqcn)) {
        $objAction = new $sFqcn($objConfig, $iUserId, $iBoardId);
        $objAction->setCsrfToken($sCsrfToken);
    } else {														// invalid action -> show error
        $objAction = new cActionError($objConfig, $iUserId, $iBoardId);
    }
} catch (SkinInitializationException $e) {
    // Skin initialization failed - display error and exit
    die('ERROR: ' . htmlspecialchars($e->getMessage()));
}

// Validate base permissions and conditions before executing the action lifecycle
if ($objAction->validateBasePermissionsAndConditions()) {

    // execute the pre-actions
    $objAction->doPreActions();

    // do the action
    $objAction->performAction();

    // execute the post-actions
    $objAction->doPostActions();

    // Update session based on action result
    $objActiveUser = $objAction->getActiveUser();
    if ($objActiveUser) {
        // User is logged in - store UserID in session
        $objSession->startSession();
        $objSession->setSessionVar('userid', $objActiveUser->getId());
    } elseif ($objSession->sessionDataAvailable()) {
        // No active user but session exists - destroy it
        $objSession->destroySession();
    }
}

// close the session before parsing the template to unlock the sessionfile
$objSession->writeCloseSession();

// output the result
echo $objAction->getOutput();
