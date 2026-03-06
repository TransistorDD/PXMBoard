<?php

namespace PXMBoard\Search;

use PXMBoard\Enum\eMessageStatus;

/**
 * ElasticSearch search engine implementation
 *
 * Requires: composer package elasticsearch/elasticsearch
 * Install: composer require elasticsearch/elasticsearch
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cSearchEngineElasticSearch extends cSearchEngine
{
    /**
     * @var object ElasticSearch client instance
     */
    private mixed $m_objClient;

    /**
     * @var string ElasticSearch index name
     */
    private string $m_sIndexName;

    /**
     * Constructor
     *
     * @param array<string, mixed> $arrConfig Configuration array with keys: host, index, api_key (optional)
     * @return void
     */
    public function __construct(array $arrConfig)
    {
        // Check if Elasticsearch client is available
        if (!class_exists('Elastic\Elasticsearch\ClientBuilder')) {
            throw new \RuntimeException('Elasticsearch PHP client not found. Install with: composer require elasticsearch/elasticsearch');
        }

        // Validate configuration
        if (!isset($arrConfig['host']) || !isset($arrConfig['index'])) {
            throw new \RuntimeException('ElasticSearch configuration requires "host" and "index" keys');
        }

        $this->m_sIndexName = $arrConfig['index'];

        // Build client
        $clientBuilder = \Elastic\Elasticsearch\ClientBuilder::create()
            ->setHosts([$arrConfig['host']]);

        // Add API key if provided
        if (isset($arrConfig['api_key']) && !empty($arrConfig['api_key'])) {
            $clientBuilder->setApiKey($arrConfig['api_key']);
        }

        $this->m_objClient = $clientBuilder->build();

        // Create index if it doesn't exist
        $this->_createIndexIfNotExists();
    }

    /**
     * Execute a message search and return results
     *
     * @param string $sSearchTerm Search query string
     * @param string $sUserName Filter by username, empty = no filter
     * @param array<int> $arrBoardIds Filter by board IDs, empty = all boards
     * @param int $iSearchDays Timespan in days (0 = all time)
     * @param int $iSearchTimestamp Reference timestamp
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

        // Build query
        $arrQuery = [
            'index' => $this->m_sIndexName,
            'body' => [
                'size' => $iLimit + 1, // +1 to detect overflow
                'query' => $this->_buildQuery(
                    $sSearchTerm,
                    $sUserName,
                    $arrBoardIds,
                    $iSearchDays,
                    $iSearchTimestamp,
                    $iTimeOffset,
                    $iCurrentUserId
                ),
                'sort' => [
                    ['_score' => ['order' => 'desc']],
                    ['timestamp' => ['order' => 'desc']]
                ]
            ]
        ];

        // Execute search
        try {
            $response = $this->m_objClient->search($arrQuery);
            $arrResults = [];

            // Parse results
            if (isset($response['hits']['hits'])) {
                foreach ($response['hits']['hits'] as $hit) {
                    $arrResults[] = [
                        'id' => (int) $hit['_id'],
                        'score' => (float) $hit['_score'],
                        'timestamp' => (int) $hit['_source']['timestamp']
                    ];
                }
            }

            return new cSearchResultSet($arrResults, count($arrResults));

        } catch (\Exception $e) {
            // Log error and return empty result set
            error_log('ElasticSearch search error: ' . $e->getMessage());
            return new cSearchResultSet([], 0);
        }
    }

    /**
     * Index or update a message in the search index
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
     * @return bool Success or failure
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

        $arrParams = [
            'index' => $this->m_sIndexName,
            'id' => $iMessageId,
            'body' => [
                'thread_id' => $iThreadId,
                'board_id' => $iBoardId,
                'parent_id' => $iParentId,
                'user_id' => $iUserId,
                'username' => $sUserName,
                'subject' => $sSubject,
                'body' => $sBody,
                'timestamp' => $iTimestamp,
                'status' => $iStatus
            ]
        ];

        try {
            $this->m_objClient->index($arrParams);
            return true;
        } catch (\Exception $e) {
            error_log('ElasticSearch indexing error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Remove a message from the search index
     *
     * @param int $iMessageId Message ID to remove
     * @return bool Success or failure
     */
    public function removeMessage(int $iMessageId): bool
    {
        $arrParams = [
            'index' => $this->m_sIndexName,
            'id' => $iMessageId
        ];

        try {
            $this->m_objClient->delete($arrParams);
            return true;
        } catch (\Exception $e) {
            // Document might not exist - not necessarily an error
            if (strpos($e->getMessage(), 'document_missing_exception') === false) {
                error_log('ElasticSearch delete error: ' . $e->getMessage());
            }
            return true; // Return true anyway to not block deletion
        }
    }

    /**
     * Bulk-index multiple messages
     *
     * @param array<mixed> $arrMessages Array of message data
     * @return int Number of successfully indexed messages
     */
    public function bulkIndex(array $arrMessages): int
    {
        if (empty($arrMessages)) {
            return 0;
        }

        $arrParams = ['body' => []];
        $iSuccessCount = 0;

        foreach ($arrMessages as $arrMessage) {
            // Index action
            $arrParams['body'][] = [
                'index' => [
                    '_index' => $this->m_sIndexName,
                    '_id' => $arrMessage['id']
                ]
            ];

            // Document data
            $arrParams['body'][] = [
                'thread_id' => $arrMessage['thread_id'],
                'board_id' => $arrMessage['board_id'],
                'parent_id' => $arrMessage['parent_id'],
                'user_id' => $arrMessage['user_id'],
                'username' => $arrMessage['username'],
                'subject' => $arrMessage['subject'],
                'body' => $arrMessage['body'],
                'timestamp' => $arrMessage['timestamp'],
                'status' => $arrMessage['status']
            ];

            // Send bulk request in batches of 500
            if (count($arrParams['body']) >= 1000) {
                try {
                    $response = $this->m_objClient->bulk($arrParams);
                    if (!isset($response['errors']) || !$response['errors']) {
                        $iSuccessCount += count($arrParams['body']) / 2;
                    }
                } catch (\Exception $e) {
                    error_log('ElasticSearch bulk indexing error: ' . $e->getMessage());
                }
                $arrParams = ['body' => []];
            }
        }

        // Send remaining documents
        if (!empty($arrParams['body'])) {
            try {
                $response = $this->m_objClient->bulk($arrParams);
                if (!isset($response['errors']) || !$response['errors']) {
                    $iSuccessCount += count($arrParams['body']) / 2;
                }
            } catch (\Exception $e) {
                error_log('ElasticSearch bulk indexing error: ' . $e->getMessage());
            }
        }

        return $iSuccessCount;
    }

    /**
     * Check if the search engine is available
     *
     * @return bool True if cluster is reachable
     */
    public function isAvailable(): bool
    {
        try {
            $response = $this->m_objClient->ping();
            return isset($response['acknowledged']) || $response === true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get the type of the search engine
     *
     * @return string Returns "ElasticSearch"
     */
    public function getEngineType(): string
    {
        return 'ElasticSearch';
    }

    /**
     * Build ElasticSearch query with weighted scoring
     *
     * Implements the same weighted ranking as MySQL implementation:
     * - Root subject: 4.0x
     * - Root body: 3.0x
     * - Reply subject: 2.0x
     * - Reply body: 1.0x
     *
     * @param string $sSearchTerm Search term
     * @param string $sUserName Username filter
     * @param array<int> $arrBoardIds Board IDs filter
     * @param int $iSearchDays Days filter
     * @param int $iSearchTimestamp Reference timestamp
     * @param int $iTimeOffset Timezone offset
     * @param int $iCurrentUserId Current user ID
     * @return array<string, mixed> ElasticSearch query
     */
    private function _buildQuery(
        string $sSearchTerm,
        string $sUserName,
        array $arrBoardIds,
        int $iSearchDays,
        int $iSearchTimestamp,
        int $iTimeOffset,
        int $iCurrentUserId
    ): array {

        $arrMust = [];
        $arrFilter = [];

        // Full-text search
        if (!empty($sSearchTerm)) {
            $arrMust[] = [
                'multi_match' => [
                    'query' => $sSearchTerm,
                    'fields' => ['subject^2', 'body'],
                    'type' => 'best_fields',
                    'operator' => 'and'
                ]
            ];
        }

        // Username filter
        if (!empty($sUserName)) {
            $arrFilter[] = [
                'prefix' => [
                    'username' => $sUserName
                ]
            ];
        }

        // Board filter
        if (!empty($arrBoardIds)) {
            $arrFilter[] = [
                'terms' => [
                    'board_id' => $arrBoardIds
                ]
            ];
        }

        // Timespan filter
        if ($iSearchDays > 0) {
            $iMinTimestamp = $iSearchTimestamp - $iSearchDays * 86400 + $iTimeOffset;
            $arrFilter[] = [
                'range' => [
                    'timestamp' => [
                        'gte' => $iMinTimestamp
                    ]
                ]
            ];
        }

        // Status filter: published OR user's drafts
        if ($iCurrentUserId > 0) {
            $arrFilter[] = [
                'bool' => [
                    'should' => [
                        ['term' => ['status' => eMessageStatus::PUBLISHED->value]],
                        [
                            'bool' => [
                                'must' => [
                                    ['term' => ['status' => eMessageStatus::DRAFT->value]],
                                    ['term' => ['user_id' => $iCurrentUserId]]
                                ]
                            ]
                        ]
                    ],
                    'minimum_should_match' => 1
                ]
            ];
        } else {
            $arrFilter[] = [
                'term' => ['status' => eMessageStatus::PUBLISHED->value]
            ];
        }

        // Build function_score query for weighted ranking
        $arrQuery = [
            'function_score' => [
                'query' => [
                    'bool' => [
                        'must' => $arrMust,
                        'filter' => $arrFilter
                    ]
                ],
                'functions' => [
                    // Root message subject: 4.0x weight
                    [
                        'filter' => [
                            'bool' => [
                                'must' => [
                                    ['term' => ['parent_id' => 0]],
                                    ['match' => ['subject' => $sSearchTerm]]
                                ]
                            ]
                        ],
                        'weight' => 4.0
                    ],
                    // Root message body: 3.0x weight
                    [
                        'filter' => [
                            'bool' => [
                                'must' => [
                                    ['term' => ['parent_id' => 0]],
                                    ['match' => ['body' => $sSearchTerm]]
                                ]
                            ]
                        ],
                        'weight' => 3.0
                    ],
                    // Reply subject: 2.0x weight
                    [
                        'filter' => [
                            'bool' => [
                                'must' => [
                                    ['range' => ['parent_id' => ['gt' => 0]]],
                                    ['match' => ['subject' => $sSearchTerm]]
                                ]
                            ]
                        ],
                        'weight' => 2.0
                    ],
                    // Reply body: 1.0x weight (default)
                    [
                        'filter' => [
                            'bool' => [
                                'must' => [
                                    ['range' => ['parent_id' => ['gt' => 0]]],
                                    ['match' => ['body' => $sSearchTerm]]
                                ]
                            ]
                        ],
                        'weight' => 1.0
                    ]
                ],
                'boost_mode' => 'sum'
            ]
        ];

        return $arrQuery;
    }

    /**
     * Create index with proper mapping if it doesn't exist
     *
     * @return void
     */
    private function _createIndexIfNotExists(): void
    {
        try {
            // Check if index exists
            $arrParams = ['index' => $this->m_sIndexName];
            if ($this->m_objClient->indices()->exists($arrParams)) {
                return;
            }

            // Create index with mapping
            $arrParams = [
                'index' => $this->m_sIndexName,
                'body' => [
                    'mappings' => [
                        'properties' => [
                            'thread_id' => ['type' => 'integer'],
                            'board_id' => ['type' => 'integer'],
                            'parent_id' => ['type' => 'integer'],
                            'user_id' => ['type' => 'integer'],
                            'username' => ['type' => 'keyword'],
                            'subject' => ['type' => 'text'],
                            'body' => ['type' => 'text'],
                            'timestamp' => ['type' => 'integer'],
                            'status' => ['type' => 'integer']
                        ]
                    ]
                ]
            ];

            $this->m_objClient->indices()->create($arrParams);

        } catch (\Exception $e) {
            error_log('ElasticSearch index creation error: ' . $e->getMessage());
        }
    }
}
