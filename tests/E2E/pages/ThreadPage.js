/**
 * Page Object Model – Thread Tree.
 *
 * When a thread is opened, HTMX loads the tree view into `#thread-container`.
 * The individual message body is loaded separately into `#message-container`.
 */
export class ThreadPage {
    /**
     * @param {import('@playwright/test').Page} page
     */
    constructor(page) {
        this.page = page;

        // Tree container populated by HTMX
        this.container = page.locator('#thread-container');

        // Message detail container (separate HTMX target)
        this.messageArea = page.locator('#message-container');
    }

    /**
     * Navigate directly to a thread and wait for both containers.
     *
     * @param {number} boardId
     * @param {number} messageId
     */
    async goto(boardId, messageId) {
        await this.page.goto(`/pxmboard.php?mode=message&brdid=${boardId}&msgid=${messageId}`);
        await this.container.waitFor({ state: 'visible', timeout: 10000 });
        await this.messageArea.waitFor({ state: 'visible', timeout: 10000 });
    }

    /**
     * Click a message node in the thread tree by its subject text.
     *
     * @param {string} subject
     */
    async clickMessage(subject) {
        await this.container.locator('a', { hasText: subject }).first().click();
        await this.messageArea.waitFor({ state: 'visible', timeout: 10000 });
    }

    /**
     * Click the "Antworten" (reply) link in the message area.
     */
    async clickReply() {
        await this.messageArea.locator('a[href*="mode=messageform"]').click();
        await this.page.locator('input[name="subject"]').waitFor({ state: 'visible', timeout: 5000 });
    }

    /**
     * Get a Locator for a tree node by subject.
     *
     * @param {string} subject
     * @returns {import('@playwright/test').Locator}
     */
    nodeBySubject(subject) {
        return this.container.locator('a', { hasText: subject }).first();
    }
}
