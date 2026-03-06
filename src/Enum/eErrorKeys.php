<?php

namespace PXMBoard\Enum;

use PXMBoard\I18n\cTranslator;

/**
 * Error key enumeration (dot-notation i18n keys)
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
enum eErrorKeys: string
{
    // ── Methods ────────────────────────────────────────────────────────────

    /**
     * Returns the translated error message for this key.
     *
     * @param array<string, mixed> $params optional placeholder replacements
     * @return string translated string
     */
    public function t(array $params = []): string
    {
        return cTranslator::translate($this->value, $params);
    }
    // ── Cases ──────────────────────────────────────────────────────────────

    /** Invalid board mode supplied. */
    case INVALID_MODE = 'error.invalid_mode';

    /** Username not found in the database. */
    case USERNAME_UNKNOWN = 'error.username_unknown';

    /** Password does not match the stored hash. */
    case INVALID_PASSWORD = 'error.invalid_password';

    /** Session could not be created. */
    case COULD_NOT_CREATE_SESSION = 'error.could_not_create_session';

    /** Board ID was not supplied in the request. */
    case BOARD_ID_MISSING = 'error.board_id_missing';

    /** Board ID is not a valid integer or does not exist. */
    case INVALID_BOARD_ID = 'error.invalid_board_id';

    /** Message ID is not a valid integer or does not exist. */
    case INVALID_MESSAGE_ID = 'error.invalid_message_id';

    /** Message subject was not supplied. */
    case SUBJECT_MISSING = 'error.subject_missing';

    /** INSERT query failed. */
    case COULD_NOT_INSERT_DATA = 'error.could_not_insert_data';

    /** UPDATE query failed. */
    case COULD_NOT_UPDATE_DATA = 'error.could_not_update_data';

    /** Thread is closed and does not accept new replies. */
    case THREAD_CLOSED = 'error.thread_closed';

    /** Thread ID is not a valid integer or does not exist. */
    case INVALID_THREAD_ID = 'error.invalid_thread_id';

    /** User is already logged in. */
    case ALREADY_LOGGED_IN = 'error.already_logged_in';

    /** User lacks the required permission for this operation. */
    case NOT_AUTHORIZED = 'error.not_authorized';

    /** DELETE query failed. */
    case COULD_NOT_DELETE_DATA = 'error.could_not_delete_data';

    /** A message with the same content already exists. */
    case MESSAGE_ALREADY_EXISTS = 'error.message_already_exists';

    /** Image upload failed. */
    case IMAGE_UPLOAD_ERROR = 'error.image_upload_error';

    /** Sending the e-mail failed. */
    case COULD_NOT_SEND_EMAIL = 'error.could_not_send_email';

    /** Message already has at least one reply. */
    case MESSAGE_HAS_REPLY = 'error.message_has_reply';

    /** Board is closed (only moderators/admins may access). */
    case BOARD_CLOSED = 'error.board_closed';

    /** Board is in read-only mode. */
    case BOARD_READONLY = 'error.board_readonly';

    /** Search result set exceeds the configured maximum. */
    case RESULT_SET_TOO_LARGE = 'error.result_set_too_large';

    /** Too many search requests in a short period. */
    case RATE_LIMIT_EXCEEDED = 'error.rate_limit_exceeded';

    /** User ID is not a valid integer or does not exist. */
    case INVALID_USER_ID = 'error.invalid_user_id';

    /** E-mail address is syntactically invalid. */
    case INVALID_EMAIL = 'error.invalid_email';

    /** Operation requires an authenticated user. */
    case NOT_LOGGED_IN = 'error.not_logged_in';

    /** Password could not be updated. */
    case COULD_NOT_UPDATE_PASSWORD = 'error.could_not_update_password';

    /** Supplied data does not match the stored data. */
    case DATA_MISMATCH = 'error.data_mismatch';

    /** The requested username is already taken. */
    case USERNAME_ALREADY_EXISTS = 'error.username_already_exists';

    /** Username field was left empty. */
    case USERNAME_REQUIRED = 'error.username_required';

    /** Move target is the message itself (circular reference). */
    case CANNOT_MOVE_TO_SELF = 'error.cannot_move_to_self';

    /** Move target is a descendant of the message (circular reference). */
    case CANNOT_MOVE_TO_SUBTREE = 'error.cannot_move_to_subtree';

    /** Source and target message belong to different boards. */
    case CANNOT_MOVE_ACROSS_BOARDS = 'error.cannot_move_across_boards';

    /** Generic error while moving a message tree. */
    case MESSAGE_MOVE_ERROR = 'error.message_move_error';

    /** CSRF token is missing or does not match the session token. */
    case CSRF_TOKEN_INVALID = 'error.csrf_token_invalid';
}
