<?php
require_once(SRCDIR . '/Model/cUser.php');
/**
 * user profile handling
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cUserProfile extends cUser{

	protected int $m_iLastUpdateTimestap;		// timestamp of last profileupdate
	protected array $m_arrAddFields;			// additional profile fields
	protected array $m_arrAddData;				// additional profile data

	/**
	 * Constructor
	 *
	 * @param array $arrAddFields additional profile fields
	 * @return void
	 */
	public function __construct($arrAddFields = array()){

		parent::__construct();

		$this->m_iLastUpdateTimestap = 0;
		$this->m_arrAddFields = $arrAddFields;
		$this->m_arrAddData = array();
	}

	/**
	 * initalize the member variables with the resultset from the db
	 *
	 * @param object $objResultRow resultrow from db query
	 * @return boolean success / failure
	 */
	protected function _setDataFromDb($objResultRow){

		cUser::_setDataFromDb($objResultRow);

		$this->m_sSignature	= $objResultRow->u_signature;
		$this->m_iLastUpdateTimestap = intval($objResultRow->u_profilechangedtstmp);

		foreach($this->m_arrAddFields as $sFieldName => $arrFieldAttributes){
			$sResultVarName = "u_profile_".$sFieldName;
			$this->m_arrAddData[$sFieldName] = ($arrFieldAttributes[0]=='i'?intval($objResultRow->$sResultVarName):$objResultRow->$sResultVarName);
		}

		return true;
	}

	/**
	 * get additional database attributes for this object (template method)
	 *
	 * @return string additional database attributes for this object
	 */
	protected function _getDbAttributes(){

	 	$sAddDbFields = "";
	 	foreach(array_keys($this->m_arrAddFields)as $sFieldName){
			$sAddDbFields .= ",u_profile_".$sFieldName;
		}
	 	return cUser::_getDbAttributes().",u_signature,u_profilechangedtstmp".$sAddDbFields;
	}

	/**
	 * update data in database
	 *
	 * @return boolean success / failure
	 */
	public function updateData(){

		$sAddUpdateQuery = "";

		foreach($this->m_arrAddData as $sFieldName => $mData){
			if(is_integer($mData)){
				$sAddUpdateQuery .= "u_profile_".$sFieldName."=".$mData.",";
			}
			else{
				$sAddUpdateQuery .= "u_profile_".$sFieldName."=".cDBFactory::getInstance()->quote($mData).",";
			}
		}

		if(!cDBFactory::getInstance()->executeQuery("UPDATE pxm_user SET ".
												 "u_lastname=".cDBFactory::getInstance()->quote($this->m_sLastName).",".
												 "u_city=".cDBFactory::getInstance()->quote($this->m_sCity).",".
												 "u_publicmail=".cDBFactory::getInstance()->quote($this->m_sPublicMail).",".
												 $sAddUpdateQuery.
												 "u_profilechangedtstmp=".$this->m_iLastUpdateTimestap.
							" WHERE u_id=".$this->m_iId)){
			return false;
		}
		return true;
	}

	/**
	 * get last update timestamp
	 *
	 * @return integer last update timestamp
	 */
	public function getLastUpdateTimestamp(){
		return $this->m_iLastUpdateTimestap;
	}

	/**
	 * set last update timestamp
	 *
	 * @param integer $iLastUpdateTimestap last update timestamp
	 * @return void
	 */
	public function setLastUpdateTimestamp($iLastUpdateTimestap){
		$this->m_iLastUpdateTimestap = intval($iLastUpdateTimestap);
	}

	/**
	 * get additional data element
	 *
	 * @param string $sElementName name of the additional data element
	 * @return mixed element
	 */
	public function getAdditionalDataElement($sElementName){
		if(isset($this->m_arrAddData[$sElementName])){
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
	public function setAdditionalDataElement($sElementName,$mElementValue){
		if(isset($this->m_arrAddFields[$sElementName])){
			if($this->m_arrAddFields[$sElementName][0]=='i'){
				$mElementValue = intval($mElementValue);
			}
			$this->m_arrAddData[$sElementName] = $mElementValue;
		}
	}

	/**
	 * get membervariables as array
	 *
	 * @param integer $iTimeOffset time offset in seconds
	 * @param string $sDateFormat php date format
	 * @param object $objParser message parser (for signature)
	 * @return array member variables
	 */
	public function getDataArray($iTimeOffset,$sDateFormat,$objParser){
		return array_merge(cUser::getDataArray($iTimeOffset,$sDateFormat,$objParser),
						   array("lchange"	=>	(($this->m_iLastUpdateTimestap>0)?date($sDateFormat,($this->m_iLastUpdateTimestap+$iTimeOffset)):0)),
						   $this->m_arrAddData);
	}
}
?>