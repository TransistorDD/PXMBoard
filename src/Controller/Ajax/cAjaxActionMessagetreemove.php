<?php

namespace PXMBoard\Controller\Ajax;

use PXMBoard\Enum\eErrorKeys;
use PXMBoard\Enum\eSuccessKeys;
use PXMBoard\Exception\cCircularReferenceException;
use PXMBoard\Exception\cInvalidBoardException;
use PXMBoard\Exception\cInvalidParentException;
use PXMBoard\Exception\cMessageMoveException;
use PXMBoard\Exception\cSelfReferenceException;
use PXMBoard\Model\cBoardMessage;

/**
 * Ajax-Action: Move a message subtree to a different parent
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cAjaxActionMessagetreemove extends cAjaxAction
{
    /**
     * Validate base permissions - requires authentication, board, and moderator rights
     *
     * @return bool true if all permissions granted, false otherwise
     */
    public function validateBasePermissionsAndConditions(): bool
    {
        return $this->_requireValidCsrfToken()
            && $this->_requireAuthentication()
            && $this->_requireBoard()
            && $this->_requireModeratorPermission();
    }

    /**
     * Perform action - move a message subtree to a different parent via Ajax
     *
     * @return void
     */
    public function performAction(): void
    {
        $objActiveBoard = $this->getActiveBoard();
        $iBoardId = $objActiveBoard->getId();

        $iSourceMessageId = $this->m_objInputHandler->getIntFormVar('sourcemsgid', true, false, true);
        $iTargetMessageId = $this->m_objInputHandler->getIntFormVar('targetmsgid', true, false, true);

        if ($iSourceMessageId <= 0 || $iTargetMessageId <= 0) {
            $this->_setJsonError(eErrorKeys::INVALID_MESSAGE_ID, 400);
            return;
        }

        $objSourceMessage = new cBoardMessage();
        if (!$objSourceMessage->loadDataById($iSourceMessageId, $iBoardId)) {
            $this->_setJsonError(eErrorKeys::INVALID_MESSAGE_ID, 404);
            return;
        }

        try {
            if ($objSourceMessage->moveToParent($iTargetMessageId)) {
                $this->_setJsonSuccess(eSuccessKeys::MESSAGE_TREE_MOVED);
            } else {
                $this->_setJsonError(eErrorKeys::COULD_NOT_DELETE_DATA, 500);
            }
        } catch (cSelfReferenceException $e) {
            $this->_setJsonError(eErrorKeys::CANNOT_MOVE_TO_SELF, 422);
        } catch (cCircularReferenceException $e) {
            $this->_setJsonError(eErrorKeys::CANNOT_MOVE_TO_SUBTREE, 422);
        } catch (cInvalidBoardException $e) {
            $this->_setJsonError(eErrorKeys::CANNOT_MOVE_ACROSS_BOARDS, 422);
        } catch (cInvalidParentException $e) {
            $this->_setJsonError(eErrorKeys::INVALID_MESSAGE_ID, 400);
        } catch (cMessageMoveException $e) {
            $this->_setJsonError(eErrorKeys::MESSAGE_MOVE_ERROR, 500);
        }
    }
}
