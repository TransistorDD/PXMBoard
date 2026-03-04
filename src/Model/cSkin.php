<?php

/**
 * skin handling
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cSkin
{
    protected int $m_iId;							// skin id
    protected string $m_sName;						// name
    protected string $m_sDirectory;					// subdirectory of the templates
    protected array $m_arrSupportedTemplateEngines; // supported template engines
    protected array $m_arrAdditionalSkinValues;		// additional values

    /**
     * Constructor
     *
     * @return void
     */
    public function __construct()
    {

        $this->m_iId = 0;
        $this->m_sName = '';
        $this->m_sDirectory = '';
        $this->m_arrSupportedTemplateEngines = [];
        $this->m_arrAdditionalSkinValues = [];
    }

    /**
     * get data from database by skin id
     *
     * @param int $iSkinId skin id
     * @return bool success / failure
     */
    public function loadDataById(int $iSkinId): bool
    {

        $bReturn = false;
        $iSkinId = intval($iSkinId);

        if ($iSkinId > 0) {


            if ($objResultSet = cDBFactory::getInstance()->executeQuery('SELECT s_fieldname,s_fieldvalue FROM pxm_skin WHERE s_id='.$iSkinId)) {
                if ($objResultSet->getNumRows() >= 3) {
                    $bReturn = true;
                    $this->m_iId = $iSkinId;
                    while ($objResultRow = $objResultSet->getNextResultRowObject()) {
                        switch ($objResultRow->s_fieldname) {
                            case 'name':	$this->m_sName = $objResultRow->s_fieldvalue;
                                break;
                            case 'dir':	$this->m_sDirectory = $objResultRow->s_fieldvalue;
                                break;
                            case 'type':	$this->m_arrSupportedTemplateEngines = explode(',', $objResultRow->s_fieldvalue);
                                break;
                            default:	$this->m_arrAdditionalSkinValues[$objResultRow->s_fieldname] = $objResultRow->s_fieldvalue;
                        }
                    }
                }
                $objResultSet->freeResult();
                unset($objResultSet);
            }
        }
        return $bReturn;
    }

    /**
     * update data in database
     *
     * @return bool success / failure
     */
    public function updateData(): bool
    {

        $bReturn = false;

        if ($this->m_iId > 0) {
            cDBFactory::getInstance()->executeQuery('UPDATE pxm_skin SET s_fieldvalue='.cDBFactory::getInstance()->quote($this->m_sName).' WHERE s_id='.$this->m_iId." AND s_fieldname='name'");
            cDBFactory::getInstance()->executeQuery('UPDATE pxm_skin SET s_fieldvalue='.cDBFactory::getInstance()->quote($this->m_sDirectory).' WHERE s_id='.$this->m_iId." AND s_fieldname='dir'");

            foreach ($this->m_arrAdditionalSkinValues as $sKey => $sValue) {
                cDBFactory::getInstance()->executeQuery('UPDATE pxm_skin SET s_fieldvalue='.cDBFactory::getInstance()->quote($sValue).' WHERE s_id='.$this->m_iId.' AND s_fieldname='.cDBFactory::getInstance()->quote($sKey));
            }
            $bReturn = true;
        }
        return $bReturn;
    }

    /**
     * get id
     *
     * @return int id
     */
    public function getId(): int
    {
        return $this->m_iId;
    }

    /**
     * set id
     *
     * @param int $iId id
     * @return void
     */
    public function setId(int $iId): void
    {
        $this->m_iId = intval($iId);
    }

    /**
     * get name
     *
     * @return string name
     */
    public function getName(): string
    {
        return $this->m_sName;
    }

    /**
     * set name
     *
     * @param string $sName name
     * @return void
     */
    public function setName(string $sName): void
    {
        if (!empty($sName)) {
            $this->m_sName = $sName;
        }
    }

    /**
     * get directory
     *
     * @return string directory
     */
    public function getDirectory(): string
    {
        return $this->m_sDirectory;
    }

    /**
     * set directory
     *
     * @param string $sDirectory directory
     * @return void
     */
    public function setDirectory(string $sDirectory): void
    {
        if (!empty($sDirectory)) {
            $this->m_sDirectory = $sDirectory;
        }
    }

    /**
     * get supported template engines
     *
     * @return array supported template engines
     */
    public function getSupportedTemplateEngines(): array
    {
        return $this->m_arrSupportedTemplateEngines;
    }

    /**
     * set supported template engines
     *
     * @param array $arrSupportedTemplateEngines supported template engines
     * @return void
     */
    public function setSupportedTemplateEngines(array $arrSupportedTemplateEngines): void
    {
        $this->m_arrSupportedTemplateEngines = $arrSupportedTemplateEngines;
    }

    /**
     * get additional skin values
     *
     * @return array additional skin values
     */
    public function getAdditionalSkinValues(): array
    {
        return $this->m_arrAdditionalSkinValues;
    }

    /**
     * set additional skin values
     *
     * @param array $arrAdditionalSkinValues additional skin values
     * @return void
     */
    public function setAdditionalSkinValues(array $arrAdditionalSkinValues): void
    {
        $this->m_arrAdditionalSkinValues = $arrAdditionalSkinValues;
    }

    /**
     * get membervariables as array
     *
     * @param array  $arrAdditionalConfig additional configuration
     * @return array member variables
     */
    public function getDataArray(array $arrAdditionalConfig = []): array
    {
        return array_merge(
            ['id'			=>	$this->m_iId,
                                 'name'		 	=>	$this->m_sName],
            $this->m_arrAdditionalSkinValues
        );
    }
}
