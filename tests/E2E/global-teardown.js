/**
 * Playwright global teardown.
 *
 * Kills any leftover PHP built-in server on port 8001 that may have been
 * started manually or reused by Playwright's webServer option.
 */

import { execSync } from 'child_process';

export default async function globalTeardown() {
    try {
        execSync("pkill -f 'php -S 127.0.0.1:8001' 2>/dev/null || true");
    } catch {
        // ignore – process may already be gone
    }
}
