<?php

/**
 * Success message enumeration
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
enum eSuccessMessage: string
{
    // Message operations
    case MESSAGE_DELETED = 'Nachricht wurde erfolgreich gelöscht';
    case MESSAGE_TREE_DELETED = 'Subthread erfolgreich gelöscht';
    case MESSAGE_TREE_MOVED = 'Subthread erfolgreich verschoben';
    case MESSAGE_TREE_EXTRACTED = 'Subthread erfolgreich ausgegliedert';
    case PRIVATE_MESSAGE_SENT = 'Ihre private Nachricht wurde erfolgreich gesendet.';

    // Notification operations
    case NOTIFICATION_MARKED_READ = 'Benachrichtigung als gelesen markiert';
    case NOTIFICATION_ENABLED = 'E-Mail-Benachrichtigung aktiviert';
    case NOTIFICATION_DISABLED = 'E-Mail-Benachrichtigung deaktiviert';
    case ALL_NOTIFICATIONS_READ = 'Alle Benachrichtigungen wurden als gelesen markiert.';

    // Thread operations
    case THREAD_FIXED = 'Thread fixiert';
    case THREAD_UNFIXED = 'Thread nicht mehr fixiert';
    case THREAD_OPENED = 'Thread geöffnet';
    case THREAD_CLOSED = 'Thread geschlossen';
    case THREAD_MOVED = 'Thread verschoben';

    // User operations
    case USER_ACTIVATED = 'Benutzer aktiviert';
    case USER_DEACTIVATED = 'Benutzer deaktiviert';
    case DEVICE_LOGGED_OUT = 'Das Gerät wurde ausgeloggt.';
    case USER_CONFIG_SAVED = 'Die Einstellungen wurden gespeichert.';
    case USER_PROFILE_SAVED = 'Das Profil wurde gespeichert.';
    case USER_REGISTERED = 'Die Registrierung war erfolgreich. Du kannst dich jetzt anmelden.';
    case USER_PASSWORD_SENT = 'Das neue Passwort wurde per E-Mail verschickt.';
    case USER_PASSWORD_RESET_REQUESTED = 'Die Anfrage wurde bearbeitet. Falls ein Konto mit diesen Daten existiert, wurden die Anmeldedaten per E-Mail verschickt.';
    case USER_PASSWORD_CHANGED = 'Das Passwort wurde geändert.';

    // Board operations
    case BOARD_STATUS_CHANGED = 'Board-Status wurde geändert';
}
