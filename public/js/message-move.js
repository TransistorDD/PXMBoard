/**
 * Message Move - Client-side logic
 *
 * Manages the selection of a message to move and displays UI elements.
 * Communicates with parent window via postMessage for badge display.
 *
 * @author Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright Torsten Rentsch 2001 - 2026
 */

const MessageMove = {

	/**
	 * SessionStorage keys
	 */
	STORAGE_KEY_ID: 'pxmboard_move_message_id',
	STORAGE_KEY_SUBJECT: 'pxmboard_move_message_subject',
	STORAGE_KEY_AUTHOR: 'pxmboard_move_message_author',
	STORAGE_KEY_DATE: 'pxmboard_move_message_date',
	STORAGE_KEY_BOARDID: 'pxmboard_move_message_boardid',

	/**
	 * Build badge text from message data
	 * @private
	 */
	_buildBadgeText: function(messageId, subject, author, date) {
		let text = 'Nachricht #' + messageId;
		if (subject) {
			text += ': "' + subject + '"';
		}
		if (author) {
			text += ' von ' + author;
		}
		if (date) {
			text += ' (' + date + ')';
		}
		return text;
	},

	/**
	 * Select message for moving
	 *
	 * @param {number} messageId Message ID to select
	 * @param {string} subject Message subject
	 * @param {string} author Message author
	 * @param {string} date Message date
	 * @param {number} boardId Board ID
	 */
	selectMessageForMove: function(messageId, subject, author, date, boardId) {
		sessionStorage.setItem(this.STORAGE_KEY_ID, messageId);
		sessionStorage.setItem(this.STORAGE_KEY_SUBJECT, subject || '');
		sessionStorage.setItem(this.STORAGE_KEY_AUTHOR, author || '');
		sessionStorage.setItem(this.STORAGE_KEY_DATE, date || '');
		sessionStorage.setItem(this.STORAGE_KEY_BOARDID, boardId || '');
		this.showMoveBadge(messageId, subject, author, date);
		this.updateMoveButtons();
	},

	/**
	 * Clear move selection
	 */
	clearMoveSelection: function() {
		sessionStorage.removeItem(this.STORAGE_KEY_ID);
		sessionStorage.removeItem(this.STORAGE_KEY_SUBJECT);
		sessionStorage.removeItem(this.STORAGE_KEY_AUTHOR);
		sessionStorage.removeItem(this.STORAGE_KEY_DATE);
		sessionStorage.removeItem(this.STORAGE_KEY_BOARDID);
		this.hideMoveBadge();
		this.updateMoveButtons();
	},

	/**
	 * Get currently selected message data
	 *
	 * @return {object|null} Selected message data or null
	 */
	getMoveSelection: function() {
		const id = sessionStorage.getItem(this.STORAGE_KEY_ID);
		if (!id) return null;

		return {
			id: parseInt(id, 10),
			subject: sessionStorage.getItem(this.STORAGE_KEY_SUBJECT) || '',
			author: sessionStorage.getItem(this.STORAGE_KEY_AUTHOR) || '',
			date: sessionStorage.getItem(this.STORAGE_KEY_DATE) || '',
			boardId: sessionStorage.getItem(this.STORAGE_KEY_BOARDID) || ''
		};
	},

	/**
	 * Show move badge via postMessage to parent window
	 *
	 * @param {number} messageId Selected message ID
	 * @param {string} subject Message subject
	 * @param {string} author Message author
	 * @param {string} date Message date
	 */
	showMoveBadge: function(messageId, subject, author, date) {
		const text = this._buildBadgeText(messageId, subject, author, date);
		if (window.parent && window.parent !== window) {
			window.parent.postMessage({
				action: 'showMoveBadge',
				text: text
			}, window.location.origin);
		}
	},

	/**
	 * Hide move badge via postMessage to parent window
	 */
	hideMoveBadge: function() {
		if (window.parent && window.parent !== window) {
			window.parent.postMessage({
				action: 'hideMoveBadge'
			}, window.location.origin);
		}
	},

	/**
	 * Update visibility of move buttons based on selection
	 */
	updateMoveButtons: function() {
		const selection = this.getMoveSelection();
		const selectedId = selection ? selection.id : null;
		const selectButtons = document.querySelectorAll('.btn-select-move');
		const insertButtons = document.querySelectorAll('.btn-insert-here');

		if (selectedId) {
			// Hide "select" buttons, show "insert" buttons
			selectButtons.forEach(btn => {
				btn.style.display = 'none';
			});

			insertButtons.forEach(btn => {
				const msgId = parseInt(btn.dataset.msgid, 10);
				// Don't show insert button for the selected message itself
				if (msgId !== selectedId) {
					btn.style.display = 'inline-block';
				}
			});
		} else {
			// Show "select" buttons, hide "insert" buttons
			selectButtons.forEach(btn => {
				btn.style.display = 'inline-block';
			});

			insertButtons.forEach(btn => {
				btn.style.display = 'none';
			});
		}

		// Update dropdown options
		this.updateDropdownOptions();
	},

	/**
	 * Update visibility of dropdown options based on selection
	 */
	updateDropdownOptions: function() {
		const selection = this.getMoveSelection();
		const selectedId = selection ? selection.id : null;

		// Get all dropdowns
		const dropdowns = document.querySelectorAll('[id^="admin-dropdown-"]');

		dropdowns.forEach(dropdown => {
			// Extract message ID from dropdown ID
			const msgId = parseInt(dropdown.id.replace('admin-dropdown-', ''), 10);

			const selectMoveOption = dropdown.querySelector('.option-select-move');
			const insertHereOption = dropdown.querySelector('.option-insert-here');

			if (!selectMoveOption || !insertHereOption) return;

			if (selectedId) {
				// A message is selected
				if (msgId === selectedId) {
					// This IS the selected message - hide both options
					selectMoveOption.style.display = 'none';
					insertHereOption.style.display = 'none';
				} else {
					// This is NOT the selected message - show "insert here", hide "select"
					selectMoveOption.style.display = 'none';
					insertHereOption.style.display = '';
				}
			} else {
				// No message selected - show "select move", hide "insert here"
				selectMoveOption.style.display = '';
				insertHereOption.style.display = 'none';
			}
		});
	},

	/**
	 * Perform move operation
	 *
	 * @param {number} targetMessageId Target message ID
	 * @param {string} targetSubject Target message subject
	 * @param {string} targetAuthor Target message author
	 * @param {string} targetDate Target message date
	 */
	performMove: function(targetMessageId, targetSubject, targetAuthor, targetDate) {
		const selection = this.getMoveSelection();

		if (!selection) {
			alert('Keine Nachricht zum Verschieben ausgewählt');
			return;
		}

		if (!selection.boardId) {
			alert('Board-ID fehlt. Bitte Seite neu laden.');
			return;
		}

		// Build confirmation message
		let confirmMsg = 'Nachricht verschieben?\n\n';
		confirmMsg += 'Von: #' + selection.id;
		if (selection.subject) confirmMsg += ' - "' + selection.subject + '"';
		if (selection.author) confirmMsg += ' (von ' + selection.author + ')';
		confirmMsg += '\n\n';
		confirmMsg += 'Nach: #' + targetMessageId;
		if (targetSubject) confirmMsg += ' - "' + targetSubject + '"';
		if (targetAuthor) confirmMsg += ' (von ' + targetAuthor + ')';

		// Confirmation dialog
		if (!confirm(confirmMsg)) {
			return;
		}

		// Build URL with board ID from selection
		const url = 'pxmboard.php?mode=messagemove&brdid=' + selection.boardId + '&sourcemsgid=' + selection.id + '&targetmsgid=' + targetMessageId;

		// Clear selection
		this.clearMoveSelection();

		// Redirect to action
		window.location.href = url;
	},

	/**
	 * Extract message data from DOM
	 *
	 * @return {object} Message data
	 */
	extractMessageData: function() {
		const subject = this._extractSubject();
		const author = this._extractAuthor();
		const date = this._extractDate();

		return {
			subject: subject,
			author: author,
			date: date
		};
	},

	/**
	 * Extract subject from DOM
	 * @private
	 */
	_extractSubject: function() {
		const subjectArea = document.querySelector('.bg-gray-100 .font-semibold');
		if (subjectArea) {
			return subjectArea.textContent.trim();
		}
		return '';
	},

	/**
	 * Extract author from DOM
	 * @private
	 */
	_extractAuthor: function() {
		const headerLink = document.querySelector('.bg-pxm-dark a[href*="userprofile"]');
		if (headerLink) {
			return headerLink.textContent.trim();
		}

		// Or look for nickname without link (guest posts)
		const headerNickname = document.querySelector('.bg-pxm-dark .font-semibold');
		if (headerNickname) {
			return headerNickname.textContent.trim();
		}

		return '';
	},

	/**
	 * Extract date from DOM
	 * @private
	 */
	_extractDate: function() {
		const dateSpan = document.querySelector('.bg-pxm-dark .text-gray-300');
		if (dateSpan) {
			const text = dateSpan.textContent;
			const match = text.match(/am\s+(.+?)\s+Uhr/);
			if (match) {
				return match[1].trim();
			}
		}
		return '';
	},

	/**
	 * Initialize on page load
	 */
	init: function() {
		const selection = this.getMoveSelection();

		if (selection) {
			this.showMoveBadge(selection.id, selection.subject, selection.author, selection.date);
		}

		this.updateMoveButtons();

		// Extract message data from current page
		const messageData = this.extractMessageData();

		// Get board ID from template badge
		const localBadge = document.getElementById('move-badge');
		const boardId = localBadge ? localBadge.dataset.boardid : null;

		// Bind event handlers for select buttons
		document.querySelectorAll('.btn-select-move').forEach(btn => {
			btn.addEventListener('click', (e) => {
				e.preventDefault();
				const messageId = parseInt(btn.dataset.msgid, 10);
				this.selectMessageForMove(messageId, messageData.subject, messageData.author, messageData.date, boardId);
			});
		});

		// Bind event handlers for insert buttons
		document.querySelectorAll('.btn-insert-here').forEach(btn => {
			btn.addEventListener('click', (e) => {
				e.preventDefault();
				const messageId = parseInt(btn.dataset.msgid, 10);
				this.performMove(messageId, messageData.subject, messageData.author, messageData.date);
			});
		});

		// Listen for cancel message from parent window
		window.addEventListener('message', (event) => {
			if (event.origin !== window.location.origin) return;
			if (event.data && event.data.action === 'cancelMove') {
				this.clearMoveSelection();
			}
		});
	}
};

// Initialize when DOM is ready
if (document.readyState === 'loading') {
	document.addEventListener('DOMContentLoaded', function() {
		MessageMove.init();
	});
} else {
	// DOM is already ready
	MessageMove.init();
}
