<?php

namespace PXMBoard\Search;

use PXMBoard\Exception\cSearchEngineException;

/**
 * Factory class for search engine abstraction (singleton pattern)
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cSearchEngineFactory
{
    /**
     * @var cSearchEngine|null Singleton instance of search engine
     */
    private static ?cSearchEngine $objInstance = null;

    /**
     * Private Constructor - prevents direct instantiation
     */
    private function __construct()
    {
    }

    /**
     * Get singleton instance of search engine
     *
     * @param array<string, mixed>|null $arrConfig Search engine configuration (required on first call, must contain 'type' key, e.g. "MySql", "ElasticSearch")
     * @return cSearchEngine Search engine object
     * @throws cSearchEngineException if configuration is invalid or engine is unavailable
     */
    public static function getInstance(?array $arrConfig = null): cSearchEngine
    {
        // First call: initialize with config
        if (self::$objInstance === null) {
            if ($arrConfig === null) {
                throw new cSearchEngineException('Search engine configuration required on first getInstance() call');
            }

            if (!isset($arrConfig['type'])) {
                throw new cSearchEngineException("Invalid search engine configuration: missing 'type' key");
            }

            // Create search engine object
            self::$objInstance = self::getSearchEngineObject($arrConfig['type'], $arrConfig);
            if (!self::$objInstance) {
                throw new cSearchEngineException('Invalid search engine type: ' . $arrConfig['type']);
            }

            // Verify availability
            if (!self::$objInstance->isAvailable()) {
                throw new cSearchEngineException('Search engine is not available: ' . $arrConfig['type']);
            }
        }

        return self::$objInstance;
    }

    /**
     * Instantiates and returns the selected search engine object
     *
     * @param string $sEngineType Search engine type (MySql, ElasticSearch, ...)
     * @param array<string, mixed> $arrConfig Engine-specific configuration
     * @return cSearchEngine|null Search engine object or null on error
     */
    private static function getSearchEngineObject(string $sEngineType, array $arrConfig): ?cSearchEngine
    {
        if (preg_match('/^[a-zA-Z]+$/', $sEngineType)) {
            $sClassName = __NAMESPACE__ . '\\cSearchEngine' . $sEngineType;

            if (class_exists($sClassName)) {
                $objEngine = new $sClassName($arrConfig);
                return $objEngine;
            }
        }
        return null;
    }
}
