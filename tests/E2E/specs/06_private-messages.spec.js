/**
 * Spec 06 – Private Messages
 *
 * Verifies the PM inbox, outbox, and send-PM workflow.
 * The PM modal is loaded via HTMX into #htmxModal / #htmxModalBody.
 *
 * The seed database contains one unread PM from Webmaster to Tester.
 */

import { test, expect } from '@playwright/test';
import { openDbConnection } from '../fixtures/db-helpers.js';
import { BoardPage } from '../pages/BoardPage.js';

async function loginAs(page, username, password) {
    const board = new BoardPage(page);
    await board.goto();
    await board.login(username, password);
}

test.describe('PM Inbox (as Tester)', () => {
    test.beforeEach(async ({ page }) => {
        // Clean up PMs created by the 'can submit a PM to Tester' test across
        // repeated runs, and reset the unread counter so badge tests stay stable.
        const conn = await openDbConnection();
        try {
            await conn.execute("DELETE FROM pxm_priv_message WHERE p_subject LIKE 'PM E2E%'");
            await conn.execute('UPDATE pxm_user SET u_priv_message_unread_count = 1 WHERE u_id = 2');
        } finally {
            await conn.end();
        }
        await loginAs(page, 'Tester', 'test5678');
    });

    test('PM link is visible for authenticated user', async ({ page }) => {
        const board = new BoardPage(page);
        await expect(board.pmLink).toBeVisible();
    });

    test('inbox shows the seeded unread PM', async ({ page }) => {
        // Open the PM modal via the envelope icon in the header.
        await page.locator('a[href*="mode=privatemessagelist"]').click();

        // Wait for the modal close button to become visible.
        await page.locator('.htmx-close-btn').waitFor({ state: 'visible', timeout: 10000 });

        // The beforeEach cleanup ensures the seed PM is always on page 1.
        await expect(page.locator('#htmxModalBody').getByText('Willkommen beim E2E-Test')).toBeVisible({ timeout: 8000 });
    });

    test('PM unread count badge is displayed', async ({ page }) => {
        // The #pm-badge span is visible (no "hidden" class) when unread count > 0.
        // Seed data sets u_priv_message_unread_count=1 for Tester.
        await expect(page.locator('#pm-badge')).toBeVisible({ timeout: 5000 });
    });
});

/**
 * Open the PM compose form for a specific recipient from within a message view.
 * Flow: board page → load message from recipient → click "Private Nachricht" button
 *       → form loads into #htmxModalBody.
 *
 * @param {import('@playwright/test').Page} page
 * @param {number} boardId
 * @param {number} threadId
 * @param {number} messageId  ID of the message authored by the PM recipient
 */
async function openPmFormForMessageAuthor(page, boardId, threadId, messageId) {
    await page.goto(`/pxmboard.php?mode=board&brdid=${boardId}&thrdid=${threadId}&msgid=${messageId}`);
    // Wait for the message to auto-load via HTMX.
    const pmButton = page.locator('#message-container button[title="Private Nachricht"]');
    await pmButton.waitFor({ state: 'visible', timeout: 10000 });
    await pmButton.click();
    // Wait for the modal and PM form to appear.
    await page.locator('#htmxModal').waitFor({ state: 'visible', timeout: 10000 });
    await page.locator('#htmxModalBody form').waitFor({ state: 'visible', timeout: 8000 });
}

test.describe('Send PM (as Webmaster)', () => {
    // Seed: message 2 in thread 1 is authored by Tester (user id=2).
    const BOARD_ID = 1;
    const THREAD_ID = 1;
    const TESTER_MESSAGE_ID = 2;

    test.beforeEach(async ({ page }) => {
        await loginAs(page, 'Webmaster', 'test1234');
    });

    test('PM compose form is accessible', async ({ page }) => {
        await openPmFormForMessageAuthor(page, BOARD_ID, THREAD_ID, TESTER_MESSAGE_ID);
        await expect(page.locator('#htmxModalBody form')).toBeVisible({ timeout: 5000 });
    });

    test('can submit a PM to Tester', async ({ page }) => {
        await openPmFormForMessageAuthor(page, BOARD_ID, THREAD_ID, TESTER_MESSAGE_ID);

        // The recipient is pre-set via toid in the URL (hidden field).
        // Fill subject and body.
        const pmSubject = `PM E2E ${Date.now()}`;
        await page.locator('#htmxModalBody input[name="subject"]').fill(pmSubject);

        // Tiptap editor inside the modal.
        const editor = page.locator('#htmxModalBody .ProseMirror');
        await editor.click();
        await editor.fill('Test PM from Playwright.');

        await page.locator('#htmxModalBody button[type="submit"], #htmxModalBody input[type="submit"]').first().click();

        // After sending, confirm.tpl loads into #htmxModalBody with the success message.
        await expect(
            page.locator('#htmxModalBody')
        ).toContainText('erfolgreich', { timeout: 10000 });
    });
});

test.describe('PM Outbox', () => {
    test('outbox tab is accessible', async ({ page }) => {
        await loginAs(page, 'Webmaster', 'test1234');
        // Open PM modal via envelope icon, then click Outbox tab.
        await page.locator('a[href*="mode=privatemessagelist"]').click();
        await page.locator('.htmx-close-btn').waitFor({ state: 'visible', timeout: 10000 });
        // Wait for the inbox content (tab nav) to finish loading before clicking the outbox tab.
        await page.locator('#htmxModalBody a[href*="type=outbox"]').waitFor({ state: 'visible', timeout: 8000 });
        await page.locator('#htmxModalBody a[href*="type=outbox"]').click();
        await expect(page.locator('#htmxModalBody')).toContainText('Gesendete Nachrichten', { timeout: 8000 });
    });
});
