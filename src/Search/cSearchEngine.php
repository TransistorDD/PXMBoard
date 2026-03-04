<?php

require_once(SRCDIR . '/Search/cSearchResultSet.php');
/**
 * Abstraction layer for search engine handling (interface)
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
abstract class cSearchEngine
{
    /**
     * Execute a message search and return results
     *
     * This method performs a full-text search on message subjects and bodies.
     * Results are ordered by relevance score (DESC) and timestamp (DESC).
     *
     * Weighted scoring (preserved from MySQL implementation):
     * - Root messages (parent_id = 0): Subject match 4.0x, Body match 3.0x
     * - Reply messages (parent_id > 0): Subject match 2.0x, Body match 1.0x
     *
     * @param string $sSearchTerm Search query string (may contain wildcards, phrases)
     * @param string $sUserName Filter by username (LIKE prefix match), empty = no filter
     * @param array<int> $arrBoardIds Filter by board IDs (array of integers), empty = all boards
     * @param int $iSearchDays Timespan in days (0 = all time, N = last N days)
     * @param int $iSearchTimestamp Reference timestamp for timespan calculation
     * @param int $iTimeOffset User timezone offset in seconds
     * @param int $iCurrentUserId Current user ID (for draft visibility: 0 = no drafts, >0 = include user's drafts)
     * @param int $iLimit Maximum number of results (default: 500)
     * @return cSearchResultSet Standardized result set with message IDs, scores and timestamps
     */
    abstract public function search(
        string $sSearchTerm,
        string $sUserName,
        array $arrBoardIds,
        int $iSearchDays,
        int $iSearchTimestamp,
        int $iTimeOffset,
        int $iCurrentUserId,
        int $iLimit = 500
    ): cSearchResultSet;

    /**
     * Index or update a message in the search index
     *
     * This method is called after INSERT or UPDATE operations on messages.
     * For MySQL FULLTEXT, this is a no-op (indexes update automatically).
     * For ElasticSearch, this creates or updates the document in the index.
     *
     * @param int $iMessageId Message ID
     * @param int $iThreadId Thread ID
     * @param int $iBoardId Board ID
     * @param int $iParentId Parent message ID (0 = root message)
     * @param int $iUserId Author user ID
     * @param string $sUserName Author username
     * @param string $sSubject Message subject
     * @param string $sBody Message body (raw text)
     * @param int $iTimestamp Message timestamp
     * @param int $iStatus Message status (0 = draft, 1 = published)
     * @return bool Success or failure
     */
    abstract public function indexMessage(
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
    ): bool;

    /**
     * Remove a message from the search index
     *
     * This method is called after DELETE operations on messages.
     * For MySQL FULLTEXT, this is a no-op (indexes update automatically).
     * For ElasticSearch, this removes the document from the index.
     *
     * @param int $iMessageId Message ID to remove
     * @return bool Success or failure
     */
    abstract public function removeMessage(int $iMessageId): bool;

    /**
     * Bulk-index multiple messages (for initial import/reindexing)
     *
     * This method is used for initial population of the search index or
     * complete reindexing. It should be optimized for batch operations.
     *
     * @param array<mixed> $arrMessages Array of message data arrays with keys:
     *                           id, thread_id, board_id, parent_id, user_id,
     *                           username, subject, body, timestamp, status
     * @return int Number of successfully indexed messages
     */
    abstract public function bulkIndex(array $arrMessages): int;

    /**
     * Check if the search engine is available and properly configured
     *
     * This method verifies that the search engine can be used.
     * For MySQL: Check if FULLTEXT indexes exist
     * For ElasticSearch: Check connection to cluster
     *
     * @return bool True if search engine is available
     */
    abstract public function isAvailable(): bool;

    /**
     * Get the type of the search engine
     *
     * @return string Search engine type (e.g., "MySql", "ElasticSearch")
     */
    abstract public function getEngineType(): string;
}
