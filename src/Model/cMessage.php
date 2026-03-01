<?php
require_once(SRCDIR . '/Model/cMessageHeader.php');
/**
 * message handling
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cMessage extends cMessageHeader{

	protected string $m_sBody;				// messagebody
	protected string $m_sIp;					// ip number for this message

	/**
	 * Constructor
	 *
	 * @return void
	 */
	public function __construct(){

		parent::__construct();

		$this->m_sBody = "";
		$this->m_sIp = "";
	}

	/**
	 * initalize the member variables with the resultset from the db
	 *
	 * @param object $objResultRow resultrow from db query
	 * @return boolean success / failure
	 */
	protected function _setDataFromDb($objResultRow): bool{

		cMessageHeader::_setDataFromDb($objResultRow);

		$this->m_sBody = $objResultRow->m_body;
		$this->m_sIp = $objResultRow->m_ip;

		return true;
	}

	/**
	 * get additional database attributes for this object (template method)
	 *
	 * @return string additional database attributes for this object
	 */
	 protected function _getDbAttributes(){
	 	return cMessageHeader::_getDbAttributes().",m_body,m_ip";
	 }

	/**
	 * get message body
	 *
	 * @return string message body
	 */
	public function getBody(){
		return $this->m_sBody;
	}

	/**
	 * set message body
	 *
	 * @param string $sBody message body
	 * @return void
	 */
	public function setBody($sBody){
		$this->m_sBody = $sBody;
	}

	/**
	 * get ip
	 *
	 * @return string ip
	 */
	public function getIp(){
		return $this->m_sIp;
	}

	/**
	 * set ip
	 *
	 * @param string $sIp ip
	 * @return void
	 */
	public function setIp($sIp){
		$this->m_sIp = $sIp;
	}

	/**
	 * get membervariables as array
	 *
	 * @param integer $iTimeOffset time offset in seconds
	 * @param string $sDateFormat php date format
	 * @param integer $iLastOnlineTimestamp last online timestamp for user
	 * @param string $sSubjectQuotePrefix prefix for quoted subject
 	 * @param object $objParser message parser
	 * @return array member variables
	 */
	public function getDataArray($iTimeOffset,$sDateFormat,$iLastOnlineTimestamp,$sSubjectQuotePrefix = "",$objParser = null){
		// TODO: Vererbung mit unterschiedlicher Methodensignatur optimieren
		return array_merge(cMessageHeader::getDataArray($iTimeOffset,$sDateFormat,$iLastOnlineTimestamp,$sSubjectQuotePrefix,$objParser),
						   array("_body"	=>	$objParser->parse($this->getBody()),
						   		 "ip"		=>	$this->m_sIp));
	}
}
?>