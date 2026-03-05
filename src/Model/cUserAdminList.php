<?php

require_once(SRCDIR . '/Model/cScrollList.php');
require_once(SRCDIR . '/Model/cUserProfile.php');
/**
 * user overview for admins
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cUserAdminList extends cScrollList
{
    protected int $m_iUserStateFilter;	// user state filter
    protected string $m_sSortAttribute;	// sort attribute
    protected string $m_sSortDirection;	// sort direction

    /**
     * Constructor
     *
     * @param int $iUserStateFilter user state filter
     * @param string $sSortAttribute sort attribute
     * @param string $sSortDirection sort direction
     * @return void
     */
    public function __construct(int $iUserStateFilter, string $sSortAttribute, string $sSortDirection)
    {
        parent::__construct();

        $this->m_iUserStateFilter = $iUserStateFilter;
        $this->m_sSortAttribute = $sSortAttribute;
        $this->m_sSortDirection = $sSortDirection;
    }

    /**
     * get the query
     *
     * @return string query
     */
    protected function _getQuery(): string
    {
        $sQuery = 'SELECT u_id,u_username,u_registrationtstmp,u_lastonlinetstmp,u_profilechangedtstmp,u_msgquantity,u_status FROM pxm_user';
        if (!empty($this->m_iUserStateFilter)) {
            $sQuery .= ' WHERE u_status='.$this->m_iUserStateFilter;
        }
        if (!empty($this->m_sSortAttribute)) {
            $sQuery .=  ' ORDER BY '.$this->m_sSortAttribute.' '.$this->m_sSortDirection;
        }
        return $sQuery;
    }

    /**
     * initalize the member variables with the resultrow from the db
     *
     * @param object $objResultRow resultrow from db query
     * @return bool success / failure
     */
    protected function _setDataFromDb(object $objResultRow): bool
    {
        $objUser = new cUserProfile();
        $objUser->setId($objResultRow->u_id);
        $objUser->setUserName($objResultRow->u_username);
        $objUser->setRegistrationTimestamp($objResultRow->u_registrationtstmp);
        $objUser->setLastOnlineTimestamp($objResultRow->u_lastonlinetstmp);
        $objUser->setLastUpdateTimestamp($objResultRow->u_profilechangedtstmp);
        $objUser->setMessageQuantity($objResultRow->u_msgquantity);
        $objUser->setStatus(eUserStatus::from((int)$objResultRow->u_status));

        $this->m_arrResultList[] = $objUser;
        return true;
    }
}
