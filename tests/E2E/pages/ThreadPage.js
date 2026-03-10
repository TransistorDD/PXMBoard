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
     * Navigate directly to a thread via the canonical deep-link URL and wait
     * for both containers to become visible.
     *
     * Uses `mode=board` with explicit `thrdid` so the server emits the HTMX
     * load-trigger for `#thread-container`.  Without `thrdid`, the thread tree
     * is not pre-loaded by the server and the container stays empty (= zero
     * height on mobile, which Playwright reports as hidden).
     *
     * @param {number} boardId
     * @param {number} messageId
     * @param {number} threadId   - ID of the thread that contains messageId
     */
    async goto(boardId, messageId, threadId) {
        await this.page.goto(
            `/pxmboard.php?mode=board&brdid=${boardId}&thrdid=${threadId}&msgid=${messageId}`
        );
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
