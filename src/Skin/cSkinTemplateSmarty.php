<?php

require_once(SRCDIR . '/Skin/cSkinTemplate.php');
use Smarty\Smarty;

/**
 * abstraction layer for output (smarty)
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cSkinTemplateSmarty extends cSkinTemplate
{
    protected Smarty $m_objSmarty;					// smarty template parser

    /**
     * Constructor
     *
     * @param string $sSkinDir skin directory
     * @return void
     */
    public function __construct(string $sSkinDir)
    {

        parent::__construct($sSkinDir);
        $this->m_sTemplateExtension = '.tpl';

        $this->m_objSmarty = new Smarty();
        $this->m_objSmarty->setCompileDir($sSkinDir.'/cache');
        $this->m_objSmarty->setTemplateDir($sSkinDir);
        $this->m_objSmarty->enableSecurity();
        $this->m_objSmarty->setEscapeHtml(true);    // escape all template variable output with htmlspecialchars
    }

    /**
     * add data to the template (internal recursive template method)
     *
     * @param array<string, mixed> $arrData key - value pairs
     * @param string $sSubst subst string for integer keys
     * @return bool success / failure
     */
    protected function _addDataRecursive(array $arrData, string $sSubst = ''): bool
    {
        foreach ($arrData as $mKey => $mVal) {
            $this->m_objSmarty->assign($mKey, $mVal);
        }
        return true;
    }

    /**
     * get the parsed template
     *
     * @return string parsed template
     */
    public function getOutput(): string
    {
        return $this->m_objSmarty->fetch($this->m_sTemplateName.$this->m_sTemplateExtension);
    }
}
