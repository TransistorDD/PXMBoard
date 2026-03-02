<?php

declare(strict_types=1);
/**
 * holds the validation rules for string input
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
final class cStringValidations
{
    private const array LIMITS = [
        'boardmode'        => 30,
        'boardname'        => 100,
        'boarddescription' => 255,
        'sortmode'         => 20,
        'sortdirection'    => 4,
        'subject'          => 90,    // DB: pxm_message.m_subject varchar(100), 10 chars for reply prefix
        'body'             => 60000,
        'searchstring'     => 30,
        'username'         => 30,
        'password'         => 20,
        'email'            => 100,
        'city'             => 30,
        'firstname'        => 30,
        'lastname'         => 30,
        'signature'        => 100,
        'character'        => 1,
        'key'              => 32,
        'dateformat'       => 30,
        'notification'     => 2000,
        'quotesubject'     => 10,
        'directory'        => 100,
        'dbattributename'  => 15,
        'badword'          => 20,
        'textsearch'       => 20,
        'textreplace'      => 255,
        'skinvalue'        => 255,
        'error'            => 255,
        'type'             => 255,
        'csrf_token'       => 64,
    ];

    /**
     * Constructor (static class)
     *
     * @return void
     */
    private function __construct()
    {
    }

    /**
     * Truncate a string to the maximum length defined for the given type.
     * Uses mb_substr to correctly handle multi-byte characters.
     * Unknown types are returned unchanged.
     *
     * @param string $sValue value to truncate
     * @param string $sType  type key (must exist in LIMITS)
     * @return string truncated value
     */
    public static function truncate(string $sValue, string $sType): string
    {
        if (!isset(self::LIMITS[$sType])) {
            return $sValue;
        }
        $iLimit = self::LIMITS[$sType];
        if ($iLimit > 0 && mb_strlen($sValue) > $iLimit) {
            $sValue = mb_substr($sValue, 0, $iLimit);
        }
        return $sValue;
    }

    /**
     * Check whether a string contains only ASCII letters (a-z, A-Z).
     *
     * @param string $sValue value to check
     * @return bool true if value matches /^[a-zA-Z]+$/
     */
    public static function isAlpha(string $sValue): bool
    {
        return (bool)preg_match('/^[a-zA-Z]+$/', $sValue);
    }

    /**
     * Get the max length for a given type key.
     *
     * @param string $sType type key
     * @return int|null maximum length, or null if type is unknown
     */
    public static function getLength(string $sType): ?int
    {
        return self::LIMITS[$sType] ?? null;
    }

    /**
     * Get all type → length mappings.
     *
     * @return array<string,int>
     */
    public static function getAllLimits(): array
    {
        return self::LIMITS;
    }
}
