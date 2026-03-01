<?php
/**
 * PXMBoard runtime configuration
 *
 * Copy this file to pxmboard-config.php and fill in your values.
 * This file must NOT be accessible via HTTP (it resides outside public/).
 *
 * @author Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright Torsten Rentsch 2001 - 2026
 */

return [

    // -------------------------------------------------------------------------
    // Database configuration
    // -------------------------------------------------------------------------
    'database' => [
        'type' => 'MySql',               // MySql, PostgreSql (experimental)
        'host' => 'localhost',
        'user' => 'pxmboard',
        'pass' => 'your-password-here',
        'name' => 'pxmboard',
    ],

    // -------------------------------------------------------------------------
    // Template engine configuration
    // -------------------------------------------------------------------------
    'template_types' => ['Smarty'],      // Smarty, Xslt

    // -------------------------------------------------------------------------
    // Search engine configuration (optional – defaults to MySQL FULLTEXT)
    // -------------------------------------------------------------------------
    'search_engine' => [
        'type' => 'MySql',
    ],
    // ElasticSearch (optional):
    // 'search_engine' => [
    //     'type'    => 'ElasticSearch',
    //     'host'    => 'https://localhost:9200',
    //     'index'   => 'pxmboard_messages',
    //     'api_key' => '',   // optional
    // ],

    // -------------------------------------------------------------------------
    // Session configuration
    // -------------------------------------------------------------------------
    'session_name' => 'brdsid',

];
