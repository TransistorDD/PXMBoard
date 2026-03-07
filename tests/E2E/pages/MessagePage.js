/**
 * Page Object Model – Message Detail & Message Form.
 *
 * Covers both the read view (message body loaded into `#message-container`
 * via HTMX) and the write view (messageform.tpl with the Tiptap editor).
 */
export class MessagePage {
    /**
     * @param {import('@playwright/test').Page} page
     */
    constructor(page) {
        this.page = page;

        // Read view – loaded by HTMX into #message-container on board.tpl
        this.container = page.locator('#message-container');

        // Write view – classic full-page form (mode=messageform / messagesave)
        this.subjectInput = page.locator('input[name="subject"]');
        this.submitButton = page.locator('button[name="publish"][value="abschicken"]');
        this.draftButton = page.locator('button[name="btn_draft"]');

        // Tiptap editor root – the contenteditable div rendered by the bundle
        this.editorContent = page.locator('.ProseMirror');

        // Reply / new-message link inside the message area
        this.replyLink = this.container.locator('a[href*="mode=messageform"]');
    }

    /**
     * Navigate to the board page and open the "Neuer Beitrag" form via HTMX.
     * The messageform fragment requires editor-bundle.js which is loaded by board.tpl.
     *
     * @param {number} boardId
     */
    async gotoNewMessage(boardId) {
        await this.page.goto(`/pxmboard.php?mode=board&brdid=${boardId}`);
        // Wait for HTMX / Alpine.js to be ready (threadlist load confirms it).
        await this.page.locator('#threadlist-container').waitFor({ state: 'visible', timeout: 8000 });
        // Desktop header button vs. mobile footer button.
        const headerBtn = this.page.locator('a[hx-target="#message-container"][href*="mode=messageform"]');
        const mobileBtn = this.page.locator('a.htmx-mobile-footer-newpost[href*="mode=messageform"]');
        if (await headerBtn.isVisible()) {
            await headerBtn.click();
        } else {
            await mobileBtn.click();
        }
        // Wait for the Tiptap editor (Alpine pxmEditor component) to render.
        await this.editorContent.waitFor({ state: 'visible', timeout: 10000 });
    }

    /**
     * Navigate to the board page with a specific message, then click the reply
     * link to load the reply form via HTMX into #message-container.
     *
     * @param {number} boardId
     * @param {number} threadId
     * @param {number} parentMessageId
     */
    async gotoReply(boardId, threadId, parentMessageId) {
        // Navigate with thrdid + msgid so board.tpl auto-loads the message via HTMX.
        await this.page.goto(`/pxmboard.php?mode=board&brdid=${boardId}&thrdid=${threadId}&msgid=${parentMessageId}`);
        // Wait for the message to appear in #message-container.
        const replyLink = this.page.locator('#message-container button[title="Antworten"]');
        await replyLink.waitFor({ state: 'visible', timeout: 10000 });
        // Click Antworten → HTMX loads the reply form into #message-container.
        await replyLink.click();
        // Wait for the Tiptap editor to render.
        await this.editorContent.waitFor({ state: 'visible', timeout: 10000 });
    }

    /**
     * Fill the message form and submit (post) it.
     *
     * The Tiptap editor exposes its editable area as `.ProseMirror`.  We use
     * `click()` + `fill()` to place text, which satisfies basic E2E coverage
     * without fighting Tiptap's complex event model.
     *
     * @param {string} subject
     * @param {string} body  Plain text to type into the editor
     */
    async fillAndSubmit(subject, body) {
        await this.subjectInput.fill(subject);
        await this.editorContent.click();
        await this.editorContent.fill(body);
        await this.submitButton.click();
        // HTMX loads confirm.tpl into #message-container after a successful post.
        // Click the "Zur gespeicherten Nachricht" link to navigate to the saved message.
        const savedLink = this.container.locator('a', { hasText: 'gespeicherten Nachricht' });
        await savedLink.waitFor({ state: 'visible', timeout: 8000 });
        await savedLink.click();
        // Wait for the message view to load (subject becomes visible).
        await this.container.waitFor({ state: 'visible', timeout: 8000 });
    }

    /**
     * Fill the message form and save as draft.
     *
     * @param {string} subject
     * @param {string} body
     */
    async fillAndSaveDraft(subject, body) {
        await this.subjectInput.fill(subject);
        await this.editorContent.click();
        await this.editorContent.fill(body);
        await this.draftButton.click();
    }

    /**
     * Return the message body element inside the read-view container.
     *
     * @returns {import('@playwright/test').Locator}
     */
    get body() {
        return this.container.locator('.message-body, .pxm-msg-body').first();
    }

    /**
     * Return the message subject element inside the read-view container.
     *
     * @returns {import('@playwright/test').Locator}
     */
    get subject() {
        return this.container.locator('h1, .message-subject').first();
    }
}
