<?php
require_once(SRCDIR . '/Skin/cSkinTemplate.php');
/**
 * abstraction layer for output (xslt)
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cSkinTemplateXslt extends cSkinTemplate{

	protected string $m_sXmlDoc;					// xml file for the data

	/**
	 * Constructor
	 *
	 * @param string $sSkinDir skin directory
	 * @return void
	 */
	public function __construct(string $sSkinDir){

		parent::__construct($sSkinDir);
		$this->m_sTemplateExtension = '.xsl';

		$this->m_sXmlDoc = 	 "<?xml version=\"1.0\" encoding=\"iso-8859-1\"?>\n"
							."<!DOCTYPE xsl:stylesheet [\n<!ENTITY nbsp \"&#160;\">\n]>\n"
							."<pxmboard>\n";
	}

	/**
	 * add data to the template (internal recursive template method)
	 *
	 * @param array $arrData key - value pairs
	 * @param string $sSubst subst string for integer keys
	 * @return boolean success / failure
	 */
	protected function _addDataRecursive(array $arrData, string $sSubst = ''): bool{
		foreach($arrData as $mKey => $mVal){
			if(is_integer($mKey) && !empty($sSubst)){
				$mKey = $sSubst;
			}
			if(empty($mKey)){
				return false;
			}

			if(is_string($mKey) && (strncmp($mKey,'_',1)==0)){
				$mKey = substr($mKey,1);
			}
			if(is_array($mVal)){
				if(!empty($mVal)){
					if(is_integer(key($mVal))){
						$this->_addDataRecursive($mVal,$mKey);
					}
					else{
						$this->m_sXmlDoc .= "<$mKey>\n";
						$this->_addDataRecursive($mVal,$sSubst);
						$this->m_sXmlDoc .= "</$mKey>\n";
					}
				}
			}
			else{
				if( $mVal===0 || $mVal==='' ){
					$this->m_sXmlDoc .= "<$mKey/>\n";
				}
				else{
					$this->m_sXmlDoc .= "<$mKey><![CDATA[$mVal]]></$mKey>\n";
				}
			}
		}
		return true;
	}

	/**
	 * get the parsed template
	 *
	 * @return string parsed template
	 */
	public function getOutput(): string{

		// Append the closing tag to the XML string
		$this->m_sXmlDoc .= "</pxmboard>\n";

		// Ensure the XSL extension is available
		if (!extension_loaded('xsl')) {
			return 'Error: XSL extension not loaded.';
		}

		$objXsltProcessor = new XSLTProcessor();

		// 1. Prepare the Template (XSL)
		$sTemplatePath = $this->m_sSkinDir . '/' . $this->m_sTemplateName . $this->m_sTemplateExtension;
		$objStyleDoc = new DOMDocument();
		
		// Load file into the instance. @ suppresses warnings to handle them manually.
		if (!@$objStyleDoc->load($sTemplatePath, LIBXML_NOCDATA)) {
			return 'Error: Could not load XSL template file.';
		}
		$objXsltProcessor->importStyleSheet($objStyleDoc);

		// 2. Prepare the Data (XML)
		$objXmlDoc = new DOMDocument();
		if (!@$objXmlDoc->loadXML($this->m_sXmlDoc)) {
			return 'Error: XML source is invalid.';
		}

		// 3. Transform
		$sResult = $objXsltProcessor->transformToXML($objXmlDoc);

		// XSLTProcessor returns false on failure, so we cast to string or return error
		return ($sResult !== false) ? $sResult : 'Error: Transformation failed.';
	}
}
?>
