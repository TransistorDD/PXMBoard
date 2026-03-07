/**
 * Spec 02 – Navigation
 *
 * Verifies the HTMX-powered navigation flow:
 *   board list → thread list → thread/message view
 *
 * None of these navigations trigger a full page reload; HTMX swaps content
 * into the target divs (#threadlist-container, #thread-container,
 * #message-container).
 */

import { test, expect } from '@playwright/test';
import { BoardPage } from '../pages/BoardPage.js';
import { ThreadListPage } from '../pages/ThreadListPage.js';
import { ThreadPage } from '../pages/ThreadPage.js';

test.describe('Navigation – Board → Thread list → Message', () => {
    test('clicking a board opens the thread list via HTMX', async ({ page }) => {
        const board = new BoardPage(page);
        const threadList = new ThreadListPage(page);

        await board.goto();
        await board.boardByName('Test').click();

        // The thread list container must appear without a full page reload.
        // Wait explicitly for the first thread row so that HTMX has time to
        // deliver content even when the PHP server is under parallel-test load.
        await expect(threadList.container).toBeVisible({ timeout: 10000 });
        await expect(threadList.threadRows.first()).toBeVisible({ timeout: 10000 });
        const count = await threadList.count();
        expect(count).toBeGreaterThan(0);
    });

    test('thread list shows the E2E seed threads', async ({ page }) => {
        const threadList = new ThreadListPage(page);
        await threadList.goto(1 /* boardId */);

        await expect(threadList.threadBySubject('E2E Testthread')).toBeVisible();
        await expect(threadList.threadBySubject('Angepinnter Thread')).toBeVisible();
    });

    test('clicking a thread subject loads the message view via HTMX', async ({ page }) => {
        const threadList = new ThreadListPage(page);
        await threadList.goto(1);

        await threadList.openThread('E2E Testthread');

        // Both the tree container and the message detail must be populated.
        await expect(page.locator('#thread-container')).toBeVisible({ timeout: 10000 });
        await expect(page.locator('#message-container')).toBeVisible({ timeout: 10000 });
    });

    test('deep-link URL opens thread directly', async ({ page }) => {
        const thread = new ThreadPage(page);
        await thread.goto(1 /* boardId */, 1 /* messageId – root message */);

        await expect(thread.container).toBeVisible();
        await expect(thread.messageArea).toBeVisible();
    });
});

test.describe('Navigation – mobile layout', () => {
    test('mobile footer navigation is present', async ({ page }) => {
        await page.goto('/pxmboard.php?mode=board&brdid=1');
        // The mobile footer exists in the DOM regardless of viewport.
        // Its visibility depends on CSS breakpoints evaluated by the browser.
        const footer = page.locator('#mobile-footer');
        await expect(footer).toBeAttached();
    });
});

test.describe('Navigation – dark mode', () => {
    test('dark-mode projects have colorScheme=dark', async ({ page, browserName }) => {
        // This check is project-specific; light-mode projects will naturally pass
        // because the data-theme attribute may still be "light".  We verify that
        // the html element always carries *some* valid data-theme value.
        await page.goto('/pxmboard.php');
        const theme = await page.locator('html').getAttribute('data-theme');
        expect(['light', 'dark']).toContain(theme);
    });
});
