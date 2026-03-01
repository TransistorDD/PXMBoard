<?php
/**
 * User-related enums
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */

/**
 * User status enumeration
 */
enum UserStatus: int {
	case ACTIVE = 1;
	case NOT_ACTIVATED = 2;
	case DISABLED = 3;

	/**
	 * Get human-readable label for the status
	 *
	 * @return string
	 */
	public function getLabel(): string {
		return match($this) {
			self::ACTIVE => 'active',
			self::NOT_ACTIVATED => 'not activated',
			self::DISABLED => 'disabled',
		};
	}

	/**
	 * Get all user states as array (for backward compatibility)
	 *
	 * @return array user states (key: id; value: name)
	 */
	public static function getAll(): array {
		return [
			self::ACTIVE->value => self::ACTIVE->getLabel(),
			self::NOT_ACTIVATED->value => self::NOT_ACTIVATED->getLabel(),
			self::DISABLED->value => self::DISABLED->getLabel(),
		];
	}
}
?>
