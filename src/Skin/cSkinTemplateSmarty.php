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
class cSkinTemplateSmarty extends cSkinTemplate{

	protected Smarty $m_objSmarty;					// smarty template parser

	/**
	 * Constructor
	 *
	 * @param string $sSkinDir skin directory
	 * @return void
	 */
	public function __construct(string $sSkinDir){

		parent::__construct($sSkinDir);
		$this->m_sTemplateExtension = '.tpl';

		$this->m_objSmarty = new Smarty();
		$this->m_objSmarty->setCompileDir($sSkinDir.'/cache');
		$this->m_objSmarty->setTemplateDir($sSkinDir);
		$this->m_objSmarty->enableSecurity();
	}

	/**
	 * add data to the template (internal recursive template method)
	 *
	 * @param array $arrData key - value pairs
	 * @param string $sSubst subst string for integer keys
	 * @return boolean success / failure
	 */
	protected function _addDataRecursive(array $arrData, string $sSubst = ''): bool{

		$this->_quoteSpecialCharsRecursive($arrData);
		foreach($arrData as $mKey => $mVal){
			$this->m_objSmarty->assign($mKey,$mVal);
		}
		return true;
	}

	/**
	 * quote special chars in an array recursive
	 *
	 * @param array $arrData key - value pairs
	 * @return void
	 */
	private function _quoteSpecialCharsRecursive(array &$arrData): void{
		foreach(array_keys($arrData) as $mKey){
			if(is_array($arrData[$mKey])){
				$this->_quoteSpecialCharsRecursive($arrData[$mKey]);
			}
			else if(is_string($mKey) && (strncmp($mKey,"_",1)!=0) && is_string($arrData[$mKey])){
				$arrData[$mKey] = htmlspecialchars($arrData[$mKey]);
			}
			else if($arrData[$mKey]===0){
				$arrData[$mKey] = "";
			}
		}
	}

	/**
	 * get the parsed template
	 *
	 * @return string parsed template
	 */
	public function getOutput(): string{
		return $this->m_objSmarty->fetch($this->m_sTemplateName.$this->m_sTemplateExtension);
	}
}
?>
