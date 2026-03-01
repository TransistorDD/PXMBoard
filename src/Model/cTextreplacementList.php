<?php
/**
 * handles the textreplacements (smilies etc)
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cTextreplacementList{

	/**
	 * Constructor
	 *
	 * @return void
	 */
	public function __construct(){
	}

	/**
	 * get all textreplacements
	 *
	 * @return array textreplacements
	 */
	public function getList(){


		$arrReplacements = array("search"=>array(),"replace"=>array());

		if($objResultSet = cDBFactory::getInstance()->executeQuery("SELECT tr_name,tr_replacement FROM pxm_textreplacement")){
			while($objResultRow = $objResultSet->getNextResultRowObject()){
				if(strlen($objResultRow->tr_name)>0){
					$arrReplacements["search"][] = $objResultRow->tr_name;
					$arrReplacements["replace"][] = $objResultRow->tr_replacement;
				}
			}
			$objResultSet->freeResult();
		}
		return $arrReplacements;
	}

	/**
	 * update all textreplacements
	 *
	 * @param array $arrReplacements textreplacements
	 * @return boolean success / failure
	 */
	public function updateList(array $arrReplacements): bool{

		if(isset($arrReplacements["search"]) && isset($arrReplacements["replace"])){
			if(cDBFactory::getInstance()->executeQuery("DELETE FROM pxm_textreplacement")){
				foreach($arrReplacements["search"] as $iKey=>$sReplacementSearch){
					if(strlen($sReplacementSearch)>0){
						if(isset($arrReplacements["replace"][$iKey])){
							$sReplacementReplace = $arrReplacements["replace"][$iKey];
						}
						else{
							$sReplacementReplace = "";
						}
						cDBFactory::getInstance()->executeQuery("INSERT INTO pxm_textreplacement (tr_name,tr_replacement) VALUES (".cDBFactory::getInstance()->quote($sReplacementSearch).",".cDBFactory::getInstance()->quote($sReplacementReplace).")");
					}
				}
			}
		}
		return true;
	}
}
?>