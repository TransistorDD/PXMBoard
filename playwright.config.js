import { defineConfig, devices } from '@playwright/test';
import path from 'path';
import { fileURLToPath } from 'url';
import { loadE2eDbEnv } from './tests/E2E/fixtures/db-helpers.js';

const __dirname = path.dirname(fileURLToPath(import.meta.url));

// Expose E2E_DB_* environment variables for spec-level DB access.
loadE2eDbEnv();

export default defineConfig({
    testDir: './tests/E2E/specs',
    globalTeardown: './tests/E2E/global-teardown.js',
    outputDir: './reports/playwright-artifacts',
    reporter: [['html', { outputFolder: 'reports/playwright-html' }]],
    // Playwright starts (and owns) a dedicated PHP server on port 8001 that uses
    // the E2E database.  This keeps it completely isolated from the dev server.
    // Env vars from tests/E2E/.env are forwarded so the static PHP config can
    // read them via getenv().
    webServer: {
        command: `PXMBOARD_CONFIG=${path.resolve(__dirname, 'tests/E2E/pxmboard-config.e2e.php')} php -S 127.0.0.1:8001 -t public/`,
        url: 'http://127.0.0.1:8001',
        reuseExistingServer: !process.env.CI,
        stdout: 'ignore',
        stderr: 'pipe',
    },
    use: {
        baseURL: process.env.E2E_BASE_URL ?? 'http://127.0.0.1:8001',
        screenshot: 'only-on-failure',
        trace: 'retain-on-failure',
        // Allow Service Workers so PWA tests can register sw.js
        serviceWorkers: 'allow',
        // --- Debugging helpers (uncomment temporarily, never commit) ---
        // launchOptions: { slowMo: 1500 },   // pause between actions (ms) – use with --headed
        // video: 'on',                        // record video of every test run
    },
    retries: process.env.CI ? 2 : 0,
    // Run one worker at a time so all projects/specs share the E2E database
    // sequentially and DB-state race conditions between parallel workers are
    // impossible.  Override via --workers=N on the command line when needed.
    workers: 1,
    projects: [
        // --- Desktop (Light) ---
        {
            name: 'Desktop Chrome',
            use: { ...devices['Desktop Chrome'] },
        },
        {
            name: 'Desktop Safari',
            use: { ...devices['Desktop Safari'] },
        },

        // --- Desktop (Dark Mode) ---
        {
            name: 'Desktop Chrome Dark',
            use: { ...devices['Desktop Chrome'], colorScheme: 'dark' },
        },
        {
            name: 'Desktop Safari Dark',
            use: { ...devices['Desktop Safari'], colorScheme: 'dark' },
        },

        // --- Mobile iPhone 14 Pro via WebKit (Light + Dark) ---
        {
            name: 'Mobile Safari – iPhone 14 Pro',
            use: { ...devices['iPhone 14 Pro'] },
        },
        {
            name: 'Mobile Safari – iPhone 14 Pro Dark',
            use: { ...devices['iPhone 14 Pro'], colorScheme: 'dark' },
        },

        // --- Mobile Pixel 7 via Chromium (Light + Dark) ---
        {
            name: 'Mobile Chrome – Pixel 7',
            use: { ...devices['Pixel 7'] },
        },
        {
            name: 'Mobile Chrome – Pixel 7 Dark',
            use: { ...devices['Pixel 7'], colorScheme: 'dark' },
        },
    ],
});
