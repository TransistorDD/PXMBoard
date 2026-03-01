<?php
require_once(SRCDIR . '/Search/cSearchEngine.php');
require_once(SRCDIR . '/Database/cDBFactory.php');
require_once(SRCDIR . '/Enum/eMessage.php');
/**
 * MySQL FULLTEXT search engine implementation
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cSearchEngineMySql extends cSearchEngine {

	/**
	 * Constructor
	 *
	 * @param array $arrConfig Configuration array (not used for MySQL)
	 * @return void
	 */
	public function __construct(array $arrConfig) {
		// MySQL search engine doesn't need additional configuration
		// It uses the existing database connection from cDBFactory
	}

	/**
	 * Execute a message search and return results
	 *
	 * This implementation uses MySQL FULLTEXT search with weighted relevance scoring.
	 *
	 * @param string $sSearchTerm Search query string
	 * @param string $sUserName Filter by username (LIKE prefix match), empty = no filter
	 * @param array $arrBoardIds Filter by board IDs, empty = all boards
	 * @param int $iSearchDays Timespan in days (0 = all time)
	 * @param int $iSearchTimestamp Reference timestamp for timespan calculation
	 * @param int $iTimeOffset User timezone offset in seconds
	 * @param int $iCurrentUserId Current user ID (for draft visibility)
	 * @param int $iLimit Maximum number of results
	 * @return cSearchResultSet Standardized result set
	 */
	public function search(
		string $sSearchTerm,
		string $sUserName,
		array $arrBoardIds,
		int $iSearchDays,
		int $iSearchTimestamp,
		int $iTimeOffset,
		int $iCurrentUserId,
		int $iLimit = 500
	): cSearchResultSet {

		$objDb = cDBFactory::getInstance();
		$arrResults = [];

		// Build MATCH score and filter expressions
		$sMatchScore = "";
		$sMatchFilter = "";

		if (!empty($sSearchTerm)) {
			// Add wildcard for partial matches in Boolean Mode
			$sSearchTermProcessed = $sSearchTerm;
			if (strpos($sSearchTermProcessed, '*') === false && strpos($sSearchTermProcessed, '"') === false) {
				// Auto-add wildcard if not already present and not a phrase search
				$sSearchTermProcessed .= '*';
			}

			$sQuotedTerm = $objDb->quote($sSearchTermProcessed);

			// Composite MATCH for WHERE clause filtering (uses m_search composite FULLTEXT index)
			$sMatchFilter = "MATCH(m_subject,m_body) AGAINST(" . $sQuotedTerm . " IN BOOLEAN MODE)";

			// Weighted relevance scoring for SELECT clause (uses composite FULLTEXT index):
			// Root messages (thread starters) are weighted higher than replies.
			$sMatchScore = "(CASE WHEN m_parentid = 0 THEN " .
						   "  MATCH(m_subject,m_body) AGAINST(" . $sQuotedTerm . " IN BOOLEAN MODE) * 3.0 " .
						   "ELSE " .
						   "  MATCH(m_subject,m_body) AGAINST(" . $sQuotedTerm . " IN BOOLEAN MODE) * 1.0 " .
						   "END)";
		} else {
			$sMatchScore = "0";
		}

		// Status filter: only published messages OR drafts from current user
		$sStatusFilter = "(m_status=" . MessageStatus::PUBLISHED->value;
		if ($iCurrentUserId > 0) {
			$sStatusFilter .= " OR (m_status=" . MessageStatus::DRAFT->value . " AND m_userid=" . $iCurrentUserId . ")";
		}
		$sStatusFilter .= ")";

		// Build main query
		$sQuery = "SELECT m_id, m_tstmp, ROUND(" . $sMatchScore . ",2) AS score " .
				  "FROM pxm_board " .
				  "INNER JOIN pxm_thread ON b_id = t_boardid " .
				  "INNER JOIN pxm_message ON t_id = m_threadid " .
				  "WHERE b_status!=5 AND " . $sStatusFilter;

		// Add timespan filter
		if ($iSearchDays > 0) {
			$sQuery .= " AND m_tstmp>" . ($iSearchTimestamp - $iSearchDays * 86400 + $iTimeOffset);
		}

		// Add username filter
		if (!empty($sUserName)) {
			$sQuery .= " AND m_username LIKE " . $objDb->quote($sUserName . "%");
		}

		// Add board filter
		if (!empty($arrBoardIds)) {
			if (count($arrBoardIds) > 1) {
				$sQuery .= " AND t_boardid IN (" . implode(",", $arrBoardIds) . ")";
			} else {
				$sQuery .= " AND t_boardid = " . $arrBoardIds[0];
			}
		}

		// Add search term filter
		if (!empty($sSearchTerm)) {
			$sQuery .= " AND " . $sMatchFilter;
		}

		// Order by score and timestamp, limit results
		$sQuery .= " ORDER BY score DESC, m_tstmp DESC";
		$sQuery .= " LIMIT " . ($iLimit + 1); // +1 to detect overflow

		// Execute query
		if ($objResultSet = $objDb->executeQuery($sQuery)) {
			while ($objRow = $objResultSet->getNextResultRowObject()) {
				$arrResults[] = [
					'id' => intval($objRow->m_id),
					'score' => floatval($objRow->score),
					'timestamp' => intval($objRow->m_tstmp)
				];
			}
			$objResultSet->freeResult();
		}

		// Return result set (total count = result count for this implementation)
		return new cSearchResultSet($arrResults, count($arrResults));
	}

	/**
	 * Index or update a message in the search index
	 *
	 * For MySQL FULLTEXT, this is a no-op as indexes update automatically.
	 *
	 * @param int $iMessageId Message ID
	 * @param int $iThreadId Thread ID
	 * @param int $iBoardId Board ID
	 * @param int $iParentId Parent message ID
	 * @param int $iUserId Author user ID
	 * @param string $sUserName Author username
	 * @param string $sSubject Message subject
	 * @param string $sBody Message body
	 * @param int $iTimestamp Message timestamp
	 * @param int $iStatus Message status
	 * @return bool Always returns true (no-op)
	 */
	public function indexMessage(
		int $iMessageId,
		int $iThreadId,
		int $iBoardId,
		int $iParentId,
		int $iUserId,
		string $sUserName,
		string $sSubject,
		string $sBody,
		int $iTimestamp,
		int $iStatus
	): bool {
		// No-op for MySQL: FULLTEXT indexes update automatically on INSERT/UPDATE
		return true;
	}

	/**
	 * Remove a message from the search index
	 *
	 * For MySQL FULLTEXT, this is a no-op as indexes update automatically.
	 *
	 * @param int $iMessageId Message ID to remove
	 * @return bool Always returns true (no-op)
	 */
	public function removeMessage(int $iMessageId): bool {
		// No-op for MySQL: FULLTEXT indexes update automatically on DELETE
		return true;
	}

	/**
	 * Bulk-index multiple messages
	 *
	 * For MySQL FULLTEXT, this is a no-op as indexes exist and are populated automatically.
	 *
	 * @param array $arrMessages Array of message data
	 * @return int Always returns 0 (no-op)
	 */
	public function bulkIndex(array $arrMessages): int {
		// No-op for MySQL: FULLTEXT indexes are automatically populated
		return 0;
	}

	/**
	 * Check if the search engine is available
	 *
	 * Verifies that FULLTEXT indexes exist on pxm_message table.
	 *
	 * @return bool True if FULLTEXT indexes exist
	 */
	public function isAvailable(): bool {
		$objDb = cDBFactory::getInstance();

		// Check if composite FULLTEXT index exists on pxm_message
		$sQuery = "SHOW INDEX FROM pxm_message WHERE Index_type='FULLTEXT' AND Key_name='m_search'";
		if ($objResultSet = $objDb->executeQuery($sQuery)) {
			$bFound = $objResultSet->getNextResultRowObject() !== null;
			$objResultSet->freeResult();
			return $bFound;
		}

		return false;
	}

	/**
	 * Get the type of the search engine
	 *
	 * @return string Returns "MySql"
	 */
	public function getEngineType(): string {
		return "MySql";
	}
}
?>
