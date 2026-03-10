<?php

/**
 * PXMBoard E2E test configuration (static, committed).
 *
 * Reads database credentials from environment variables set by the Playwright
 * test infrastructure.  Developers configure credentials in tests/E2E/.env —
 * this file does not need to be edited.
 *
 * @author Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright Torsten Rentsch 2001 - 2026
 */

return [
    'database' => [
        'type' => 'MySql',
        'host' => getenv('E2E_DB_HOST') ?: 'localhost',
        'user' => getenv('E2E_DB_USER') ?: 'pxmboard',
        'pass' => getenv('E2E_DB_PASS') ?: '',
        'name' => getenv('E2E_DB_NAME') ?: 'pxmboard_e2e',
    ],
    'template_types' => ['Smarty'],
    'search_engine'  => ['type' => 'MySql'],
    'session_name'   => 'brdsid',
];
