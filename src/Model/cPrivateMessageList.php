<?php

require_once(SRCDIR . '/Model/cScrollList.php');
require_once(SRCDIR . '/Enum/ePrivateMessageStatus.php');
/**
 * private message list handling (abstract class)
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cPrivateMessageList extends cScrollList
{
    protected int $m_iUserId;				// user id
    protected string $m_sDateFormat;		// date format
    protected int $m_iTimeOffset;			// time offset

    /**
     * Constructor
     *
     * @param int $iUserId user id
     * @param int $iTimeOffset time offset
     * @param string $sDateFormat date format
     * @return void
     */
    public function __construct(int $iUserId, int $iTimeOffset = 0, string $sDateFormat = '')
    {
        parent::__construct();

        $this->m_iUserId = $iUserId;
        $this->m_iTimeOffset = $iTimeOffset;
        $this->m_sDateFormat = $sDateFormat;
    }
}
