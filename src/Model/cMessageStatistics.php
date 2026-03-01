<?php
require_once(SRCDIR . '/Model/cBoardMessage.php');
/**
 * message statistics
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cMessageStatistics{

	/**
	 * Constructor
	 *
	 * @return void
	 */
	public function __construct(){
	}

	/**
	 * get the amount of messages
	 *
	 * @return integer amount of messages
	 */
	public function getMessageCount(){


		if($objResultSet = cDBFactory::getInstance()->executeQuery("SELECT count(*) AS messages FROM pxm_message")){
			if($objResultRow = $objResultSet->getNextResultRowObject()){
				return $objResultRow->messages;
			}
		}
		return 0;
	}

	/**
	 * get the amount of private messages
	 *
	 * @return integer amount of private messages
	 */
	public function getPrivateMessageCount(){


		if($objResultSet = cDBFactory::getInstance()->executeQuery("SELECT count(*) AS messages FROM pxm_priv_message")){
			if($objResultRow = $objResultSet->getNextResultRowObject()){
				return $objResultRow->messages;
			}
		}
		return 0;
	}

	/**
	 * get the newest messages
	 *
	 * @param integer $iTimeSpan timespan
	 * @return array newest messages
	 */
	public function getNewestMessages($iTimeSpan){
		return $this->_getMessagesByAttribute("m_tstmp","DESC",10,$iTimeSpan);
	}

	/**
	 * get the oldest messages
	 *
	 * @return array oldest messages
	 */
	public function getOldestMessages(){
		return $this->_getMessagesByAttribute("m_tstmp","ASC",10);
	}

	/**
	 * get board messages selected by a passed attribute
	 *
	 * @param string $sAttribute db attribute
	 * @param string $sOrder order by (asc|desc)
	 * @param integer $iLimit limit the result to x rows
	 * @param integer $iTimeSpan timespan
	 * @return array boardmessage objects
	 */
	private function _getMessagesByAttribute($sAttribute,$sOrder = "ASC",$iLimit = 1,$iTimeSpan = 0){

		$arrBoardMessages = array();

		if($objResultSet = cDBFactory::getInstance()->executeQuery("SELECT m_id,m_parentid,t_boardid,t_id,t_active,m_subject,m_tstmp,m_userid,m_username,m_usermail,m_userhighlight FROM pxm_board,pxm_thread,pxm_message WHERE b_id=t_boardid AND t_id=m_threadid AND b_status!=5 AND m_tstmp>".intval($iTimeSpan)." ORDER BY $sAttribute $sOrder",$iLimit)){
			while($objResultRow = $objResultSet->getNextResultRowObject()){

				$objBoardMessage = new cBoardMessage();

				$objBoardMessage->setId($objResultRow->m_id);
				$objBoardMessage->setParentId($objResultRow->m_parentid);
				$objBoardMessage->setBoardId($objResultRow->t_boardid);
				$objBoardMessage->setThreadId($objResultRow->t_id);
				$objBoardMessage->setIsThreadActive($objResultRow->t_active);
				$objBoardMessage->setSubject($objResultRow->m_subject);
				$objBoardMessage->setMessageTimestamp($objResultRow->m_tstmp);
				$objBoardMessage->setAuthorId($objResultRow->m_userid);
				$objBoardMessage->setAuthorUserName($objResultRow->m_username);
				$objBoardMessage->setAuthorPublicMail($objResultRow->m_usermail);
				$objBoardMessage->setAuthorHighlightUser($objResultRow->m_userhighlight);

				$arrBoardMessages[] = $objBoardMessage;
			}
			$objResultSet->freeResult();
		}
		return $arrBoardMessages;
	}
}
?>