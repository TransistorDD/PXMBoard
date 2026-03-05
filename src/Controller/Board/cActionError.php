<?php

namespace PXMBoard\Controller\Board;

use PXMBoard\Enum\eErrorKeys;

/**
 * display a invalid board mode errormessage
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cActionError extends cPublicAction
{
    /**
     * Validate permissions - error page is always accessible
     *
     * @return bool always true
     */
    public function validateBasePermissionsAndConditions(): bool
    {
        return true;
    }

    /**
     * perform the action
     *
     * @return void
     */
    public function performAction(): void
    {
        $this->m_objTemplate = $this->_getErrorTemplateObject(eErrorKeys::INVALID_MODE);	// invalid board mode
    }
}
