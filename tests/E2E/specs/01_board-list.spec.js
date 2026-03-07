/**
 * Spec 01 – Board list
 *
 * Verifies that the board list page renders correctly for both anonymous
 * and authenticated users across all configured Playwright projects
 * (Desktop/Mobile × Light/Dark).
 */

import { test, expect } from '@playwright/test';
import { BoardPage } from '../pages/BoardPage.js';

test.describe('Board list (anonymous)', () => {
    test('renders login form', async ({ page }) => {
        const board = new BoardPage(page);
        await board.goto();

        await expect(board.usernameInput).toBeVisible();
        await expect(board.passwordInput).toBeVisible();
        await expect(board.loginButton).toBeVisible();
    });

    test('shows "Registrieren" and "Passwort vergessen" links', async ({ page }) => {
        const board = new BoardPage(page);
        await board.goto();

        await expect(board.registerLink).toBeVisible();
        await expect(board.forgotPwLink).toBeVisible();
    });

    test('lists at least the two E2E seed boards', async ({ page }) => {
        const board = new BoardPage(page);
        await board.goto();

        await expect(board.boardByName('Test')).toBeVisible();
        await expect(board.boardByName('Test2')).toBeVisible();
    });

    test('has correct page title', async ({ page }) => {
        await page.goto('/pxmboard.php');
        await expect(page).toHaveTitle(/.+/); // Any non-empty title
    });
});

test.describe('Board list (authenticated as Tester)', () => {
    test.beforeEach(async ({ page }) => {
        const board = new BoardPage(page);
        await board.goto();
        await board.login('Tester', 'test5678');
    });

    test('shows welcome text with username', async ({ page }) => {
        const board = new BoardPage(page);
        await expect(board.welcomeText('Tester')).toBeVisible();
    });

    test('logout link is in the DOM (inside the user dropdown)', async ({ page }) => {
        const board = new BoardPage(page);
        // The logout link lives inside an Alpine.js dropdown that starts closed,
        // so it is present in the DOM but not visible until the dropdown is opened.
        await expect(board.logoutLink).toBeAttached();
    });

    test('login form is hidden after login', async ({ page }) => {
        const board = new BoardPage(page);
        await expect(board.usernameInput).not.toBeVisible();
    });
});

test.describe('Board list (dark-mode appearance)', () => {
    test('html element carries data-theme attribute', async ({ page }) => {
        await page.goto('/pxmboard.php');
        const root = page.locator('html');
        await expect(root).toHaveAttribute('data-theme', /light|dark/);
    });
});
