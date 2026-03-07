/**
 * Spec 03 – Authentication
 *
 * Covers login, logout, and basic access-control checks.
 */

import { test, expect } from '@playwright/test';
import { BoardPage } from '../pages/BoardPage.js';

test.describe('Login', () => {
    test('valid credentials log the user in', async ({ page }) => {
        const board = new BoardPage(page);
        await board.goto();
        await board.login('Tester', 'test5678');

        await expect(board.welcomeText('Tester')).toBeVisible();
        // Logout link lives inside the avatar dropdown; check it is in the DOM.
        await expect(board.logoutLink).toBeAttached();
    });

    test('admin login works', async ({ page }) => {
        const board = new BoardPage(page);
        await board.goto();
        await board.login('Webmaster', 'test1234');

        await expect(board.welcomeText('Webmaster')).toBeVisible();
    });

    test('invalid password stays on login page', async ({ page }) => {
        const board = new BoardPage(page);
        await board.goto();
        await board.usernameInput.fill('Tester');
        await board.passwordInput.fill('wrongpassword');
        await board.loginButton.click();

        // The login form must still be visible after a failed attempt.
        await expect(board.usernameInput).toBeVisible({ timeout: 5000 });
        await expect(board.welcomeText('Tester')).not.toBeVisible();
    });

    test('unknown username stays on login page', async ({ page }) => {
        const board = new BoardPage(page);
        await board.goto();
        await board.usernameInput.fill('NonExisting');
        await board.passwordInput.fill('test1234');
        await board.loginButton.click();

        await expect(board.usernameInput).toBeVisible({ timeout: 5000 });
    });
});

test.describe('Logout', () => {
    test('logout returns to anonymous board list', async ({ page }) => {
        const board = new BoardPage(page);
        await board.goto();
        await board.login('Tester', 'test5678');

        await board.logout();

        await expect(board.usernameInput).toBeVisible();
        await expect(board.welcomeText('Tester')).not.toBeVisible();
    });
});

test.describe('Stay logged in (remember-me)', () => {
    test('"Dauerhaft einloggen" checkbox is visible on login form', async ({ page }) => {
        const board = new BoardPage(page);
        await board.goto();
        // The checkbox uses a custom toggle-switch; the native <input> is CSS-hidden.
        await expect(board.stayLoggedIn).toBeAttached();
    });
});

test.describe('Access control', () => {
    test('unauthenticated access to admin panel redirects or shows error', async ({ page }) => {
        await page.goto('/pxmboard.php?mode=admConfig');
        // Expect either a redirect back to the board list or an error message –
        // not a 200 response with admin content visible to guests.
        const body = page.locator('body');
        await expect(body).not.toContainText('Konfiguration speichern', { timeout: 5000 });
    });
});
