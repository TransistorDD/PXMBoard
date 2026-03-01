<?php
/**
 * Standardized search result set
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cSearchResultSet {

	/**
	 * @var array Array of result items with structure: ['id' => int, 'score' => float, 'timestamp' => int]
	 */
	private array $m_arrResults;

	/**
	 * @var int Total count of results (may differ from array size due to pagination)
	 */
	private int $m_iTotalCount;

	/**
	 * Constructor
	 *
	 * @param array $arrResults Array of result items
	 * @param int $iTotalCount Total count of results
	 * @return void
	 */
	public function __construct(array $arrResults = [], int $iTotalCount = 0) {
		$this->m_arrResults = $arrResults;
		$this->m_iTotalCount = $iTotalCount;
	}

	/**
	 * Get the search results
	 *
	 * @return array Array of result items with keys: id, score, timestamp
	 */
	public function getResults(): array {
		return $this->m_arrResults;
	}

	/**
	 * Get total count of results
	 *
	 * @return int Total result count
	 */
	public function getTotalCount(): int {
		return $this->m_iTotalCount;
	}

	/**
	 * Check if result set is empty
	 *
	 * @return bool True if no results
	 */
	public function isEmpty(): bool {
		return empty($this->m_arrResults);
	}

	/**
	 * Get count of results in this set (may differ from total count)
	 *
	 * @return int Count of results
	 */
	public function getCount(): int {
		return count($this->m_arrResults);
	}
}
?>
