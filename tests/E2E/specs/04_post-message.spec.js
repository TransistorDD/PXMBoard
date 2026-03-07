/**
 * Spec 04 – Post a message
 *
 * Verifies that an authenticated user can compose and publish a new message,
 * and that the post appears in the thread list afterwards.
 *
 * Also covers the draft workflow.
 */

import { test, expect } from '@playwright/test';
import { BoardPage } from '../pages/BoardPage.js';
import { MessagePage } from '../pages/MessagePage.js';
import { ThreadListPage } from '../pages/ThreadListPage.js';

const BOARD_ID = 1;
const THREAD_ID = 1; // seed: thread 1 is in board 1

// Helper: log in as Tester before each test
async function loginAsTester(page) {
    const board = new BoardPage(page);
    await board.goto();
    await board.login('Tester', 'test5678');
}

test.describe('Post new root message', () => {
    test('form fields are visible on the message form', async ({ page }) => {
        await loginAsTester(page);
        const msg = new MessagePage(page);
        await msg.gotoNewMessage(BOARD_ID);

        await expect(msg.subjectInput).toBeVisible();
        await expect(msg.editorContent).toBeVisible();
        await expect(msg.submitButton).toBeVisible();
    });

    test('submitting a message creates a new thread', async ({ page }) => {
        await loginAsTester(page);

        const subject = `E2E Post ${Date.now()}`;
        const msg = new MessagePage(page);
        await msg.gotoNewMessage(BOARD_ID);
        await msg.fillAndSubmit(subject, 'This message was created by a Playwright E2E test.');

        // After posting we should end up in the board/thread view and the
        // new subject must be visible somewhere on the page.
        await expect(page.locator(`text=${subject}`)).toBeVisible({ timeout: 10000 });
    });

    test('new thread appears in the thread list', async ({ page }) => {
        await loginAsTester(page);

        const subject = `E2E ThreadList ${Date.now()}`;
        const msg = new MessagePage(page);
        await msg.gotoNewMessage(BOARD_ID);
        await msg.fillAndSubmit(subject, 'Thread list visibility check.');

        // Navigate to the thread list and verify the new entry.
        const tl = new ThreadListPage(page);
        await tl.goto(BOARD_ID);
        await expect(tl.threadBySubject(subject)).toBeVisible({ timeout: 10000 });
    });
});

test.describe('Reply to an existing message', () => {
    test('reply form is pre-filled with quoted subject', async ({ page }) => {
        await loginAsTester(page);
        const msg = new MessagePage(page);
        // Message 1 is the root of thread 1 in board 1.
        await msg.gotoReply(BOARD_ID, THREAD_ID, 1);

        // Subject field should contain the quoted value (e.g. "Re: E2E Testthread")
        const subject = await msg.subjectInput.inputValue();
        expect(subject).toContain('E2E Testthread');
    });

    test('submitting a reply links to the parent thread', async ({ page }) => {
        await loginAsTester(page);

        const subject = `E2E Reply ${Date.now()}`;
        const msg = new MessagePage(page);
        await msg.gotoReply(BOARD_ID, THREAD_ID, 1);
        await msg.subjectInput.fill(subject);
        await msg.editorContent.click();
        await msg.editorContent.fill('A reply created by Playwright.');
        await msg.submitButton.click();

        // confirm.tpl loads into #message-container after a successful post.
        // Click the link to navigate to the saved reply.
        await page.locator('a:has-text("gespeicherten Nachricht")').click();
        await expect(page.locator('#message-container')).toContainText(subject, { timeout: 10000 });
    });
});

test.describe('Draft workflow', () => {
    test('"Entwurf speichern" button is visible on the message form', async ({ page }) => {
        await loginAsTester(page);
        const msg = new MessagePage(page);
        await msg.gotoNewMessage(BOARD_ID);
        await expect(msg.draftButton).toBeVisible();
    });
});

test.describe('Guest / unauthenticated', () => {
    test('unauthenticated user sees the QuickPost form with nick/password fields', async ({ page }) => {
        // PXMBoard allows guest posting on PUBLIC boards via the QuickPost feature.
        // Navigating as a guest opens the form with nick and password input fields.
        const board = new BoardPage(page);
        await board.goto();
        // Open board page → click "Neuer Beitrag" to load the form via HTMX.
        const msg = new MessagePage(page);
        await msg.gotoNewMessage(BOARD_ID);
        // Guest form shows nick/password fields (QuickPost).
        await expect(page.locator('input[name="nick"]')).toBeVisible({ timeout: 5000 });
        await expect(page.locator('input[name="pass"]')).toBeVisible({ timeout: 5000 });
    });
});
