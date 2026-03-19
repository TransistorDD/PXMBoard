/**
 * Spec 08 – Read Tracking (is_new / is_read)
 *
 * Verifies the server-side read-tracking introduced in Story 006:
 *   – is_new:  (neu) badge in the thread tree for messages posted after the
 *              user's last-login timestamp (frozen in the session at login time,
 *              NOT the constantly-updated last-online timestamp).
 *   – lastnew: red dot in the thread list when a thread contains new messages.
 *   – is_read: htmx-msg-read class and CSS hiding of the (neu) badge after a
 *              message is opened; server-side persistence across page reloads.
 *
 * Seed prerequisites (e2e-seed.sql):
 *   - ReadTester's u_lastonlinetstmp = UNIX_TIMESTAMP() - 86400  (yesterday / 24 h ago)
 *     ReadTester is a dedicated user only used in this spec, so no other spec
 *     ever calls updateLastOnlineTimestamp() for this account.  Tests within
 *     this spec do update it, but m_id=5 is seeded to UNIX_TIMESTAMP() + 86400
 *     (tomorrow), so it always stays newer than any login timestamp written
 *     during the run.
 *   - Messages m_id=1,2,3: posted UNIX_TIMESTAMP()-172800 (2 days ago) → is_new = 0
 *   - Message  m_id=5:     posted UNIX_TIMESTAMP() + 86400 (tomorrow) → always is_new = 1
 *
 * Navigation strategy:
 *   All flows start from the board SPA (mode=board).  Partials
 *   (mode=thread, mode=threadlist, mode=message) are never opened directly
 *   so HTMX / Alpine.js behaviour is exercised end-to-end.
 */

import { test, expect } from '@playwright/test';
import { openDbConnection } from '../fixtures/db-helpers.js';
import { BoardPage } from '../pages/BoardPage.js';
import { ThreadListPage } from '../pages/ThreadListPage.js';

const BOARD_ID = 1;
const READTESTER_USER_ID = 3;

/**
 * Log in as ReadTester, navigate to the board and open "E2E Testthread".
 * ReadTester is the dedicated account for read-tracking tests.
 * Its u_lastonlinetstmp is frozen at seed-load-time minus 24 h when first read,
 * but subsequent logins in the same test run advance it.  m_id=5 is seeded to
 * UNIX_TIMESTAMP() + 86400 (tomorrow) so is_new=1 holds for every login within
 * a single test-suite run.
 *
 * @param {import('@playwright/test').Page} page
 * @returns {Promise<ThreadListPage>}
 */
async function loginAndOpenThread(page) {
    const board = new BoardPage(page);
    await board.goto();
    await board.login('ReadTester', 'read5678');

    const threadList = new ThreadListPage(page);
    await threadList.goto(BOARD_ID);
    await threadList.openThread('E2E Testthread');

    // Ensure the thread tree is fully rendered before tests interact with it.
    // openThread() already waits for #message-container, but we also need
    // #thread-container (the tree) to be populated.
    await page.locator('#thread-container').waitFor({ state: 'visible', timeout: 8000 });

    return threadList;
}

/**
 * Return a Locator for a thread-tree row by message id.
 * Covers both .htmx-thread-root-header (root message) and
 * .htmx-thread-msg-row (reply messages).
 *
 * @param {import('@playwright/test').Page} page
 * @param {number} msgId
 * @returns {import('@playwright/test').Locator}
 */
function treeRow(page, msgId) {
    return page.locator(
        `.htmx-thread-msg-row[data-msgid="${msgId}"], ` +
        `.htmx-thread-root-header[data-msgid="${msgId}"]`
    );
}

// Clear ReadTester's read-tracking state before every test so each test
// starts with a clean slate.  Also reset the thread's last-message timestamp
// to "tomorrow" so that lastnew = 1 holds regardless of messages posted by
// earlier tests (e.g. spec 04).
test.beforeEach(async () => {
    const conn = await openDbConnection();
    try {
        await conn.execute(
            'DELETE FROM pxm_message_read WHERE mr_userid = ?',
            [READTESTER_USER_ID]
        );
        // Keep m_id=5 always in the future so is_new=1 holds regardless of when
        // the seed was originally loaded.
        await conn.execute(
            'UPDATE pxm_message SET m_tstmp = UNIX_TIMESTAMP() + 86400 WHERE m_id = 5',
            []
        );
        await conn.execute(
            'UPDATE pxm_thread SET t_lastmsgtstmp = UNIX_TIMESTAMP() + 86400 WHERE t_id = 1',
            []
        );
    } finally {
        await conn.end();
    }
});

// ============================================================
// is_new – (neu) badge
// ============================================================
test.describe('is_new – (neu) badge in the thread tree', () => {
    test('(neu) badge is visible on a message posted after the last login', async ({ page }) => {
        await loginAndOpenThread(page);

        // m_id=5 was seeded to tomorrow – always newer than ReadTester's last login
        const row = treeRow(page, 5);
        await expect(row).toBeVisible({ timeout: 8000 });
        await expect(row.locator('span.text-accent-danger')).toBeVisible();
    });

    test('(neu) badge is absent on messages posted before the last login', async ({ page }) => {
        await loginAndOpenThread(page);

        // m_id=1 is the root message – posted 2 days ago, before ReadTester's last login
        const row = treeRow(page, 1);
        await expect(row).toBeVisible({ timeout: 8000 });
        await expect(row.locator('span.text-accent-danger')).not.toBeVisible();
    });
});

// ============================================================
// lastnew – red dot in the thread list
// ============================================================
test.describe('lastnew – new-message indicator in the thread list', () => {
    test('red dot is shown on a thread that contains messages newer than the last login', async ({ page }) => {
        const board = new BoardPage(page);
        await board.goto();
        await board.login('ReadTester', 'read5678');

        const threadList = new ThreadListPage(page);
        await threadList.goto(BOARD_ID);

        // Thread 1 contains m_id=5 (posted today) which is newer than ReadTester's last login (yesterday)
        const row = threadList.threadBySubject('E2E Testthread');
        await expect(row).toBeVisible({ timeout: 8000 });
        await expect(row.locator('span[title="Neue Antwort"]')).toBeVisible();
    });
});

// ============================================================
// is_read – read tracking
// ============================================================
test.describe('is_read – read tracking', () => {
    test('clicking a message in the thread tree adds htmx-msg-read (client-side)', async ({ page }) => {
        await loginAndOpenThread(page);

        // m_id=2 is an unread reply; no pxm_message_read entry exists in the fresh seed
        const row = treeRow(page, 2);
        await expect(row).toBeVisible({ timeout: 8000 });
        await expect(row).not.toHaveClass(/htmx-msg-read/);

        // Click the link in the tree → HTMX swaps #message-container + selectMessage() fires
        await row.locator('a[data-msgid="2"]').click();
        await page.locator('#message-container').waitFor({ state: 'visible', timeout: 8000 });

        // selectMessage() immediately adds htmx-msg-read to the row via JS
        await expect(row).toHaveClass(/htmx-msg-read/);
    });

    test('(neu) badge is hidden via CSS after marking the message as read', async ({ page }) => {
        await loginAndOpenThread(page);

        // m_id=5: new message – badge must be visible before clicking
        const row = treeRow(page, 5);
        await expect(row).toBeVisible({ timeout: 8000 });
        await expect(row.locator('span.text-accent-danger')).toBeVisible();

        // Click it → HTMX loads the message + selectMessage() marks the row
        await row.locator('a[data-msgid="5"]').click();
        await page.locator('#message-container').waitFor({ state: 'visible', timeout: 8000 });

        // CSS rule: .htmx-thread-msg-row.htmx-msg-read .htmx-msg-meta .text-accent-danger { display: none }
        await expect(row).toHaveClass(/htmx-msg-read/);
        await expect(row.locator('span.text-accent-danger')).toBeHidden();
    });

    test('read status persists after the thread tree is reloaded from the server', async ({ page }) => {
        const threadList = await loginAndOpenThread(page);

        // Click m_id=3 → HTMX loads it → cActionMessage::performAction() writes
        // a row to pxm_message_read for ReadTester
        const row = treeRow(page, 3);
        await expect(row).toBeVisible({ timeout: 8000 });
        await row.locator('a[data-msgid="3"]').click();
        await page.locator('#message-container').waitFor({ state: 'visible', timeout: 8000 });

        // Navigate away: reload board 1 (full-page load; thread tree disappears)
        await threadList.goto(BOARD_ID);

        // Re-open the same thread → HTMX fetches a fresh thread tree from the server
        await threadList.openThread('E2E Testthread');
        await page.locator('#thread-container').waitFor({ state: 'visible', timeout: 8000 });

        // The server renders is_read=1 for m_id=3 → htmx-msg-read must be present
        await expect(treeRow(page, 3)).toHaveClass(/htmx-msg-read/, { timeout: 8000 });
    });
});
