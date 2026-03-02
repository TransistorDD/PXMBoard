<?php

/**
 * session and cookie support
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 *
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cSession
{
    /**
     * Constructor
     *
     * @param  string  $sSessionName  name of the session / cookie name
     * @return void
     */
    public function __construct(string $sSessionName)
    {
        session_name($sSessionName);
    }

    /**
     * is session data available?
     *
     * @return bool session data available / not available
     */
    public function sessionDataAvailable(): bool
    {
        return isset($_COOKIE[session_name()]) || isset($_POST[session_name()]) || isset($_GET[session_name()]);
    }

    /**
     * start the session
     *
     * @return void
     */
    public function startSession()
    {
        @session_start();
    }

    /**
     * get the session id
     *
     * @return string session id
     *
     * @see cSession::getSid()
     */
    public function getSessionId(): string
    {
        return session_id();
    }

    /**
     * get the session name
     *
     * @return string session name
     */
    public function getSessionName(): string
    {
        return session_name();
    }

    /**
     * store the session data and end the session
     *
     * @return void
     */
    public function writeCloseSession()
    {
        session_write_close();
    }

    /**
     * destroy the session
     *
     * @return bool success / failure
     */
    public function destroySession(): bool
    {
        $_SESSION = [];

        return @session_destroy();
    }

    /**
     * get the value for a session variable
     *
     * @param  string  $sVarName  name of the variable
     * @return mixed value of the variable
     */
    public function getSessionVar(string $sVarName): mixed
    {
        $mValue = null;
        if (isset($_SESSION[$sVarName])) {
            $mValue = $_SESSION[$sVarName];
        }

        return $mValue;
    }

    /**
     * Ensure a CSRF token exists in the session, creating one if absent.
     *
     * @return string the current (or newly generated) CSRF token
     */
    public function ensureCsrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }

    /**
     * Get the current CSRF token from the session.
     *
     * @return string|null the token, or null if no session / no token
     */
    public function getCsrfToken(): ?string
    {
        return $_SESSION['csrf_token'] ?? null;
    }

    /**
     * set the value of a session variable
     *
     * @param  string  $sVarName  name of the variable
     * @param  mixed  $mVarValue  value of the variable
     * @return void
     */
    public function setSessionVar(string $sVarName, mixed $mVarValue)
    {
        if ($mVarValue !== null) {
            $_SESSION[$sVarName] = $mVarValue;
        } else {
            unset($_SESSION[$sVarName]);
        }
    }

    /**
     * get the value of a cookie
     *
     * @param  string  $sVarName  name of the variable
     * @return string value of the variable
     */
    public static function getCookieVar(string $sVarName): string
    {
        $sValue = '';
        if (isset($_COOKIE[$sVarName])) {
            $sValue = $_COOKIE[$sVarName];
        }

        return $sValue;
    }

    /**
     * set a cookie
     *
     * @param  string  $sVarName  name of the variable
     * @param  string  $sVarValue  value of the variable
     * @param  int  $iExpireDate  when expires the cookie? (unix timestamp)
     * @return void
     */
    public static function setCookieVar(string $sVarName, string $sVarValue, int $iExpireDate)
    {
        if (strlen($sVarValue) > 0) {
            setcookie($sVarName, $sVarValue, $iExpireDate, '/');
        } else {
            setcookie($sVarName, '', $iExpireDate, '/');
        }
    }
}
