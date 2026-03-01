<?php
require_once(SRCDIR . '/Model/cScrollList.php');
require_once(SRCDIR . '/Model/cMessageStates.php');
/**
 * private message list handling (abstract class)
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cPrivateMessageList extends cScrollList{

	protected int $m_iUserId;				// user id
	protected string $m_sDateFormat;			// date format
	protected int $m_iTimeOffset;			// time offset

	/**
	 * Constructor
	 *
	 * @param integer $iUserId user id
	 * @param integer $iTimeOffset time offset
	 * @param string $sDateFormat date format
	 * @return void
	 */
	public function __construct($iUserId,$iTimeOffset = 0,$sDateFormat = ""){

		$this->m_iUserId = intval($iUserId);
		$this->m_iTimeOffset = intval($iTimeOffset);
		$this->m_sDateFormat = $sDateFormat;

		parent::__construct();
	}
}
?>