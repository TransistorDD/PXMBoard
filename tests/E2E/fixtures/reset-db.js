/**
 * E2E database reset script.
 *
 * Drops and re-creates the E2E database, then imports the seed SQL.
 * Called by every npm test:e2e* script before Playwright runs.
 *
 * Environment variables (all optional, fall back to defaults):
 *   E2E_DB_HOST    – MySQL host          (default: 127.0.0.1)
 *   E2E_DB_PORT    – MySQL TCP port      (default: 3306)
 *   E2E_DB_SOCKET  – Unix socket path    (overrides host/port when set, e.g. /run/mysqld/mysqld.sock)
 *   E2E_DB_USER    – MySQL user          (default: pxmboard)
 *   E2E_DB_PASS    – MySQL password      (default: '')
 *   E2E_DB_NAME    – Database name       (default: pxmboard_e2e)
 */

import { createConnection } from 'mysql2/promise';
import { readFileSync } from 'fs';
import { fileURLToPath } from 'url';
import { dirname, join } from 'path';

const __filename = fileURLToPath(import.meta.url);
const __dirname = dirname(__filename);

const socketPath = process.env.E2E_DB_SOCKET ?? '';
const host = process.env.E2E_DB_HOST ?? '127.0.0.1';
const port = parseInt(process.env.E2E_DB_PORT ?? '3306', 10);
const user = process.env.E2E_DB_USER ?? 'pxmboard';
const pass = process.env.E2E_DB_PASS ?? '';
const dbName = process.env.E2E_DB_NAME ?? 'pxmboard_e2e';

const seedPath = join(__dirname, 'e2e-seed.sql');
const seedSql = readFileSync(seedPath, 'utf8');

async function resetDatabase() {
    // Connect without selecting a database first so we can (re)create it.
    const connOptions = socketPath
        ? { socketPath, user, password: pass, multipleStatements: true }
        : { host, port, user, password: pass, multipleStatements: true };
    const conn = await createConnection(connOptions);

    try {
        console.log(`[E2E] Recreating database "${dbName}" …`);
        await conn.query(`DROP DATABASE IF EXISTS \`${dbName}\``);
        await conn.query(`CREATE DATABASE \`${dbName}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci`);
        await conn.query(`USE \`${dbName}\``);

        console.log('[E2E] Importing seed SQL …');
        await conn.query(seedSql);

        console.log('[E2E] Database reset complete.');
    } finally {
        await conn.end();
    }
}

resetDatabase().catch((err) => {
    console.error('[E2E] Database reset failed:', err.message);
    process.exit(1);
});
