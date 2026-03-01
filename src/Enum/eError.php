<?php
/**
 * Error message enumeration
 *
 * @author Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright Torsten Rentsch 2001 - 2026
 */
enum eError: string {
    case INVALID_MODE = 'ungültiger modus';
    case USERNAME_UNKNOWN = 'nutzername unbekannt';
    case INVALID_PASSWORD = 'passwort ungültig';
    case COULD_NOT_CREATE_SESSION = 'konnte session nicht anlegen';
    case BOARD_ID_MISSING = 'board id fehlt';
    case INVALID_BOARD_ID = 'board id ungültig';
    case INVALID_MESSAGE_ID = 'message id ungültig';
    case SUBJECT_MISSING = 'subject fehlt';
    case COULD_NOT_INSERT_DATA = 'konnte daten nicht einfügen';
    case COULD_NOT_UPDATE_DATA = 'konnte daten nicht aktualisieren';
    case THREAD_CLOSED = 'dieser thread ist geschlossen';
    case INVALID_THREAD_ID = 'thread id ungültig';
    case ALREADY_LOGGED_IN = 'sie sind bereits eingeloggt';
    case NOT_AUTHORIZED = 'sie sind nicht dazu berechtigt';
    case COULD_NOT_DELETE_DATA = 'konnte daten nicht löschen';
    case MESSAGE_ALREADY_EXISTS = 'diese nachricht ist bereits vorhanden';
    case IMAGE_UPLOAD_ERROR = 'fehler beim upload des bildes';
    case COULD_NOT_SEND_EMAIL = 'konnte email nicht verschicken';
    case MESSAGE_HAS_REPLY = 'auf diese nachricht wurde bereits geantwortet';
    case BOARD_CLOSED = 'dieses board ist geschlossen';
    case BOARD_READONLY = 'dieses board ist im nur-lesen-modus';
    case RESULT_SET_TOO_LARGE = 'ergebnismenge zu groß bitte schränken sie die suche ein';
    case RATE_LIMIT_EXCEEDED = 'zu viele suchanfragen bitte versuchen sie es in einer minute erneut';
    case INVALID_USER_ID = 'user id ungültig';
    case INVALID_EMAIL = 'email ungültig';
    case NOT_LOGGED_IN = 'sie sind nicht angemeldet';
    case COULD_NOT_UPDATE_PASSWORD = 'konnte passwort nicht übernehmen';
    case DATA_MISMATCH = 'ihre daten stimmen nicht mit den gespeicherten überein';
    case USERNAME_ALREADY_EXISTS = 'dieser nutzername ist bereits vergeben';
    case USERNAME_REQUIRED = 'bitte geben sie ihren nutzername ein';

    // Spezifische Fehler für Nachrichtenbewegung (vorher Custom-Messages)
    case CANNOT_MOVE_TO_SELF = 'Nachricht kann nicht zu sich selbst verschoben werden.';
    case CANNOT_MOVE_TO_SUBTREE = 'Nachricht kann nicht in einen ihrer eigenen Unterbäume verschoben werden (Zirkelreferenz).';
    case CANNOT_MOVE_ACROSS_BOARDS = 'Nachrichten können nur innerhalb desselben Boards verschoben werden.';
    case MESSAGE_MOVE_ERROR = 'Fehler beim Verschieben der Nachricht.';
}
?>
