<?php

/**
 * German translations for PXMBoard
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
return [
    // Error messages
    'error.invalid_mode'              => 'Ungültiger Modus',
    'error.username_unknown'          => 'Nutzername unbekannt',
    'error.invalid_password'          => 'Passwort ungültig',
    'error.could_not_create_session'  => 'Konnte Session nicht anlegen',
    'error.board_id_missing'          => 'Board-ID fehlt',
    'error.invalid_board_id'          => 'Board-ID ungültig',
    'error.invalid_message_id'        => 'Nachrichten-ID ungültig',
    'error.subject_missing'           => 'Betreff fehlt',
    'error.could_not_insert_data'     => 'Konnte Daten nicht einfügen',
    'error.could_not_update_data'     => 'Konnte Daten nicht aktualisieren',
    'error.thread_closed'             => 'Dieser Thread ist geschlossen',
    'error.invalid_thread_id'         => 'Thread-ID ungültig',
    'error.already_logged_in'         => 'Sie sind bereits eingeloggt',
    'error.not_authorized'            => 'Sie sind nicht dazu berechtigt',
    'error.could_not_delete_data'     => 'Konnte Daten nicht löschen',
    'error.message_already_exists'    => 'Diese Nachricht ist bereits vorhanden',
    'error.image_upload_error'        => 'Fehler beim Upload des Bildes',
    'error.could_not_send_email'      => 'Konnte E-Mail nicht verschicken',
    'error.message_has_reply'         => 'Auf diese Nachricht wurde bereits geantwortet',
    'error.board_closed'              => 'Dieses Board ist geschlossen',
    'error.board_readonly'            => 'Dieses Board ist im Nur-Lesen-Modus',
    'error.result_set_too_large'      => 'Ergebnismenge zu groß - bitte schränken Sie die Suche ein',
    'error.rate_limit_exceeded'       => 'Zu viele Suchanfragen - bitte versuchen Sie es in einer Minute erneut',
    'error.invalid_user_id'           => 'Benutzer-ID ungültig',
    'error.invalid_email'             => 'E-Mail-Adresse ungültig',
    'error.not_logged_in'             => 'Sie sind nicht angemeldet',
    'error.could_not_update_password' => 'Konnte Passwort nicht übernehmen',
    'error.data_mismatch'             => 'Ihre Daten stimmen nicht mit den gespeicherten überein',
    'error.username_already_exists'   => 'Dieser Nutzername ist bereits vergeben',
    'error.username_required'         => 'Bitte geben Sie Ihren Nutzernamen ein',
    'error.cannot_move_to_self'       => 'Nachricht kann nicht zu sich selbst verschoben werden',
    'error.cannot_move_to_subtree'    => 'Nachricht kann nicht in einen ihrer eigenen Unterbäume verschoben werden (Zirkelreferenz)',
    'error.cannot_move_across_boards' => 'Nachrichten können nur innerhalb desselben Boards verschoben werden',
    'error.message_move_error'        => 'Fehler beim Verschieben der Nachricht',
    'error.csrf_token_invalid'        => 'Ungültiges oder fehlendes Sicherheitstoken',

    // Success messages
    'success.message_deleted'                => 'Nachricht wurde erfolgreich gelöscht',
    'success.message_tree_deleted'           => 'Subthread erfolgreich gelöscht',
    'success.message_tree_moved'             => 'Subthread erfolgreich verschoben',
    'success.message_tree_extracted'         => 'Subthread erfolgreich ausgegliedert',
    'success.private_message_sent'           => 'Ihre private Nachricht wurde erfolgreich gesendet',
    'success.notification_marked_read'       => 'Benachrichtigung als gelesen markiert',
    'success.notification_enabled'           => 'E-Mail-Benachrichtigung aktiviert',
    'success.notification_disabled'          => 'E-Mail-Benachrichtigung deaktiviert',
    'success.all_notifications_read'         => 'Alle Benachrichtigungen wurden als gelesen markiert',
    'success.thread_fixed'                   => 'Thread fixiert',
    'success.thread_unfixed'                 => 'Thread nicht mehr fixiert',
    'success.thread_opened'                  => 'Thread geöffnet',
    'success.thread_closed'                  => 'Thread geschlossen',
    'success.thread_moved'                   => 'Thread verschoben',
    'success.user_activated'                 => 'Benutzer aktiviert',
    'success.user_deactivated'               => 'Benutzer deaktiviert',
    'success.device_logged_out'              => 'Das Gerät wurde ausgeloggt',
    'success.user_config_saved'              => 'Die Einstellungen wurden gespeichert',
    'success.user_profile_saved'             => 'Das Profil wurde gespeichert',
    'success.user_registered'                => 'Die Registrierung war erfolgreich. Du kannst dich jetzt anmelden.',
    'success.user_password_sent'             => 'Das neue Passwort wurde per E-Mail verschickt',
    'success.user_password_reset_requested'  => 'Die Anfrage wurde bearbeitet. Falls ein Konto mit diesen Daten existiert, wurden die Anmeldedaten per E-Mail verschickt.',
    'success.user_password_changed'          => 'Das Passwort wurde geändert',
    'success.board_status_changed'           => 'Board-Status wurde geändert',
    'success.draft_deleted'                  => 'Der Entwurf wurde gelöscht.',
    'success.message_updated'                => 'Ihre Nachricht wurde erfolgreich aktualisiert.',
    'success.message_saved'                  => 'Ihre Nachricht wurde erfolgreich gespeichert.',
    'success.draft_saved'                    => 'Ihr Entwurf wurde erfolgreich gespeichert.',

    // Notification texts
    'notification.reply_title'   => 'Neue Antwort auf einen Beitrag',
    'notification.reply_message' => '%username% hat auf "%subject%" geantwortet',
    'notification.mention_title'   => 'Du wurdest erwähnt',
    'notification.mention_message' => '%username% hat dich in einem Beitrag erwähnt',
    'notification.private_message_title'   => 'Neue private Nachricht',
    'notification.private_message_message' => '%username% hat dir eine PM gesendet: "%subject%"',

    // Board status labels
    'board_status.public'           => 'Öffentlich',
    'board_status.members_only'     => 'Nur Mitglieder',
    'board_status.readonly_public'  => 'Nur Lesen (Öffentlich)',
    'board_status.readonly_members' => 'Nur Lesen (Mitglieder)',
    'board_status.closed'           => 'Geschlossen',

    // Message status labels
    'message_status.draft'     => 'Entwurf',
    'message_status.published' => 'Veröffentlicht',
    'message_status.deleted'   => 'Gelöscht',

    // User status labels
    'user_status.active'        => 'Aktiv',
    'user_status.not_activated' => 'Nicht aktiviert',
    'user_status.disabled'      => 'Gesperrt',
];
