<?php
/**
 * Base test case class for PXMBoard tests
 * Provides superglobal helpers for all test types.
 * Does NOT set up any database connection; use IntegrationTestCase for DB access.
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
declare(strict_types=1);

namespace PXMBoard\Tests\TestCase;

use PHPUnit\Framework\TestCase;

/**
 * Base test case class for PXMBoard tests.
 *
 * Hierarchy:
 *   PxmTestCase (superglobal helpers; no DB)
 *     └── IntegrationTestCase (real DB, transaction rollback, fixture helpers)
 *           └── ActionTestCase (cConfig configured for real test skins)
 *
 * Unit tests (Parser, Enum, Validation) extend PxmTestCase directly.
 */
abstract class PxmTestCase extends TestCase
{
    /**
     * Reset superglobals before each test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $_POST    = [];
        $_GET     = [];
        $_SESSION = [];
    }

    /**
     * Clear superglobals after each test.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        $_POST    = [];
        $_GET     = [];
        $_SESSION = [];

        parent::tearDown();
    }

    /**
     * Set POST data for testing form submissions.
     *
     * @param array<string,mixed> $data Associative array of POST data
     * @return void
     */
    protected function setPostData(array $data): void
    {
        $_POST = $data;
    }

    /**
     * Set GET data for testing query strings.
     *
     * @param array<string,mixed> $data Associative array of GET data
     * @return void
     */
    protected function setGetData(array $data): void
    {
        $_GET = $data;
    }
}
