/**
 * Page Object Model – Board List / Login page (pxmboard.php default).
 *
 * This is the application's entry point.  When no user is logged in it shows
 * the board list together with the login form.  After a successful login the
 * page reloads in authenticated state.
 */
export class BoardPage {
    /**
     * @param {import('@playwright/test').Page} page
     */
    constructor(page) {
        this.page = page;

        // Login form
        this.usernameInput = page.locator('input[name="username"]');
        this.passwordInput = page.locator('input[name="password"]');
        this.stayLoggedIn = page.locator('input[name="staylogedin"]');
        this.loginButton = page.locator('button[type="submit"]');

        // Board links in the list
        this.boardLinks = page.locator('a[href*="mode=board&brdid="]');

        // Registration / password-recovery links
        this.registerLink = page.locator('a', { hasText: 'Registrieren' });
        this.forgotPwLink = page.locator('a[href*="mode=usersendpwd"], a[hx-get*="mode=usersendpwd"]');

        // Authenticated-state indicators
        this.logoutLink = page.locator('a[href*="mode=logout"]');
        this.pmLink = page.locator('a[href*="mode=privatemessagelist"]');
    }

    /** Navigate to the board list. */
    async goto() {
        await this.page.goto('/pxmboard.php');
    }

    /**
     * Submit the login form and wait for the authenticated view.
     *
     * @param {string} username
     * @param {string} password
     */
    async login(username, password) {
        await this.usernameInput.fill(username);
        await this.passwordInput.fill(password);
        await this.loginButton.click();
        // The login form is only rendered when logged out ({if $config.logedin == 0}).
        // Waiting for it to detach from the DOM is a reliable login-success indicator.
        await this.usernameInput.waitFor({ state: 'detached', timeout: 8000 });
    }

    /** Click the logout link and wait for the login form to reappear. */
    async logout() {
        // The logout link lives inside the user avatar dropdown — open it first.
        await this.page.locator('header nav .relative').last().locator('button').first().click();
        await this.logoutLink.click();
        await this.usernameInput.waitFor({ state: 'visible', timeout: 5000 });
    }

    /**
     * Navigate to a specific board by its ID.
     *
     * @param {number} boardId
     */
    async openBoard(boardId) {
        await this.page.goto(`/pxmboard.php?mode=board&brdid=${boardId}`);
        // Wait for the thread list container to receive its HTMX-loaded content.
        await this.page.locator('#threadlist-container').waitFor({ state: 'visible', timeout: 10000 });
    }

    /**
     * Return the welcome text element for a given username.
     *
     * @param {string} username
     * @returns {import('@playwright/test').Locator}
     */
    welcomeText(username) {
        // The header renders "Herzlich Willkommen <username>" only when logged in.
        return this.page.locator('div', { hasText: new RegExp(`Herzlich Willkommen ${username}`) });
    }

    /**
     * Return the Locator for a board link by board name.
     *
     * @param {string} name
     * @returns {import('@playwright/test').Locator}
     */
    boardByName(name) {
        return this.page.locator('a.text-link', { hasText: new RegExp(`^${name}$`) });
    }
}
