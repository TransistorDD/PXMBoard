/**
 * Page Object Model – Thread List.
 *
 * After a board is opened, HTMX loads the thread list into the
 * `#threadlist-container` div.  This POM scopes all locators to that
 * container so they do not accidentally match other parts of the page.
 */
export class ThreadListPage {
    /**
     * @param {import('@playwright/test').Page} page
     */
    constructor(page) {
        this.page = page;

        // The HTMX target container
        this.container = page.locator('#threadlist-container');

        // Individual thread rows inside the container
        this.threadRows = this.container.locator('div.htmx-thread-row');

        // Search form / toolbar
        this.searchIcon = page.locator('[data-action="search"]').first();
    }

    /**
     * Navigate to the thread list of a board and wait for content.
     *
     * @param {number} boardId
     */
    async goto(boardId) {
        await this.page.goto(`/pxmboard.php?mode=board&brdid=${boardId}`);
        await this.container.waitFor({ state: 'visible', timeout: 10000 });
        await this.threadRows.first().waitFor({ state: 'visible', timeout: 10000 });
    }

    /**
     * Get a thread row Locator by its subject text.
     *
     * @param {string} subject
     * @returns {import('@playwright/test').Locator}
     */
    threadBySubject(subject) {
        return this.container.locator('div.htmx-thread-row', { hasText: subject });
    }

    /**
     * Get the subject link Locator inside a specific thread row.
     *
     * @param {import('@playwright/test').Locator} row
     * @returns {import('@playwright/test').Locator}
     */
    subjectLink(row) {
        return row.locator('a.htmx-col-subject');
    }

    /**
     * Click a thread's subject link and wait for the message container to load.
     *
     * @param {string} subject
     */
    async openThread(subject) {
        const row = this.threadBySubject(subject);
        const link = this.subjectLink(row);
        await link.click();
        await this.page.locator('#message-container').waitFor({ state: 'visible', timeout: 10000 });
    }

    /** Return the number of visible thread rows. */
    async count() {
        return this.threadRows.count();
    }
}
