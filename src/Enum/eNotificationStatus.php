<?php

namespace PXMBoard\Enum;

/**
 * Notification read-status enumeration
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
enum eNotificationStatus: string
{
    // ── Cases ──────────────────────────────────────────────────────────────

    /** Notification has not been read yet. */
    case UNREAD = 'unread';

    /** Notification has been read. */
    case READ = 'read';
}
