<?php

/**
 * Template handling (text templates for emails and application messages)
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cTemplate
{
    protected int $m_iId = 0;							// template id
    protected string $m_sMessage = '';					// template message
    protected string $m_sName = '';						// name of the template
    protected string $m_sDescription = '';				// description of the template

    /**
     * get data from database by template id
     *
     * @param int $iTemplateId template id
     * @return bool success / failure
     */
    public function loadDataById(int $iTemplateId): bool
    {
        $bReturn = false;

        if ($iTemplateId > 0) {
            if ($objResultSet = cDBFactory::getInstance()->executeQuery('SELECT te_id,'.
                                                            'te_message,'.
                                                            'te_name,'.
                                                            'te_description'.
                                                            ' FROM pxm_template'.
                                                            ' WHERE te_id='.$iTemplateId)) {
                if ($objResultRow = $objResultSet->getNextResultRowObject()) {
                    $this->m_iId = (int) $objResultRow->te_id;
                    $this->m_sMessage = $objResultRow->te_message;
                    $this->m_sName = $objResultRow->te_name;
                    $this->m_sDescription = $objResultRow->te_description;

                    $bReturn = true;
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
            if (cDBFactory::getInstance()->executeQuery('UPDATE pxm_template SET te_message='.cDBFactory::getInstance()->quote($this->m_sMessage)." WHERE te_id=$this->m_iId")) {
                $bReturn = true;
            }
        }
        return $bReturn;
    }

    /**
     * get the id of this template
     *
     * @return int template id
     */
    public function getId(): int
    {
        return $this->m_iId;
    }

    /**
     * set the id of this template
     *
     * @param int $iTemplateId template id
     * @return void
     */
    public function setId(int $iTemplateId): void
    {
        $this->m_iId = $iTemplateId;
    }

    /**
     * get the message for this template
     *
     * @return string template message
     */
    public function getMessage(): string
    {
        return $this->m_sMessage;
    }

    /**
     * set the message for this template
     *
     * @param string $sMessage template message
     * @return void
     */
    public function setMessage(string $sMessage): void
    {
        $this->m_sMessage = $sMessage;
    }

    /**
     * get the name of this template
     *
     * @return string template name
     */
    public function getName(): string
    {
        return $this->m_sName;
    }

    /**
     * set the name of this template
     *
     * @param string $sName template name
     * @return void
     */
    public function setName(string $sName): void
    {
        $this->m_sName = $sName;
    }

    /**
     * get the description of this template
     *
     * @return string template description
     */
    public function getDescription(): string
    {
        return $this->m_sDescription;
    }

    /**
     * set the description of this template
     *
     * @param string $sDescription template description
     * @return void
     */
    public function setDescription(string $sDescription): void
    {
        $this->m_sDescription = $sDescription;
    }
}
