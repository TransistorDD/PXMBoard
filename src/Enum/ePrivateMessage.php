<?php

/**
 * Private message status enumeration
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
enum PrivateMessageStatus: int
{
    /**
     * Check if the message has not been read yet
     *
     * @return bool true if unread
     */
    public function isUnread(): bool
    {
        return $this === self::UNREAD;
    }

    /**
     * Check if the message has been read
     *
     * @return bool true if read
     */
    public function isRead(): bool
    {
        return $this === self::READ;
    }

    /**
     * Check if the message is soft-deleted
     *
     * @return bool true if deleted
     */
    public function isDeleted(): bool
    {
        return $this === self::DELETED;
    }
    /**
     * New, unread message
     */
    case UNREAD = 1;

    /**
     * Message has been read
     */
    case READ = 2;

    /**
     * Soft-deleted (per participant — the other side may still see the message)
     */
    case DELETED = 3;
}
