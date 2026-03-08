<?php

/**
 * Seeder configuration
 *
 * Copy DB credentials from config/pxmboard-config.php.
 * board_ids: At least one board must exist in pxm_board (admin panel).
 */
return [

    // -------------------------------------------------------------------------
    // Database connection
    // -------------------------------------------------------------------------
    'db' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'name' => 'pxmboard',
        'user' => 'pxmboard',
        'pass' => '',
    ],

    // -------------------------------------------------------------------------
    // Target sizes
    // -------------------------------------------------------------------------
    'seed' => [
        'user_count'              => 10_000,
        'thread_count'            => 18_000,
        'messages_per_thread_min' => 300,
        'messages_per_thread_max' => 600,
        'board_ids'               => [1],           // Must exist in pxm_board
        'start_timestamp'         => 0,             // 0 = defaults to strtotime('-5 years')
    ],

    // -------------------------------------------------------------------------
    // Read pattern distribution (sum of _pct values must equal 100)
    // -------------------------------------------------------------------------
    'read_pattern' => [
        'heavy_pct'               => 30,   // % of users: reads many complete threads
        'moderate_pct'            => 50,   // % of users: reads some threads + individual messages
        'lurker_pct'              => 20,   // % of users: sporadic individual messages

        'heavy_thread_count'      => 10,   // Threads fully read by heavy readers
        'moderate_thread_count'   => 3,    // Threads fully read by moderate users
        'moderate_extra_messages' => 60,   // Additional random individual messages (moderate)
        'lurker_message_count'    => 200,  // Number of random messages (lurker)
    ],

    // -------------------------------------------------------------------------
    // Batch processing
    // -------------------------------------------------------------------------
    'batch' => [
        'flush_size'   => 100_000,   // Rows per flush cycle (CSV load or INSERT)
        'insert_chunk' => 1_000,     // Rows per INSERT statement (fallback mode)
        'commit_every' => 5,         // COMMIT after N flush cycles
    ],

    // -------------------------------------------------------------------------
    // Internal file paths (do not change)
    // -------------------------------------------------------------------------
    'progress_file'      => __DIR__ . '/progress.json',
    'thread_ranges_file' => __DIR__ . '/thread_ranges.csv',
];
