<?php

namespace PXMBoard\Model;

use PXMBoard\Database\cDB;

/**
 * user profile handling
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cUserProfile extends cUser
{
    protected int $m_iLastUpdateTimestap = 0;		// timestamp of last profileupdate
    /** @var array<string, mixed> */
    protected array $m_arrAddFields;			// additional profile fields
    /** @var array<string, mixed> */
    protected array $m_arrAddData = [];				// additional profile data

    /**
     * Constructor
     *
     * @param array<string, mixed> $arrAddFields additional profile fields
     * @return void
     */
    public function __construct(array $arrAddFields = [])
    {
        parent::__construct();

        $this->m_arrAddFields = $arrAddFields;
    }

    /**
     * initalize the member variables with the resultset from the db
     *
     * @param object $objResultRow resultrow from db query
     * @return bool success / failure
     */
    protected function _setDataFromDb(object $objResultRow): bool
    {
        cUser::_setDataFromDb($objResultRow);

        $this->m_sSignature	= $objResultRow->u_signature;
        $this->m_iLastUpdateTimestap = (int) $objResultRow->u_profilechangedtstmp;

        foreach ($this->m_arrAddFields as $sFieldName => $arrFieldAttributes) {
            $sResultVarName = 'u_profile_'.$sFieldName;
            $this->m_arrAddData[$sFieldName] = ($arrFieldAttributes[0] == 'i' ? (int) $objResultRow->$sResultVarName : $objResultRow->$sResultVarName);
        }

        return true;
    }

    /**
     * get additional database attributes for this object (template method)
     *
     * @return string additional database attributes for this object
     */
    protected function _getDbAttributes(): string
    {
        $sAddDbFields = '';
        foreach (array_keys($this->m_arrAddFields) as $sFieldName) {
            $sAddDbFields .= ',u_profile_'.$sFieldName;
        }
        return cUser::_getDbAttributes().',u_signature,u_profilechangedtstmp'.$sAddDbFields;
    }

    /**
     * update data in database
     *
     * @return bool success / failure
     */
    public function updateData(): bool
    {
        $sAddUpdateQuery = '';

        foreach ($this->m_arrAddData as $sFieldName => $mData) {
            if (is_integer($mData)) {
                $sAddUpdateQuery .= 'u_profile_'.$sFieldName.'='.$mData.',';
            } else {
                $sAddUpdateQuery .= 'u_profile_'.$sFieldName.'='.cDB::getInstance()->quote($mData).',';
            }
        }

        if (!cDB::getInstance()->executeQuery('UPDATE pxm_user SET '.
                                                 'u_lastname='.cDB::getInstance()->quote($this->m_sLastName).','.
                                                 'u_city='.cDB::getInstance()->quote($this->m_sCity).','.
                                                 'u_publicmail='.cDB::getInstance()->quote($this->m_sPublicMail).','.
                                                 $sAddUpdateQuery.
                                                 'u_profilechangedtstmp='.$this->m_iLastUpdateTimestap.
                            ' WHERE u_id='.$this->m_iId)) {
            return false;
        }
        return true;
    }

    /**
     * get last update timestamp
     *
     * @return int last update timestamp
     */
    public function getLastUpdateTimestamp(): int
    {
        return $this->m_iLastUpdateTimestap;
    }

    /**
     * set last update timestamp
     *
     * @param int $iLastUpdateTimestap last update timestamp
     * @return void
     */
    public function setLastUpdateTimestamp(int $iLastUpdateTimestap): void
    {
        $this->m_iLastUpdateTimestap = $iLastUpdateTimestap;
    }

    /**
     * get additional data element
     *
     * @param string $sElementName name of the additional data element
     * @return mixed element
     */
    public function getAdditionalDataElement(string $sElementName): mixed
    {
        if (isset($this->m_arrAddData[$sElementName])) {
            return $this->m_arrAddData[$sElementName];
        }
        return null;
    }

    /**
     * set additional data element
     *
     * @param string $sElementName name of the additional data element
     * @param mixed $mElementValue element
     * @return void
     */
    public function setAdditionalDataElement(string $sElementName, mixed $mElementValue): void
    {
        if (isset($this->m_arrAddFields[$sElementName])) {
            if ($this->m_arrAddFields[$sElementName][0] == 'i') {
                $mElementValue = (int) $mElementValue;
            }
            $this->m_arrAddData[$sElementName] = $mElementValue;
        }
    }

    /**
     * get membervariables as array
     *
     * @param int $iTimeOffset time offset in seconds
     * @param string $sDateFormat php date format
     * @param object|null $objParser message parser (for signature)
     * @return array<string, mixed> member variables
     */
    public function getDataArray(int $iTimeOffset, string $sDateFormat, ?object $objParser): array
    {
        return array_merge(
            cUser::getDataArray($iTimeOffset, $sDateFormat, $objParser),
            ['lchange'	=>	(($this->m_iLastUpdateTimestap > 0) ? date($sDateFormat, ($this->m_iLastUpdateTimestap + $iTimeOffset)) : 0)],
            $this->m_arrAddData
        );
    }
}
