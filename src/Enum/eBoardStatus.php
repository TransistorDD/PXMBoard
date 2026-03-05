<?php

namespace PXMBoard\Enum;

use PXMBoard\I18n\cTranslator;

/**
 * Board access/permission status enumeration
 *
 * Defines the different access and write permission levels for boards.
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
enum eBoardStatus: int
{
    /**
     * Checks whether the board is readable for unauthenticated users.
     *
     * @return bool true if public can read
     */
    public function isPublicReadable(): bool
    {
        return $this === self::PUBLIC || $this === self::READONLY_PUBLIC;
    }

    /**
     * Checks whether regular authenticated users can write to the board.
     *
     * @return bool true if regular users can write
     */
    public function isWritable(): bool
    {
        return $this === self::PUBLIC || $this === self::MEMBERS_ONLY;
    }

    /**
     * Checks whether the board requires authentication to read.
     *
     * @return bool true if authentication is required
     */
    public function requiresAuthentication(): bool
    {
        return $this !== self::PUBLIC && $this !== self::READONLY_PUBLIC;
    }

    /**
     * Checks whether the board is closed (only moderators/admins may access).
     *
     * @return bool true if closed
     */
    public function isClosed(): bool
    {
        return $this === self::CLOSED;
    }

    /**
     * Returns the translated label for this board status.
     *
     * @return string translated label
     */
    public function getLabel(): string
    {
        return cTranslator::translate(match ($this) {
            self::PUBLIC           => 'board_status.public',
            self::MEMBERS_ONLY     => 'board_status.members_only',
            self::READONLY_PUBLIC  => 'board_status.readonly_public',
            self::READONLY_MEMBERS => 'board_status.readonly_members',
            self::CLOSED           => 'board_status.closed',
        });
    }

    /** Public board — everyone can read; authenticated users with post permission can write. */
    case PUBLIC = 1;

    /** Members only — only authenticated users can read and write (with post permission). */
    case MEMBERS_ONLY = 2;

    /** Read-only public — everyone can read; only moderators/admins can write. */
    case READONLY_PUBLIC = 3;

    /** Read-only members — only authenticated users can read; only moderators/admins can write. */
    case READONLY_MEMBERS = 4;

    /** Closed — only moderators and admins can access. */
    case CLOSED = 5;
}
