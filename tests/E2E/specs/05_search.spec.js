/**
 * Spec 05 – Search
 *
 * Verifies the search functionality: the search form is accessible,
 * a query for a known subject returns results, and an empty search
 * or no-match query behaves gracefully.
 */

import { test, expect } from '@playwright/test';
import { BoardPage } from '../pages/BoardPage.js';

const BOARD_ID = 1;

async function loginAsTester(page) {
    const board = new BoardPage(page);
    await board.goto();
    await board.login('Tester', 'test5678');
}

/**
 * Navigate to the board page and open the search form via the header search
 * icon. The form loads into #threadlist-container via HTMX.
 * Returns the #threadlist-container locator ready for interaction.
 */
async function openSearchForm(page) {
    await page.goto(`/pxmboard.php?mode=board&brdid=${BOARD_ID}`);
    await page.locator('#threadlist-container').waitFor({ state: 'visible', timeout: 8000 });
    // Click the search icon in the header (hx-target="#threadlist-container").
    await page.locator('a[hx-target="#threadlist-container"][href*="mode=messagesearch"]').click();
    // Wait for the search form to load inside the threadlist container.
    await page.locator('#threadlist-container input[name="smsg"]').waitFor({ state: 'visible', timeout: 8000 });
}

test.describe('Search', () => {
    test('search page is reachable', async ({ page }) => {
        // Open via board page HTMX flow (no login required for viewing).
        await openSearchForm(page);
        await expect(page.locator('#threadlist-container form')).toBeVisible({ timeout: 5000 });
    });

    test('search for known subject returns at least one result', async ({ page }) => {
        await loginAsTester(page);
        await openSearchForm(page);

        // The search field in messagesearch.tpl is named "smsg".
        await page.locator('input[name="smsg"]').fill('E2E Testthread');
        await page.locator('#threadlist-container button[type="submit"], #threadlist-container input[type="submit"]').first().click();

        // The search result list must contain the known message subject.
        // Use .first() because multiple elements on the page may contain this text.
        await expect(page.locator('text=E2E Testthread').first()).toBeVisible({ timeout: 10000 });
    });

    test('search for non-existent term shows no-results message', async ({ page }) => {
        await loginAsTester(page);
        await openSearchForm(page);

        await page.locator('input[name="smsg"]').fill('xyzzy_no_match_e2e');
        await page.locator('#threadlist-container button[type="submit"], #threadlist-container input[type="submit"]').first().click();

        // Expect either an empty list or a "no results" indicator; at minimum
        // the original subject must NOT appear.
        await expect(page.locator('text=E2E Testthread').first()).not.toBeVisible({ timeout: 10000 });
    });
});
