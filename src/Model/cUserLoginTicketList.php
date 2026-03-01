<?php
require_once(SRCDIR . '/Model/cUserLoginTicket.php');
/**
 * User login ticket list
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cUserLoginTicketList{

	/**
	 * Get all tickets for a user
	 *
	 * @param int $iUserId User ID
	 * @return array Array of cUserLoginTicket objects
	 */
	public static function getTicketsForUser(int $iUserId): array{
		$objDb = cDBFactory::getInstance();
		$arrTickets = array();

		$sQuery = "SELECT ult_id, ult_userid, ult_token, ult_useragent, ult_ipaddress, ".
				  "ult_created_timestamp, ult_last_used_timestamp ".
				  "FROM pxm_user_login_ticket ".
				  "WHERE ult_userid=".$iUserId." ".
				  "ORDER BY ult_last_used_timestamp DESC";

		if($objResultSet = $objDb->executeQuery($sQuery)){
			while($objResultRow = $objResultSet->getNextResultRowObject()){
				$arrTickets[] = cUserLoginTicket::createFromDbRow($objResultRow);
			}
		}

		return $arrTickets;
	}

	/**
	 * Delete inactive tickets (not used for X days)
	 *
	 * @param int $iDaysOld Delete tickets older than X days
	 * @return int Number of deleted tickets
	 */
	public static function deleteInactiveTickets(int $iDaysOld = 180): int{
		$intAffectedRows = 0;
		$objDb = cDBFactory::getInstance();
		$iCutoffTimestamp = time() - ($iDaysOld * 86400);

		$sQuery = "DELETE FROM pxm_user_login_ticket ".
				  "WHERE ult_last_used_timestamp < ".$iCutoffTimestamp;

		if($objResultSet = $objDb->executeQuery($sQuery)){
			$intAffectedRows = $objResultSet->getAffectedRows();
		}

		return $intAffectedRows;
	}

	/**
	 * Delete all tickets for a user
	 *
	 * @param int $iUserId User ID
	 * @return int Number of deleted tickets
	 */
	public static function deleteAllTicketsForUser(int $iUserId): int{
		$intAffectedRows = 0;	
		$objDb = cDBFactory::getInstance();

		$sQuery = "DELETE FROM pxm_user_login_ticket ".
				  "WHERE ult_userid=".$iUserId;

		if($objResultSet = $objDb->executeQuery($sQuery)){
			$intAffectedRows = $objResultSet->getAffectedRows();
		}

		return $intAffectedRows;
	}
}
?>
