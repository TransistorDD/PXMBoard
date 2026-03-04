<?php

/**
 * handles the user profile configuration
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cProfileConfig
{
    /**
     * Constructor
     *
     * @return void
     */
    public function __construct()
    {
    }

    /**
     * get the installed addional profile slots
     *
     * @return array installed additional profile slots
     */
    public function getSlotList(): array
    {


        $arrProfileSlots = [];

        if ($objResultSet = cDBFactory::getInstance()->executeQuery('SELECT pa_name,pa_type,pa_length FROM pxm_profile_accept')) {
            while ($objResultRow = $objResultSet->getNextResultRowObject()) {
                if (strlen($objResultRow->pa_name) > 0) {
                    $arrProfileSlots[$objResultRow->pa_name] = [$objResultRow->pa_type,$objResultRow->pa_length];
                }
            }
            $objResultSet->freeResult();
        }
        return $arrProfileSlots;
    }

    /**
     * delete profile slots
     *
     * @param array $arrProfileSlots installed additional profile slots
     * @return bool success / failure
     */
    public function deleteSlots(array $arrProfileSlots): bool
    {


        $arrExistingProfileSlots = $this->getSlotList();
        foreach ($arrProfileSlots as $sSlotName) {
            if (preg_match('/^[a-zA-Z]+$/', $sSlotName) && isset($arrExistingProfileSlots[$sSlotName])) {
                if (cDBFactory::getInstance()->executeQuery('DELETE FROM pxm_profile_accept WHERE pa_name='.cDBFactory::getInstance()->quote($sSlotName))) {
                    cDBFactory::getInstance()->executeQuery("ALTER TABLE pxm_user DROP u_profile_$sSlotName");
                }
            }
        }
        return true;
    }

    /**
     * add an attribute to the database
     *
     * @param string $sSlotName name of the new profile slot
     * @param string $sSlotType type of the new profile slot (s = string, a = text, i = integer)
     * @param int $iSlotSize size of the new profile slot
     * @return bool success / failure
     */
    public function addSlot(string $sSlotName, string $sSlotType, int $iSlotSize = -1): bool
    {

        $bReturn = false;
        $arrProfileSlots = $this->getSlotList();
        if (preg_match('/^[a-zA-Z]+$/', $sSlotName) && !isset($arrProfileSlots[$sSlotName])) {

            $iSlotSize = intval($iSlotSize);

            $sQuery = "ALTER TABLE pxm_user ADD u_profile_$sSlotName ";
            switch ($sSlotType) {
                case 'i':	$iSlotSize = 0;
                    $sQuery .= cDBFactory::getInstance()->getMetaType('integer');
                    break;
                case 's':
                case 'a':	if ($iSlotSize <= 0) {
                    $iSlotSize = 1;
                } elseif ($iSlotSize > 60000) {
                    $iSlotSize = 60000;
                }
                    $sQuery .= cDBFactory::getInstance()->getMetaType('string', $iSlotSize);
                    break;
                default:	$type = 's';
                    $iSlotSize = 255;
                    $sQuery .= cDBFactory::getInstance()->getMetaType('string', $iSlotSize);
            }
            if (cDBFactory::getInstance()->executeQuery($sQuery)) {
                if (cDBFactory::getInstance()->executeQuery('INSERT INTO pxm_profile_accept (pa_name,pa_type,pa_length) VALUES ('.cDBFactory::getInstance()->quote($sSlotName).','.cDBFactory::getInstance()->quote($sSlotType).",$iSlotSize)")) {
                    $bReturn = true;
                }
            }
        }
        return $bReturn;
    }
}
