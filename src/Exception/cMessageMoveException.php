<?php
/**
 * Base exception for message move operations
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cMessageMoveException extends Exception {
}

/**
 * Exception thrown when attempting to create a circular reference
 *
 * @author Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright Torsten Rentsch 2001 - 2026
 */
class cCircularReferenceException extends cMessageMoveException {
}

/**
 * Exception thrown when attempting to move message to itself
 *
 * @author Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright Torsten Rentsch 2001 - 2026
 */
class cSelfReferenceException extends cMessageMoveException {
}

/**
 * Exception thrown when attempting to move between different boards
 *
 * @author Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright Torsten Rentsch 2001 - 2026
 */
class cInvalidBoardException extends cMessageMoveException {
}

/**
 * Exception thrown when target parent message is invalid
 *
 * @author Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright Torsten Rentsch 2001 - 2026
 */
class cInvalidParentException extends cMessageMoveException {
}
?>
