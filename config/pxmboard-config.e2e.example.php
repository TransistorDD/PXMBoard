<?php
/**
 * PXMBoard E2E test configuration
 *
 * Copy this file to pxmboard-config.e2e.php and fill in your E2E DB credentials.
 * Start the PHP dev server for E2E testing with:
 *
 *   PXMBOARD_CONFIG=config/pxmboard-config.e2e.php php -S 127.0.0.1:8000 -t public/
 *
 * This file must NOT be accessible via HTTP (it resides outside public/).
 * pxmboard-config.e2e.php is excluded from version control (see .gitignore).
 *
 * @author Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright Torsten Rentsch 2001 - 2026
 */

return [

    // -------------------------------------------------------------------------
    // Database configuration (dedicated E2E database, separate from production
    // and pxmboard_test used by PHPUnit integration tests)
    // -------------------------------------------------------------------------
    'database' => [
        'type' => 'MySql',
        'host' => 'localhost',
        'user' => 'pxmboard_e2e',
        'pass' => 'your-e2e-password-here',
        'name' => 'pxmboard_e2e',
    ],

    // -------------------------------------------------------------------------
    // Template engine configuration
    // -------------------------------------------------------------------------
    'template_types' => ['Smarty'],

    // -------------------------------------------------------------------------
    // Search engine configuration
    // -------------------------------------------------------------------------
    'search_engine' => [
        'type' => 'MySql',
    ],

    // -------------------------------------------------------------------------
    // Session configuration
    // -------------------------------------------------------------------------
    'session_name' => 'brdsid',

];
