{*
  TipTap Editor Partial - HTMX Skin

  Reusable rich text editor with Alpine.js integration.
  Toolbar: Bold, Italic, Underline, Strike, Link, Image, YouTube, Twitch,
           Blockquote, Spoiler, Member-only, Undo/Redo.
  Includes loading/error states and hidden input for form submission.

  Parameters:
  - $editor_content: Initial HTML content for the editor (optional, default: "")

  Usage:
  {include file="partial_editor.tpl" editor_content=$msg._body|default:''}
*}
<div x-data='pxmEditor({$editor_content|default:""|json_encode:15 nofilter})'
     x-init="init()"
     @destroy.window="destroy()"
     class="tiptap-editor"
     style="min-height: 250px;">

	<!-- Loading State -->
	<div x-show="loading" class="text-center p-4">
		<div class="htmx-indicator-spinner" style="display: inline-block;"></div>
		<span class="ml-2" style="color: var(--color-content-secondary);">Editor l&auml;dt...</span>
	</div>

	<!-- Error State -->
	<div x-show="error" class="text-center p-4 rounded" style="background-color: var(--color-surface-secondary); border: 1px solid var(--color-accent-danger);">
		<span style="color: var(--color-accent-danger);" x-text="error"></span>
	</div>

	<!-- Toolbar -->
	<div x-show="!loading && !error" class="tiptap-toolbar">
		<!-- Text Formatting -->
		<button type="button" @mousedown.prevent @click="toggleBold()" :class="{ldelim} 'is-active': isBold() {rdelim}" title="Fett (Ctrl+B)">
			<strong>B</strong>
		</button>
		<button type="button" @mousedown.prevent @click="toggleItalic()" :class="{ldelim} 'is-active': isItalic() {rdelim}" title="Kursiv (Ctrl+I)">
			<em>I</em>
		</button>
		<button type="button" @mousedown.prevent @click="toggleUnderline()" :class="{ldelim} 'is-active': isUnderline() {rdelim}" title="Unterstrichen (Ctrl+U)">
			<u>U</u>
		</button>
		<button type="button" @mousedown.prevent @click="toggleStrike()" :class="{ldelim} 'is-active': isStrike() {rdelim}" title="Durchgestrichen">
			<s>S</s>
		</button>

		<span class="mx-1" style="border-left: 1px solid var(--color-border-default);"></span>

		<!-- Media -->
		<button type="button" @mousedown.prevent @click="toggleLink()" :class="{ldelim} 'is-active': isLink() {rdelim}" title="Link einf&uuml;gen/entfernen">
			<svg class="w-4 h-4 inline-block" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg> Link
		</button>
		<button type="button" @mousedown.prevent @click="insertImage()" title="Bild einf&uuml;gen">
			<svg class="w-4 h-4 inline-block" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg> Bild
		</button>
		<button type="button" @mousedown.prevent @click="insertYoutube()" title="YouTube-Video einf&uuml;gen">
			<svg class="w-4 h-4 inline-block" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="5 3 19 12 5 21 5 3"/></svg> YouTube
		</button>
		<button type="button" @mousedown.prevent @click="insertTwitch()" title="Twitch-Video/Clip/Channel einf&uuml;gen">
			<svg class="w-4 h-4 inline-block" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 2H3v16h5v4l4-4h5l4-4V2zm-10 9V7m5 4V7"/></svg> Twitch
		</button>

		<span class="mx-1" style="border-left: 1px solid var(--color-border-default);"></span>

		<!-- Block Formatting -->
		<button type="button" @mousedown.prevent @click="toggleBlockquote()" :class="{ldelim} 'is-active': isBlockquote() {rdelim}" title="Zitat">
			<svg class="w-4 h-4 inline-block" viewBox="0 0 24 24" fill="currentColor"><path d="M6 17h3l2-4V7H5v6h3zm8 0h3l2-4V7h-6v6h3z"/></svg> Zitat
		</button>
		<button type="button" @mousedown.prevent @click="toggleSpoiler()" :class="{ldelim} 'is-active': isSpoiler() {rdelim}" title="Spoiler">
			<svg class="w-4 h-4 inline-block" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg> Spoiler
		</button>
		<button type="button" @mousedown.prevent @click="toggleMemberContent()" :class="{ldelim} 'is-active': isMemberContent() {rdelim}" title="Nur f&uuml;r Mitglieder">
			<svg class="w-4 h-4 inline-block" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg> Mitglieder
		</button>

		<span class="mx-1" style="border-left: 1px solid var(--color-border-default);"></span>

		<!-- Undo/Redo -->
		<button type="button" @mousedown.prevent @click="undo()" title="R&uuml;ckg&auml;ngig (Ctrl+Z)">
			<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7v6h6"/><path d="M21 17a9 9 0 0 0-9-9 9 9 0 0 0-6 2.3L3 13"/></svg>
		</button>
		<button type="button" @mousedown.prevent @click="redo()" title="Wiederherstellen (Ctrl+Y)">
			<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 7v6h-6"/><path d="M3 17a9 9 0 0 1 9-9 9 9 0 0 1 6 2.3L21 13"/></svg>
		</button>
	</div>

	<!-- TipTap Editor Area -->
	<div x-show="!loading && !error" x-ref="editor" style="min-height: 200px;"></div>

	<!-- Hidden Field with PXM Content -->
	<input type="hidden" name="body" :value="pxmContent" />
</div>
