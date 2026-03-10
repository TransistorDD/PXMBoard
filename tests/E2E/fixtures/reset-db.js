/**
 * E2E database reset script.
 *
 * Drops and re-creates the E2E database, then imports the seed SQL.
 * Called by every npm test:e2e* script before Playwright runs.
 *
 * Credentials are read from tests/E2E/.env via the shared loadE2eDbEnv()
 * helper.  Environment variables can override .env values for CI pipelines.
 */

import { readFileSync } from 'fs';
import { fileURLToPath } from 'url';
import { dirname, join } from 'path';
import { loadE2eDbEnv, openDbConnection } from './db-helpers.js';

const __dirname = dirname(fileURLToPath(import.meta.url));

// Parse PHP config and populate E2E_DB_* env vars before connecting.
loadE2eDbEnv();

const dbName = process.env.E2E_DB_NAME ?? 'pxmboard_e2e';
const seedPath = join(__dirname, 'e2e-seed.sql');
const seedSql = readFileSync(seedPath, 'utf8');

async function resetDatabase() {
    const conn = await openDbConnection({ database: false, multipleStatements: true });

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
