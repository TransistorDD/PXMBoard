<?php
/**
 * Notification type and status enums
 *
 * @author Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright Torsten Rentsch 2001 - 2026
 */

enum NotificationType: string{
	case REPLY = 'reply';
	case PRIVATE_MESSAGE = 'private_message';
	case MENTION = 'mention';
	case DRAFT_REMINDER = 'draft_reminder';
	case THREAD_MOVED = 'thread_moved';
	case USER_ACTIVATED = 'user_activated';
}

enum NotificationStatus: string{
	case UNREAD = 'unread';
	case READ = 'read';
}
?>
