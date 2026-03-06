<?php

namespace PXMBoard\Model;

use PXMBoard\Database\cDB;
use PXMBoard\Enum\eUserStatus;

/**
 * user statistics
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cUserStatistics
{
    /**
     * get the amount of registered users
     *
     * @return int amount of registered users
     */
    public function getMemberCount(): int
    {
        if ($objResultSet = cDB::getInstance()->executeQuery('SELECT count(*) AS users FROM pxm_user')) {
            if ($objResultRow = $objResultSet->getNextResultRowObject()) {
                return $objResultRow->users;
            }
        }
        return 0;
    }

    /**
     * get the newest member of the board
     *
     * @return cUser|null newest member of the board
     */
    public function getNewestMember(): ?cUser
    {
        $arrTmp = $this->_getMembersByAttribute('u_registrationtstmp', 'DESC', 1);
        if (sizeof($arrTmp) > 0) {
            return $arrTmp[0];
        }
        return null;
    }

    /**
     * get the newest members of the board
     *
     * @return list<cUser> newest members of the board
     */
    public function getNewestMembers(): array
    {
        return $this->_getMembersByAttribute('u_registrationtstmp', 'DESC', 10);
    }

    /**
     * get the oldest members of the board
     *
     * @return list<cUser> oldest members of the board
     */
    public function getOldestMembers(): array
    {
        return $this->_getMembersByAttribute('u_registrationtstmp', 'ASC', 10);
    }

    /**
     * get the most active users (most posts)
     *
     * @return list<cUser> most active users (most posts)
     */
    public function getMostActiveUsers(): array
    {
        return $this->_getMembersByAttribute('u_msgquantity', 'DESC', 10);
    }

    /**
     * get the least active users (most posts)
     *
     * @return list<cUser> least active users (least posts)
     */
    public function getLeastActiveUsers(): array
    {
        return $this->_getMembersByAttribute('u_msgquantity', 'ASC', 10);
    }

    /**
     * get board members selected by a passed attribute
     *
     * @param string $sAttribute db attribute
     * @param string $sOrder order by (asc|desc)
     * @param int $iLimit limit the result to x rows
     * @return list<cUser> user objects
     */
    private function _getMembersByAttribute(string $sAttribute, string $sOrder = 'ASC', int $iLimit = 1): array
    {
        $arrUsers = [];

        if ($objResultSet = cDB::getInstance()->executeQuery("SELECT u_id,u_username,u_city,u_publicmail,u_privatemail,u_registrationtstmp,u_msgquantity,u_highlight,u_highlight,u_status FROM pxm_user WHERE u_status='1' ORDER BY $sAttribute $sOrder", $iLimit)) {
            $objUser = new cUser();
            while ($objResultRow = $objResultSet->getNextResultRowObject()) {
                $objUser->setId($objResultRow->u_id);
                $objUser->setUserName($objResultRow->u_username);
                $objUser->setCity($objResultRow->u_city);
                $objUser->setPublicMail($objResultRow->u_publicmail);
                $objUser->setRegistrationTimestamp($objResultRow->u_registrationtstmp);
                $objUser->setMessageQuantity($objResultRow->u_msgquantity);
                $objUser->setHighlightUser($objResultRow->u_highlight);
                $objUser->setStatus(eUserStatus::from((int) $objResultRow->u_status));

                $arrUsers[] = $objUser;
            }
            $objResultSet->freeResult();
        }
        return $arrUsers;
    }
}
