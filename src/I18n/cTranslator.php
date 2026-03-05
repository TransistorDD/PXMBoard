<?php

/**
 * Static translator for i18n string resolution
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cTranslator
{
    /** @var array<string, string> */
    private static array $messages = [];

    private static string $currentLocale = 'de';

    /**
     * Load translations for the given locale from lang/<locale>.php.
     * Calling this method a second time with the same locale is a no-op.
     *
     * @param string $locale locale identifier (e.g. 'de')
     * @return void
     */
    public static function load(string $locale = 'de'): void
    {
        self::$currentLocale = $locale;
        $path = dirname(__DIR__, 2) . '/lang/' . $locale . '.php';
        if (file_exists($path)) {
            /** @var array<string, string> $data */
            $data = include $path;
            self::$messages = $data;
        }
    }

    /**
     * Returns the currently loaded locale identifier.
     *
     * @return string current locale (e.g. 'de')
     */
    public static function getCurrentLocale(): string
    {
        return self::$currentLocale;
    }

    /**
     * Translate a dot-notation key, replacing %placeholder% tokens with values.
     * Falls back to the key itself when the key is not found in the loaded messages.
     *
     * @param string               $key    translation key (e.g. 'error.invalid_mode')
     * @param array<string, mixed> $params placeholder replacements (key => value)
     * @return string translated string, or $key if not found
     */
    public static function translate(string $key, array $params = []): string
    {
        $text = self::$messages[$key] ?? $key;
        foreach ($params as $k => $v) {
            $text = str_replace('%' . $k . '%', (string) $v, $text);
        }
        return $text;
    }
}
