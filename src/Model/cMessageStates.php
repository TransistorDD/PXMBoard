<?php
/**
 * Defines the available message states
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cMessageStates{
	/**
	 * Constructor
	 *
	 * @return void
	 */
	public function __construct(){
	}

	/**
	 * get the value of a new and unread message
	 *
	 * @return integer user active value
	 */
	public static function messageNew(){
		return 1;
	}

	/**
	 * get the value of a read message
	 *
	 * @return integer user not activated value
	 */
	public static function messageRead(){
		return 2;
	}

	/**
	 * get the value of a deleted message
	 *
	 * @return integer user deleted value
	 */
	public static function messageDeleted(){
		return 3;
	}

	/**
	 * get all available message states
	 *
	 * @return array message states (key: id; value: name)
	 */
	public static function getUserStates(){
		 return array(1=>"new",
			    	  2=>"read",
					  3=>"deleted");
	}
}
?>