<?php
/**
 * Template handling (text templates for emails and application messages)
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cTemplate{

	protected int $m_iId;									// template id
	protected string $m_sMessage;							// template message
	protected string $m_sName;								// name of the template
	protected string $m_sDescription;						// description of the template

	/**
	 * Constructor
	 *
	 * @return void
	 */
	public function __construct(){
		$this->m_iId = 0;
		$this->m_sMessage = "";
		$this->m_sName = "";
		$this->m_sDescription = "";
	}

	/**
	 * get data from database by template id
	 *
	 * @param integer $iTemplateId template id
	 * @return boolean success / failure
	 */
	public function loadDataById($iTemplateId){

		$bReturn = false;
		$iTemplateId = intval($iTemplateId);

		if($iTemplateId>0){


			if($objResultSet = cDBFactory::getInstance()->executeQuery("SELECT te_id,".
															"te_message,".
															"te_name,".
															"te_description".
															" FROM pxm_template".
															" WHERE te_id=".$iTemplateId)){
				if($objResultRow = $objResultSet->getNextResultRowObject()){
					$this->m_iId = intval($objResultRow->te_id);
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
	 * @return boolean success / failure
	 */
	public function updateData(){


		$bReturn = false;
		if($this->m_iId>0){
			if(cDBFactory::getInstance()->executeQuery("UPDATE pxm_template SET te_message=".cDBFactory::getInstance()->quote($this->m_sMessage)." WHERE te_id=$this->m_iId")){
				$bReturn = true;
			}
		}
		return $bReturn;
	}

	/**
	 * get the id of this template
	 *
	 * @return integer template id
	 */
	public function getId(){
		return $this->m_iId;
	}

	/**
	 * set the id of this template
	 *
	 * @param integer $iTemplateId template id
	 * @return void
	 */
	public function setId($iTemplateId){
		$this->m_iId = intval($iTemplateId);;
	}

	/**
	 * get the message for this template
	 *
	 * @return string template message
	 */
	public function getMessage(){
		return $this->m_sMessage;
	}

	/**
	 * set the message for this template
	 *
	 * @param string $sMessage template message
	 * @return void
	 */
	public function setMessage($sMessage){
		$this->m_sMessage = $sMessage;
	}

	/**
	 * get the name of this template
	 *
	 * @return string template name
	 */
	public function getName(){
		return $this->m_sName;
	}

	/**
	 * set the name of this template
	 *
	 * @param string $sName template name
	 * @return void
	 */
	public function setName($sName){
		$this->m_sName = $sName;
	}

	/**
	 * get the description of this template
	 *
	 * @return string template description
	 */
	public function getDescription(){
		return $this->m_sDescription;
	}

	/**
	 * set the description of this template
	 *
	 * @param string $sDescription template description
	 * @return void
	 */
	public function setDescription($sDescription){
		$this->m_sDescription = $sDescription;
	}
}
?>
