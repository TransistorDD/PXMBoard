<?php

namespace PXMBoard\Enum;

use PXMBoard\I18n\cTranslator;

/**
 * User account status enumeration
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
enum eUserStatus: int
{
    // ── Methods ────────────────────────────────────────────────────────────

    /**
     * Returns the translated label for this status.
     *
     * @return string translated label
     */
    public function getLabel(): string
    {
        return cTranslator::translate(match ($this) {
            self::ACTIVE        => 'user_status.active',
            self::NOT_ACTIVATED => 'user_status.not_activated',
            self::DISABLED      => 'user_status.disabled',
        });
    }

    /**
     * Returns all user statuses as an associative array (value => label).
     *
     * @return array<int, string> status id mapped to translated label
     */
    public static function getAll(): array
    {
        return [
            self::ACTIVE->value        => self::ACTIVE->getLabel(),
            self::NOT_ACTIVATED->value => self::NOT_ACTIVATED->getLabel(),
            self::DISABLED->value      => self::DISABLED->getLabel(),
        ];
    }
    // ── Cases ──────────────────────────────────────────────────────────────

    /** Account is active and fully usable. */
    case ACTIVE = 1;

    /** Account was created but not yet activated (e-mail confirmation pending or admin approval required). */
    case NOT_ACTIVATED = 2;

    /** Account is disabled by an administrator. */
    case DISABLED = 3;
}
