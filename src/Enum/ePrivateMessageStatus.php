<?php

/**
 * Private message status enumeration
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
enum ePrivateMessageStatus: int
{
    // ── Methods ────────────────────────────────────────────────────────────

    /**
     * Checks whether the message has not been read yet.
     *
     * @return bool true if UNREAD
     */
    public function isUnread(): bool
    {
        return $this === self::UNREAD;
    }

    /**
     * Checks whether the message has been read.
     *
     * @return bool true if READ
     */
    public function isRead(): bool
    {
        return $this === self::READ;
    }

    /**
     * Checks whether the message is soft-deleted.
     *
     * @return bool true if DELETED
     */
    public function isDeleted(): bool
    {
        return $this === self::DELETED;
    }
    // ── Cases ──────────────────────────────────────────────────────────────

    /** New, unread message from the recipient's perspective. */
    case UNREAD = 1;

    /** Message has been read by the recipient. */
    case READ = 2;

    /** Soft-deleted per participant; the other side may still see the message. */
    case DELETED = 3;
}
