<?php
/**
 * Message Status Enum
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
enum MessageStatus: int {
    /**
     * Draft - Only visible to author
     */
    case DRAFT = 0;

    /**
     * Published - Publicly visible (default state)
     */
    case PUBLISHED = 1;

    /**
     * Deleted - Soft-delete (for future implementation)
     * Instead of physically removing messages, mark them as DELETED
     */
    case DELETED = 2;

    /**
     * Get human-readable label
     */
    public function label(): string {
        return match($this) {
            self::DRAFT => 'Entwurf',
            self::PUBLISHED => 'Veröffentlicht',
            self::DELETED => 'Gelöscht',
        };
    }

    /**
     * Check if status allows public visibility
     */
    public function isPubliclyVisible(): bool {
        return $this === self::PUBLISHED;
    }

    /**
     * Check if status is draft
     */
    public function isDraft(): bool {
        return $this === self::DRAFT;
    }

    /**
     * Check if status is deleted
     */
    public function isDeleted(): bool {
        return $this === self::DELETED;
    }
}
?>
