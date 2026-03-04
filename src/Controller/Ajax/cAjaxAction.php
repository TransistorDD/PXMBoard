<?php

require_once(SRCDIR . '/Controller/cBaseAction.php');
require_once(SRCDIR . '/Enum/eSuccessMessage.php');
/**
 * Base class for AJAX actions with JSON response
 *
 * IMPORTANT: Do not use exit()! The framework lifecycle must remain intact.
 * JSON response is returned via getOutput().
 *
 * @author Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright Torsten Rentsch 2001 - 2026
 */
abstract class cAjaxAction extends cBaseAction
{
    /** @var array<string, mixed> */
    protected array $m_arrJsonData = [];
    protected int $m_iHttpStatusCode = 200;

    /**
     * Handle permission error by setting a JSON error response.
     *
     * @param eError $error the error that caused the permission failure
     * @return void
     */
    protected function _handlePermissionError(eError $error): void
    {
        $this->_setJsonError($error, $this->_mapErrorToHttpStatus($error));
    }

    /**
     * Map an eError value to the appropriate HTTP status code for AJAX responses.
     *
     * @param eError $error error enum
     * @return int HTTP status code
     */
    protected function _mapErrorToHttpStatus(eError $error): int
    {
        return match ($error) {
            eError::NOT_LOGGED_IN                => 401,
            eError::NOT_AUTHORIZED,
            eError::BOARD_CLOSED,
            eError::BOARD_READONLY,
            eError::CSRF_TOKEN_INVALID           => 403,
            eError::INVALID_BOARD_ID,
            eError::BOARD_ID_MISSING,
            eError::INVALID_MESSAGE_ID,
            eError::INVALID_THREAD_ID,
            eError::INVALID_USER_ID,
            eError::ALREADY_LOGGED_IN            => 400,
            eError::COULD_NOT_INSERT_DATA,
            eError::COULD_NOT_UPDATE_DATA,
            eError::COULD_NOT_DELETE_DATA        => 500,
            default                              => 400,
        };
    }

    /**
     * Set JSON response data (instead of template)
     *
     * @param array<string, mixed> $arrData data for JSON response
     * @param int $iHttpCode HTTP status code (200, 400, 404, 500)
     */
    protected function _setJsonResponse(array $arrData, int $iHttpCode = 200): void
    {
        $this->m_arrJsonData = $arrData;
        $this->m_iHttpStatusCode = $iHttpCode;
    }

    /**
     * Set error response using error enum
     *
     * @param eError $eErrorCode error enum constant
     * @param int $iHttpCode HTTP status code (400, 401, 403, 404, 500)
     */
    protected function _setJsonError(eError $eErrorCode, int $iHttpCode = 400): void
    {
        $this->_setJsonResponse(['error' => $eErrorCode->value], $iHttpCode);
    }

    /**
     * Set success response using success message enum
     *
     * @param eSuccessMessage $eSuccessMessage success message enum constant
     * @param array<string, mixed> $arrAdditionalData optional additional data (e.g. count, isActive, etc.)
     * @param int $iHttpCode HTTP status code (default 200)
     */
    protected function _setJsonSuccess(eSuccessMessage $eSuccessMessage, array $arrAdditionalData = [], int $iHttpCode = 200): void
    {
        $arrResponse = array_merge(['success' => true, 'message' => $eSuccessMessage->value], $arrAdditionalData);
        $this->_setJsonResponse($arrResponse, $iHttpCode);
    }

    /**
     * Generate output - returns JSON
     *
     * @return string JSON response
     */
    public function getOutput(): string
    {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($this->m_iHttpStatusCode);
        return json_encode($this->m_arrJsonData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
