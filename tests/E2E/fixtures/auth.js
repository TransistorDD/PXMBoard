/**
 * Authentication helpers for Playwright E2E tests.
 *
 * Provides a login fixture that stores the authenticated session in
 * `storageState` so individual tests can skip the login flow.
 *
 * Usage in a spec file:
 *
 *   import { test, expect } from '../fixtures/auth.js';
 *
 *   test('protected page', async ({ loggedInPage }) => {
 *     await loggedInPage.goto('/pxmboard.php?mode=board&brdid=1');
 *     …
 *   });
 */

import { test as base } from '@playwright/test';

/** Credentials for the admin test account. */
export const ADMIN = { username: 'Webmaster', password: 'test1234' };

/** Credentials for the regular user test account. */
export const USER = { username: 'Tester', password: 'test5678' };

/**
 * Performs a UI login and returns the page that is now authenticated.
 *
 * @param {import('@playwright/test').Page} page
 * @param {{ username: string, password: string }} credentials
 */
export async function loginViaUi(page, { username, password }) {
    await page.goto('/pxmboard.php');
    await page.locator('input[name="username"]').fill(username);
    await page.locator('input[name="password"]').fill(password);
    await page.locator('button[type="submit"]').click();
    // Wait until the login form detaches (it is only rendered when logged out).
    await page.locator('input[name="username"]').waitFor({ state: 'detached', timeout: 8000 });
}

/**
 * Extended test fixture that adds a `loggedInPage` (authenticated as the
 * regular user "Tester") and an `adminPage` (authenticated as "Webmaster").
 */
export const test = base.extend({
    /** Page authenticated as the regular user (Tester). */
    loggedInPage: async ({ page }, use) => {
        await loginViaUi(page, USER);
        await use(page);
    },

    /** Page authenticated as the admin user (Webmaster). */
    adminPage: async ({ page }, use) => {
        await loginViaUi(page, ADMIN);
        await use(page);
    },
});

export { expect } from '@playwright/test';
