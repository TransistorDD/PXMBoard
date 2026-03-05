<?php

namespace PXMBoard\Enum;

/**
 * Notification type enumeration
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
enum eNotificationType: string
{
    // ── Cases ──────────────────────────────────────────────────────────────

    /** Notification triggered by a reply to a watched message or thread. */
    case REPLY = 'reply';

    /** Notification triggered by a received private message. */
    case PRIVATE_MESSAGE = 'private_message';

    /** Notification triggered by an @-mention in a message. */
    case MENTION = 'mention';

    /** Reminder notification for an unsent draft message. */
    case DRAFT_REMINDER = 'draft_reminder';

    /** Notification triggered when a watched thread was moved to another board. */
    case THREAD_MOVED = 'thread_moved';

    /** Notification sent when a user account is activated by an admin. */
    case USER_ACTIVATED = 'user_activated';
}
