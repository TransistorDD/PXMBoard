<?php

namespace PXMBoard\Model;

use PXMBoard\Database\cDB;

/**
 * user configuration handling
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cUserConfig extends cUserPermissions
{
    protected bool $m_bIsVisible = true;					// user visible? (online list)
    protected int $m_iSkinId = 0;						    // skin id
    protected string $m_sThreadListSortMode = '';			// sort mode for threadlist
    protected int $m_iTimeOffset = 0;					    // timeoffset
    protected bool $m_bEmbedExternal = false;				// externe Inhalte einbetten (Bilder, YouTube, Twitch)
    protected bool $m_bPrivateMessageNotification = false;	// send private message notification

    /**
     * initalize the member variables with the resultset from the db
     *
     * @param object $objResultRow resultrow from db query
     * @return bool success / failure
     */
    protected function _setDataFromDb(object $objResultRow): bool
    {
        cUserPermissions::_setDataFromDb($objResultRow);

        $this->m_bIsVisible	= $objResultRow->u_visible ? true : false;
        $this->m_iSkinId = (int) $objResultRow->u_skinid;
        $this->m_sThreadListSortMode = $objResultRow->u_threadlistsort;
        $this->m_iTimeOffset = (int) $objResultRow->u_timeoffset;
        $this->m_bEmbedExternal = $objResultRow->u_embed_external ? true : false;
        $this->m_bPrivateMessageNotification = $objResultRow->u_privatenotification ? true : false;

        return true;
    }

    /**
     * update data in database
     *
     * @return bool success / failure
     */
    public function updateData(): bool
    {
        $bReturn = false;

        if (cDB::getInstance()->executeQuery('UPDATE pxm_user SET u_visible='.intval($this->m_bIsVisible).','.
                                                    'u_skinid='.$this->m_iSkinId.','.
                                                    'u_threadlistsort='.cDB::getInstance()->quote($this->m_sThreadListSortMode).','.
                                                    'u_timeoffset='.$this->m_iTimeOffset.','.
                                                    'u_embed_external='.intval($this->m_bEmbedExternal).','.
                                                    'u_privatemail='.cDB::getInstance()->quote($this->m_sPrivateMail).','.
                                                    'u_privatenotification='.intval($this->m_bPrivateMessageNotification).' '.
                                'WHERE u_id='.$this->m_iId)) {
            $bReturn = true;
        }

        return $bReturn;
    }

    /**
     * get additional database attributes for this object (template method)
     *
     * @return string additional database attributes for this object
     */
    protected function _getDbAttributes(): string
    {
        return cUserPermissions::_getDbAttributes()
                .',u_visible,u_skinid,u_threadlistsort,'
                .'u_timeoffset,u_embed_external,u_privatenotification';
    }

    /**
     * is visible in online list?
     *
     * @return bool visible / invisible
     */
    public function isVisible(): bool
    {
        return $this->m_bIsVisible;
    }

    /**
     * set the visibility of the user in the onlinelist
     *
     * @param bool $bVisible visible / invisible
     * @return void
     */
    public function setIsVisible(bool $bVisible): void
    {
        $this->m_bIsVisible = $bVisible;
    }

    /**
     * get skin id
     *
     * @return int skin id
     */
    public function getSkinId(): int
    {
        return $this->m_iSkinId;
    }

    /**
     * set skin id
     *
     * @param int $iSkinId skin id
     * @return void
     */
    public function setSkinId(int $iSkinId): void
    {
        $this->m_iSkinId = $iSkinId;
    }

    /**
     * get sort mode for threadlist
     *
     * @return string sort mode for threadlist
     */
    public function getThreadListSortMode(): string
    {
        return $this->m_sThreadListSortMode;
    }

    /**
     * set sort mode for threadlist
     *
     * @param string $sThreadListSortMode sort mode for threadlist
     * @return void
     */
    public function setThreadListSortMode(string $sThreadListSortMode): void
    {
        $this->m_sThreadListSortMode = $sThreadListSortMode;
    }

    /**
     * get time offset
     *
     * @return int time offset
     */
    public function getTimeOffset(): int
    {
        return $this->m_iTimeOffset;
    }

    /**
     * set time offset
     *
     * @param int $iTimeOffset time offset
     * @return void
     */
    public function setTimeOffset(int $iTimeOffset): void
    {
        if (($iTimeOffset < 13) && ($iTimeOffset > -13)) {
            $this->m_iTimeOffset = $iTimeOffset;
        }
    }

    /**
     * Externe Inhalte einbetten? (Bilder, YouTube, Twitch)
     *
     * @return bool externe Inhalte einbetten?
     */
    public function embedExternal(): bool
    {
        return $this->m_bEmbedExternal;
    }

    /**
     * Externe Inhalte einbetten setzen
     *
     * @param bool $bEmbedExternal externe Inhalte einbetten?
     * @return void
     */
    public function setEmbedExternal(bool $bEmbedExternal): void
    {
        $this->m_bEmbedExternal = $bEmbedExternal;
    }

    /**
     * send private message notification?
     *
     * @return bool send a notification?
     */
    public function sendPrivateMessageNotification(): bool
    {
        return $this->m_bPrivateMessageNotification;
    }

    /**
     * set send private message notification
     *
     * @param bool $bPrivateMessageNotification send a notification?
     * @return void
     */
    public function setSendPrivateMessageNotification(bool $bPrivateMessageNotification): void
    {
        $this->m_bPrivateMessageNotification = $bPrivateMessageNotification;
    }

    /**
     * get membervariables as array
     *
     * @param int $iTimeOffs time offset in seconds
     * @param string $sDateFormat php date format
     * @return array<string, mixed> member variables
     */
    public function getDataArray(int $iTimeOffs = 0, string $sDateFormat = '', $objParser = null): array
    {
        // TODO: bessere Lösung für die übergabe von $iTimeOffset, $sDateFormat und $objParser finden bei Vererbung von cUserProfile
        return ['id'				=>	$this->m_iId,
                'username'			=>	$this->m_sUserName,
                'visible'			=>	$this->m_bIsVisible,
                'skin'				=>	$this->m_iSkinId,
                'sort'				=>	$this->m_sThreadListSortMode,
                'toff'				=>	$this->m_iTimeOffset,
                'embed_external'	=>	$this->m_bEmbedExternal,
                'privatemail'		=>	$this->m_sPrivateMail,
                'privnotification'	=>	$this->m_bPrivateMessageNotification];
    }
}
