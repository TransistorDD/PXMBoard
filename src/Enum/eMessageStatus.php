<?php

namespace PXMBoard\Enum;

use PXMBoard\I18n\cTranslator;

/**
 * Message status enumeration
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
enum eMessageStatus: int
{
    // ── Methods ────────────────────────────────────────────────────────────

    /**
     * Returns the translated label for this status.
     *
     * @return string translated label
     */
    public function getLabel(): string
    {
        return cTranslator::translate(match ($this) {
            self::DRAFT     => 'message_status.draft',
            self::PUBLISHED => 'message_status.published',
            self::DELETED   => 'message_status.deleted',
        });
    }

    /**
     * Checks whether the status allows public visibility.
     *
     * @return bool true only for PUBLISHED
     */
    public function isPubliclyVisible(): bool
    {
        return $this === self::PUBLISHED;
    }

    /**
     * Checks whether the status is DRAFT.
     *
     * @return bool true if DRAFT
     */
    public function isDraft(): bool
    {
        return $this === self::DRAFT;
    }

    /**
     * Checks whether the status is DELETED.
     *
     * @return bool true if DELETED
     */
    public function isDeleted(): bool
    {
        return $this === self::DELETED;
    }
    // ── Cases ──────────────────────────────────────────────────────────────

    /** Draft — only visible to the author. */
    case DRAFT = 0;

    /** Published — publicly visible (default state). */
    case PUBLISHED = 1;

    /** Deleted — soft-delete; message is hidden from regular views. */
    case DELETED = 2;
}
