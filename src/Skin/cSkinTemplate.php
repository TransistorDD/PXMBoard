<?php

/**
 * abstraction layer for output (interface)
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
abstract class cSkinTemplate
{
    protected string $m_sSkinDir;					// skin directory
    protected string $m_sTemplateName;				// name of the template
    protected string $m_sTemplateExtension;			// template file extension

    /**
     * Constructor
     *
     * @param string $sSkinDir skin directory
     * @return void
     */
    public function __construct(string $sSkinDir)
    {

        $this->m_sSkinDir = $sSkinDir;
        $this->m_sTemplateName = '';
        $this->m_sTemplateExtension = '';
    }

    /**
     * set the name of the template
     *
     * @param string $sTemplateName name of the template
     * @return void
     */
    public function setTemplateName(string $sTemplateName): void
    {
        $this->m_sTemplateName = $sTemplateName;
    }

    /**
     * is the given template valid (is found)
     *
     * @param string $sTemplateName name of the template
     * @return bool success / failure
     */
    public function isTemplateValid(string $sTemplateName): bool
    {
        if (file_exists($this->m_sSkinDir.'/'.$sTemplateName.$this->m_sTemplateExtension)) {
            return true;
        }
        return false;
    }

    /**
     * add data to the template
     *
     * @param array $arrData key - value pairs
     * @return bool success / failure
     */
    public function addData(array $arrData): bool
    {
        return $this->_addDataRecursive($arrData, '');
    }

    /**
     * add data to the template (internal recursive template method)
     *
     * @param array $arrData key - value pairs
     * @param string $sSubst subst string for integer keys
     * @return bool success / failure
     */
    abstract protected function _addDataRecursive(array $arrData, string $sSubst = ''): bool;

    /**
     * get the parsed template
     *
     * @return string parsed template
     */
    abstract public function getOutput(): string;
}
