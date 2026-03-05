<?php

namespace PXMBoard\Enum;

use PXMBoard\I18n\cTranslator;

/**
 * Success message key enumeration (dot-notation i18n keys)
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
enum eSuccessKeys: string
{
    // ── Methods ────────────────────────────────────────────────────────────

    /**
     * Returns the translated success message for this key.
     *
     * @param array<string, mixed> $params optional placeholder replacements
     * @return string translated string
     */
    public function t(array $params = []): string
    {
        return cTranslator::translate($this->value, $params);
    }
    // ── Cases ──────────────────────────────────────────────────────────────

    /** A single message was deleted. */
    case MESSAGE_DELETED = 'success.message_deleted';

    /** An entire message sub-tree was deleted. */
    case MESSAGE_TREE_DELETED = 'success.message_tree_deleted';

    /** A message sub-tree was moved to a new parent. */
    case MESSAGE_TREE_MOVED = 'success.message_tree_moved';

    /** A message sub-tree was extracted into a new thread. */
    case MESSAGE_TREE_EXTRACTED = 'success.message_tree_extracted';

    /** A private message was sent. */
    case PRIVATE_MESSAGE_SENT = 'success.private_message_sent';

    /** A single notification was marked as read. */
    case NOTIFICATION_MARKED_READ = 'success.notification_marked_read';

    /** E-mail notification was enabled for a thread. */
    case NOTIFICATION_ENABLED = 'success.notification_enabled';

    /** E-mail notification was disabled for a thread. */
    case NOTIFICATION_DISABLED = 'success.notification_disabled';

    /** All notifications were marked as read. */
    case ALL_NOTIFICATIONS_READ = 'success.all_notifications_read';

    /** Thread was pinned/fixed. */
    case THREAD_FIXED = 'success.thread_fixed';

    /** Thread was unpinned/unfixed. */
    case THREAD_UNFIXED = 'success.thread_unfixed';

    /** Thread was reopened. */
    case THREAD_OPENED = 'success.thread_opened';

    /** Thread was closed. */
    case THREAD_CLOSED = 'success.thread_closed';

    /** Thread was moved to another board. */
    case THREAD_MOVED = 'success.thread_moved';

    /** User account was activated. */
    case USER_ACTIVATED = 'success.user_activated';

    /** User account was deactivated. */
    case USER_DEACTIVATED = 'success.user_deactivated';

    /** A device/session was logged out. */
    case DEVICE_LOGGED_OUT = 'success.device_logged_out';

    /** User configuration/preferences were saved. */
    case USER_CONFIG_SAVED = 'success.user_config_saved';

    /** User profile was saved. */
    case USER_PROFILE_SAVED = 'success.user_profile_saved';

    /** New user registration was completed. */
    case USER_REGISTERED = 'success.user_registered';

    /** A new password was sent via e-mail. */
    case USER_PASSWORD_SENT = 'success.user_password_sent';

    /** Password reset was requested and processed. */
    case USER_PASSWORD_RESET_REQUESTED = 'success.user_password_reset_requested';

    /** Password was changed successfully. */
    case USER_PASSWORD_CHANGED = 'success.user_password_changed';

    /** Board status/access level was changed. */
    case BOARD_STATUS_CHANGED = 'success.board_status_changed';

    /** A draft message was deleted. */
    case DRAFT_DELETED = 'success.draft_deleted';

    /** A message was successfully updated/edited. */
    case MESSAGE_UPDATED = 'success.message_updated';

    /** A new message was successfully saved/posted. */
    case MESSAGE_SAVED = 'success.message_saved';

    /** A draft message was successfully saved (not yet published). */
    case DRAFT_SAVED = 'success.draft_saved';
}
