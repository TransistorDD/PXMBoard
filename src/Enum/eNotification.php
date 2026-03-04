<?php

/**
 * Notification type and status enums
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */

enum NotificationType: string
{
    case REPLY = 'reply';
    case PRIVATE_MESSAGE = 'private_message';
    case MENTION = 'mention';
    case DRAFT_REMINDER = 'draft_reminder';
    case THREAD_MOVED = 'thread_moved';
    case USER_ACTIVATED = 'user_activated';
}

enum NotificationStatus: string
{
    case UNREAD = 'unread';
    case READ = 'read';
}
