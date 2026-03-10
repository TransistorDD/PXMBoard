<?php

declare(strict_types=1);

namespace PXMBoard\Validation;

/**
 * Encapsulates access to the $_SERVER superglobal
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cServerHandler
{
    /**
     * Get the X-CSRF-Token HTTP request header value
     *
     * @return string token or empty string
     */
    public function getHttpCsrfToken(): string
    {
        return $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    }

    /**
     * Get the HTTP request method (GET, POST, etc.)
     *
     * @return string request method or empty string
     */
    public function getRequestMethod(): string
    {
        return $_SERVER['REQUEST_METHOD'] ?? '';
    }

    /**
     * Get the HTTP Host header value
     *
     * @return string host or 'localhost'
     */
    public function getHttpHost(): string
    {
        return $_SERVER['HTTP_HOST'] ?? 'localhost';
    }

    /**
     * Get the HTTP User-Agent header value
     *
     * @return string user agent or empty string
     */
    public function getUserAgent(): string
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? '';
    }

    /**
     * Get the client's remote IP address
     *
     * @return string remote address or empty string
     */
    public function getRemoteAddr(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? '';
    }

    /**
     * Get the SCRIPT_NAME server variable
     *
     * @return string script name or empty string
     */
    public function getScriptName(): string
    {
        return $_SERVER['SCRIPT_NAME'] ?? '';
    }

    /**
     * Check whether the current request was made by HTMX (HX-Request: true header present)
     *
     * @return bool true if the request originates from HTMX
     */
    public function isHtmxRequest(): bool
    {
        return ($_SERVER['HTTP_HX_REQUEST'] ?? '') === 'true';
    }

    /**
     * Check whether the current request was made over HTTPS
     *
     * @return bool true if HTTPS is active
     */
    public function isHttps(): bool
    {
        return !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    }
}
