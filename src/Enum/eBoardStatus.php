<?php

/**
 * Board status enumeration
 *
 * Defines the different access and write permission levels for boards.
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
enum BoardStatus: int
{
    /**
     * Check if board is readable for public (non-authenticated users)
     *
     * @return bool true if public can read
     */
    public function isPublicReadable(): bool
    {
        return $this === self::PUBLIC || $this === self::READONLY_PUBLIC;
    }

    /**
     * Check if board is writable (for regular users, not mods/admins)
     *
     * @return bool true if regular users can write
     */
    public function isWritable(): bool
    {
        return $this === self::PUBLIC || $this === self::MEMBERS_ONLY;
    }

    /**
     * Check if board requires authentication to read
     *
     * @return bool true if authentication required
     */
    public function requiresAuthentication(): bool
    {
        return $this !== self::PUBLIC && $this !== self::READONLY_PUBLIC;
    }

    /**
     * Check if board is closed (only mods/admins can access)
     *
     * @return bool true if closed
     */
    public function isClosed(): bool
    {
        return $this === self::CLOSED;
    }

    /**
     * Get human-readable label (German)
     *
     * @return string label
     */
    public function getLabel(): string
    {
        return match($this) {
            self::PUBLIC => 'Öffentlich',
            self::MEMBERS_ONLY => 'Nur Mitglieder',
            self::READONLY_PUBLIC => 'Nur Lesen (Öffentlich)',
            self::READONLY_MEMBERS => 'Nur Lesen (Mitglieder)',
            self::CLOSED => 'Geschlossen',
        };
    }
    /**
     * Public board - everyone can read, authenticated users with post permission can write
     */
    case PUBLIC = 1;

    /**
     * Members only - only authenticated users can read and write (with post permission)
     */
    case MEMBERS_ONLY = 2;

    /**
     * Read-only public - everyone can read, only moderators/admins can write
     */
    case READONLY_PUBLIC = 3;

    /**
     * Read-only members - only authenticated users can read, only moderators/admins can write
     */
    case READONLY_MEMBERS = 4;

    /**
     * Closed - only moderators and admins can access
     */
    case CLOSED = 5;
}
