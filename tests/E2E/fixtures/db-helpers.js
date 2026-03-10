/**
 * Shared E2E database helpers.
 *
 * Provides a single source of truth for:
 *   – loadE2eDbEnv()     parse tests/E2E/.env and set E2E_DB_* env vars
 *   – openDbConnection() return a mysql2 connection using those env vars
 *
 * Used by playwright.config.js (env setup), reset-db.js (pre-run reset),
 * and individual spec files that need direct DB access in beforeEach hooks.
 *
 * @module db-helpers
 */

import { existsSync, readFileSync } from 'fs';
import { createConnection } from 'mysql2/promise';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));

/** Absolute path to the E2E test root (one level up from fixtures/). */
const E2E_ROOT = path.resolve(__dirname, '..');

/**
 * Parse a .env file and return a key-value map.
 * Ignores blank lines and comments (#).  Strips optional surrounding quotes.
 *
 * @param {string} filePath
 * @returns {Record<string, string>}
 */
function parseDotenv(filePath) {
    const vars = {};
    const lines = readFileSync(filePath, 'utf8').split('\n');
    for (const line of lines) {
        const trimmed = line.trim();
        if (!trimmed || trimmed.startsWith('#')) continue;
        const eqIndex = trimmed.indexOf('=');
        if (eqIndex === -1) continue;
        const key = trimmed.slice(0, eqIndex).trim();
        let value = trimmed.slice(eqIndex + 1).trim();
        // Strip matching quotes
        if ((value.startsWith('"') && value.endsWith('"')) ||
            (value.startsWith("'") && value.endsWith("'"))) {
            value = value.slice(1, -1);
        }
        vars[key] = value;
    }
    return vars;
}

/**
 * Read tests/E2E/.env and expose the database credentials as E2E_DB_*
 * environment variables.
 *
 * Already-set env vars take precedence so that CI pipelines can override
 * values without touching the .env file.
 *
 * This function is idempotent – calling it multiple times is harmless.
 */
export function loadE2eDbEnv() {
    const envPath = path.resolve(E2E_ROOT, '.env');
    if (!existsSync(envPath)) return;

    const vars = parseDotenv(envPath);

    // Only set if not already provided (CI overrides take precedence)
    for (const key of Object.keys(vars)) {
        if (!process.env[key]) {
            process.env[key] = vars[key];
        }
    }

    // On Linux, MariaDB/MySQL typically listens on a Unix socket when
    // host=localhost.  Node's mysql2 needs an explicit socketPath.
    if (!process.env.E2E_DB_SOCKET) {
        const socketCandidates = ['/run/mysqld/mysqld.sock', '/var/run/mysqld/mysqld.sock', '/tmp/mysql.sock'];
        const detectedSocket = socketCandidates.find(p => existsSync(p));
        if (detectedSocket && (process.env.E2E_DB_HOST === 'localhost' || !process.env.E2E_DB_HOST)) {
            process.env.E2E_DB_SOCKET = detectedSocket;
        }
    }
}

/**
 * Return a mysql2/promise connection using the E2E_DB_* environment variables.
 *
 * Callers MUST call `conn.end()` when done.
 *
 * @param {object} [options]
 * @param {boolean} [options.database=true] Whether to select the E2E database.
 *   Pass `false` when you need to DROP / CREATE the database itself.
 * @param {boolean} [options.multipleStatements=false] Allow multi-statement queries.
 * @returns {Promise<import('mysql2/promise').Connection>}
 */
export async function openDbConnection({ database = true, multipleStatements = false } = {}) {
    const socketPath = process.env.E2E_DB_SOCKET ?? '';
    const base = {
        user: process.env.E2E_DB_USER ?? 'pxmboard',
        password: process.env.E2E_DB_PASS ?? '',
        multipleStatements,
    };
    if (database) {
        base.database = process.env.E2E_DB_NAME ?? 'pxmboard_e2e';
    }
    const connOptions = socketPath
        ? { socketPath, ...base }
        : {
            host: process.env.E2E_DB_HOST ?? '127.0.0.1',
            port: parseInt(process.env.E2E_DB_PORT ?? '3306', 10),
            ...base
        };
    return createConnection(connOptions);
}
