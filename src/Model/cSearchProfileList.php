<?php

require_once(SRCDIR . '/Model/cSearchProfile.php');
/**
 * searchprofilelist handling
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cSearchProfileList
{
    /** @var array<cSearchProfile> */
    public array $m_arrSearchProfiles = [];			// SearchProfiles

    /**
     * get data from database
     *
     * @return bool success / failure
     */
    public function loadData(): bool
    {
        if ($objResultSet = cDBFactory::getInstance()->executeQuery('SELECT se_id,se_userid,se_message,se_username,se_days,se_tstmp FROM pxm_search ORDER BY se_tstmp DESC', 10)) {

            while ($objResultRow = $objResultSet->getNextResultRowObject()) {

                $objSearchProfile = new cSearchProfile();

                $objSearchProfile->setId($objResultRow->se_id);
                $objSearchProfile->setIdUser($objResultRow->se_userid);
                $objSearchProfile->setSearchMessage($objResultRow->se_message);
                $objSearchProfile->setSearchUser($objResultRow->se_username);
                $objSearchProfile->setSearchDays($objResultRow->se_days);
                $objSearchProfile->setTimestamp($objResultRow->se_tstmp);

                $this->m_arrSearchProfiles[] = $objSearchProfile;
            }
            $objResultSet->freeResult();
        } else {
            return false;
        }
        return true;
    }

    /**
     * get membervariables as array
     *
     * @param int $iTimeOffset time offset in seconds
     * @param string $sDateFormat php date format
     * @return list<array<string, mixed>> member variables
     */
    public function getDataArray(int $iTimeOffset, string $sDateFormat): array
    {
        $arrOutput = [];
        foreach ($this->m_arrSearchProfiles as $objSearchProfile) {
            $arrOutput[] = $objSearchProfile->getDataArray($iTimeOffset, $sDateFormat);
        }
        return $arrOutput;
    }

    /**
     * get the timestamp of the last search
     *
     * @return int timestamp of the last search
     */
    public function getLastProfileTimestamp(): int
    {
        if ($objResultSet = cDBFactory::getInstance()->executeQuery('SELECT MAX(se_tstmp) as lasttstmp FROM pxm_search')) {

            if ($objResultRow = $objResultSet->getNextResultRowObject()) {
                return $objResultRow->lasttstmp;
            }
        }
        return 0;
    }
}
