<?php

namespace PXMBoard\Enum;

use PXMBoard\I18n\cTranslator;

/**
 * Notification text key enumeration (dot-notation i18n keys)
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
enum eNotificationKeys: string
{
    // ── Methods ────────────────────────────────────────────────────────────

    /**
     * Returns the translated notification text for this key.
     *
     * @param array<string, mixed> $params optional placeholder replacements
     * @return string translated string
     */
    public function t(array $params = []): string
    {
        return cTranslator::translate($this->value, $params);
    }

    // ── Cases ──────────────────────────────────────────────────────────────

    /** Title for a reply-to-watched-message notification. */
    case REPLY_TITLE = 'notification.reply_title';

    /** Body for a reply notification; supports %username% and %subject% placeholders. */
    case REPLY_MESSAGE = 'notification.reply_message';

    /** Title for a mention notification. */
    case MENTION_TITLE = 'notification.mention_title';

    /** Body for a mention notification; supports %username% placeholder. */
    case MENTION_MESSAGE = 'notification.mention_message';

    /** Title for a private-message notification. */
    case PRIVATE_MESSAGE_TITLE = 'notification.private_message_title';

    /** Body for a private-message notification; supports %username% and %subject% placeholders. */
    case PRIVATE_MESSAGE_MESSAGE = 'notification.private_message_message';
}
