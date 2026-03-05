<?php

namespace PXMBoard\Controller\Ajax;

use PXMBoard\Database\cDBFactory;
use PXMBoard\Enum\eUserStatus;

/**
 * AJAX endpoint for user autocomplete (mention feature)
 *
 * Returns max 5 matching users based on username search query.
 * Requires minimum 2 characters. Only active users returned.
 *
 * @author Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright Torsten Rentsch 2001 - 2026
 */
class cAjaxActionUserautocomplete extends cAjaxAction
{
    /**
     * Validate base permissions - publicly accessible
     *
     * @return bool true (no restrictions)
     */
    public function validateBasePermissionsAndConditions(): bool
    {
        return true; // Public endpoint
    }

    /**
     * Perform user search for autocomplete
     *
     * @return void
     */
    public function performAction(): void
    {
        // Get and validate query parameter
        $sQuery = $this->m_objInputHandler->getStringFormVar('q', 'searchstring', true, true, 'trim');

        // Minimum 2 characters required
        if (strlen($sQuery) < 2) {
            $this->_setJsonResponse(['results' => []], 200);
            return;
        }

        // Search for matching active users
        $objDb = cDBFactory::getInstance();

        // Escape LIKE wildcards and special characters
        $sQueryEscaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $sQuery);

        $sSql = 'SELECT u_id, u_username
		         FROM pxm_user
		         WHERE u_username LIKE '.$objDb->quote($sQueryEscaped.'%').'
		           AND u_status = '.eUserStatus::ACTIVE->value;

        // Exclude self-mentions when logged in
        $objActiveUser = $this->getActiveUser();
        if ($objActiveUser) {
            $sSql .= ' AND u_id != '.intval($objActiveUser->getId());
        }

        $sSql .= ' ORDER BY u_username ASC
		           LIMIT 5';

        $objResultSet = $objDb->executeQuery($sSql);

        $arrResults = [];
        while ($objRow = $objResultSet->getNextResultRowObject()) {
            $arrResults[] = [
                'id' => (int) $objRow->u_id,
                'label' => $objRow->u_username
            ];
        }

        $objResultSet->freeResult();

        // Return JSON response
        $this->_setJsonResponse(['results' => $arrResults], 200);
    }
}
