<?php

namespace PXMBoard\Model;

use PXMBoard\Database\cDB;

/**
 * User login ticket (for multi-device persistent login)
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cUserLoginTicket
{
    protected int $m_iId = 0;
    protected int $m_iUserId = 0;
    protected string $m_sToken = '';
    protected string $m_sUserAgent = '';
    protected string $m_sIpAddress = '';
    protected int $m_iCreatedTimestamp = 0;
    protected int $m_iLastUsedTimestamp = 0;

    /**
     * Create a new login ticket for user
     *
     * @param int $iUserId User ID
     * @param string $sUserAgent Browser User-Agent
     * @param string $sIpAddress IP address
     * @return string Token (32 chars hex, empty on failure)
     */
    public static function createTicket(int $iUserId, string $sUserAgent = '', string $sIpAddress = ''): string
    {
        $objDb = cDB::getInstance();
        $iTimestamp = time();

        if ($iUserId <= 0) {
            return '';
        }

        // Generate cryptographically secure token (32 characters hex)
        $sToken = bin2hex(random_bytes(16));

        $sQuery = 'INSERT INTO pxm_user_login_ticket '.
                  '(ult_userid, ult_token, ult_useragent, ult_ipaddress, ult_created_timestamp, ult_last_used_timestamp) '.
                  'VALUES ('.
                  $iUserId.','.
                  $objDb->quote($sToken).','.
                  $objDb->quote(substr($sUserAgent, 0, 255)).','.
                  $objDb->quote($sIpAddress).','.
                  $iTimestamp.','.
                  $iTimestamp.
                  ')';

        if ($objDb->executeQuery($sQuery)) {
            return $sToken;
        }
        return '';
    }

    /**
     * Validate ticket and update last_used timestamp
     *
     * @param string $sToken Login token
     * @return int User-ID (0 on failure)
     */
    public static function validateTicket(string $sToken): int
    {
        $objDb = cDB::getInstance();

        if (empty($sToken)) {
            return 0;
        }

        // Load ticket
        $sQuery = 'SELECT ult_userid FROM pxm_user_login_ticket '.
                  'WHERE ult_token='.$objDb->quote($sToken);

        $iUserId = 0;
        if ($objResultSet = $objDb->executeQuery($sQuery, 1)) {
            if ($objResultRow = $objResultSet->getNextResultRowObject()) {
                $iUserId = (int) $objResultRow->ult_userid;

                // Update last_used timestamp
                $iTimestamp = time();
                $sUpdateQuery = 'UPDATE pxm_user_login_ticket '.
                                'SET ult_last_used_timestamp='.$iTimestamp.' '.
                                'WHERE ult_token='.$objDb->quote($sToken);
                $objDb->executeQuery($sUpdateQuery);
            }
            $objResultSet->freeResult();
        }
        return $iUserId;
    }

    /**
     * Load ticket by token
     *
     * @param string $sToken Login token
     * @return bool Success / Failure
     */
    public function loadDataByToken(string $sToken): bool
    {
        $objDb = cDB::getInstance();

        if (empty($sToken)) {
            return false;
        }

        $sQuery = 'SELECT ult_id, ult_userid, ult_token, ult_useragent, ult_ipaddress, '.
                  'ult_created_timestamp, ult_last_used_timestamp '.
                  'FROM pxm_user_login_ticket '.
                  'WHERE ult_token='.$objDb->quote($sToken);

        if ($objResultSet = $objDb->executeQuery($sQuery, 1)) {
            if ($objResultRow = $objResultSet->getNextResultRowObject()) {
                $this->_setDataFromDb($objResultRow);
                $objResultSet->freeResult();
                return true;
            }
            $objResultSet->freeResult();
        }
        return false;
    }

    /**
     * Load ticket by ID
     *
     * @param int $iId Ticket ID
     * @return bool Success / Failure
     */
    public function loadDataById(int $iId): bool
    {
        $objDb = cDB::getInstance();

        if ($iId <= 0) {
            return false;
        }

        $sQuery = 'SELECT ult_id, ult_userid, ult_token, ult_useragent, ult_ipaddress, '.
                  'ult_created_timestamp, ult_last_used_timestamp '.
                  'FROM pxm_user_login_ticket '.
                  'WHERE ult_id='.$iId;

        if ($objResultSet = $objDb->executeQuery($sQuery, 1)) {
            if ($objResultRow = $objResultSet->getNextResultRowObject()) {
                $this->_setDataFromDb($objResultRow);
                $objResultSet->freeResult();
                return true;
            }
            $objResultSet->freeResult();
        }
        return false;
    }

    /**
     * Set data from database row
     *
     * @param object $objResultRow Database result row
     * @return void
     */
    protected function _setDataFromDb(object $objResultRow): void
    {
        $this->m_iId = (int) $objResultRow->ult_id;
        $this->m_iUserId = (int) $objResultRow->ult_userid;
        $this->m_sToken = $objResultRow->ult_token;
        $this->m_sUserAgent = $objResultRow->ult_useragent;
        $this->m_sIpAddress = $objResultRow->ult_ipaddress;
        $this->m_iCreatedTimestamp = (int) $objResultRow->ult_created_timestamp;
        $this->m_iLastUsedTimestamp = (int) $objResultRow->ult_last_used_timestamp;
    }

    /**
     * Create ticket object from database result row
     *
     * @param object $objResultRow Database result row
     * @return cUserLoginTicket
     */
    public static function createFromDbRow(object $objResultRow): cUserLoginTicket
    {
        $objTicket = new cUserLoginTicket();
        $objTicket->_setDataFromDb($objResultRow);
        return $objTicket;
    }

    /**
     * Delete this ticket
     *
     * @return bool Success / Failure
     */
    public function deleteTicket(): bool
    {
        $objDb = cDB::getInstance();

        if ($this->m_iId <= 0) {
            return false;
        }

        $sQuery = 'DELETE FROM pxm_user_login_ticket '.
                  'WHERE ult_id='.$this->m_iId;

        if ($objDb->executeQuery($sQuery)) {
            return true;
        }
        return false;
    }

    // Getter methods
    public function getId(): int
    {
        return $this->m_iId;
    }

    public function getUserId(): int
    {
        return $this->m_iUserId;
    }

    public function getToken(): string
    {
        return $this->m_sToken;
    }

    public function getUserAgent(): string
    {
        return $this->m_sUserAgent;
    }

    public function getIpAddress(): string
    {
        return $this->m_sIpAddress;
    }

    public function getCreatedTimestamp(): int
    {
        return $this->m_iCreatedTimestamp;
    }

    public function getLastUsedTimestamp(): int
    {
        return $this->m_iLastUsedTimestamp;
    }

    /**
     * Get device info for display (Browser, OS)
     *
     * @return string Device info
     */
    public function getDeviceInfo(): string
    {
        if (empty($this->m_sUserAgent)) {
            return 'Unbekanntes Gerät';
        }

        $sUserAgent = $this->m_sUserAgent;
        $sOs = 'Unbekannt';
        $sBrowser = 'Unbekannt';

        // Detect OS
        if (preg_match('/Windows NT 10/', $sUserAgent)) {
            $sOs = 'Windows 10';
        } elseif (preg_match('/Windows NT 11/', $sUserAgent)) {
            $sOs = 'Windows 11';
        } elseif (preg_match('/Macintosh/', $sUserAgent)) {
            $sOs = 'macOS';
        } elseif (preg_match('/Linux/', $sUserAgent)) {
            $sOs = 'Linux';
        } elseif (preg_match('/Android/', $sUserAgent)) {
            $sOs = 'Android';
        } elseif (preg_match('/iPhone|iPad/', $sUserAgent)) {
            $sOs = 'iOS';
        }

        // Detect Browser
        if (preg_match('/Chrome/', $sUserAgent) && !preg_match('/Edg/', $sUserAgent)) {
            $sBrowser = 'Chrome';
        } elseif (preg_match('/Edg/', $sUserAgent)) {
            $sBrowser = 'Edge';
        } elseif (preg_match('/Firefox/', $sUserAgent)) {
            $sBrowser = 'Firefox';
        } elseif (preg_match('/Safari/', $sUserAgent) && !preg_match('/Chrome/', $sUserAgent)) {
            $sBrowser = 'Safari';
        }

        return $sBrowser . ' auf ' . $sOs;
    }

    /**
     * Get data as array for template
     *
     * @param int $iTimeOffset Time offset in seconds
     * @param string $sDateFormat PHP date format
     * @return array<string, mixed> Member variables as array
     */
    public function getDataArray(int $iTimeOffset, string $sDateFormat): array
    {
        return [
            'id'        => $this->m_iId,
            'userid'    => $this->m_iUserId,
            'token'     => $this->m_sToken,
            'useragent' => $this->m_sUserAgent,
            'ipaddress' => $this->m_sIpAddress,
            'deviceinfo' => $this->getDeviceInfo(),
            'created'   => date($sDateFormat, $this->m_iCreatedTimestamp + $iTimeOffset),
            'lastused'  => date($sDateFormat, $this->m_iLastUsedTimestamp + $iTimeOffset)
        ];
    }
}
