{*
  Private Message Form Template (Partial) - PXM Skin

  Compose or reply to a private message with TipTap editor.
  Uses standard form POST for submission (not HTMX).

  Parameters:
  - $config.type: 'inbox' or 'outbox' (required)
  - $touser.id: Recipient user ID
  - $touser.username: Recipient username
  - $msg: Existing message data for reply (optional)
  - $msg.subject: Pre-filled subject (optional)
  - $msg._body: Pre-filled body content for reply (optional)
  - $error: Array of error messages (optional)
*}

		<!-- PM Tabs -->
		{include file="partial_pm_tabs.tpl"}

		<!-- Formular -->
		<form action="pxmboard.php" method="post" hx-post="pxmboard.php" hx-target="#htmxModalBody">
			<input type="hidden" name="mode" value="privatemessagesave"/>
			<input type="hidden" name="toid" value="{$touser.id}"/>

			<div class="rounded-lg shadow" style="background-color: var(--color-surface-primary); border: 1px solid var(--color-border-default);">

				<!-- Header -->
				<div class="px-4 py-2 rounded-t-lg font-semibold" style="background-color: var(--color-surface-header); color: var(--color-content-inverse); border-bottom: 0;">
					Private Nachricht f&uuml;r {$touser.username}
				</div>

				<div class="p-4 space-y-3" style="border-top: 0;">

					{include file="partial_inline_errors.tpl"}

					<!-- Empfaenger (Anzeige) -->
					<div class="flex items-center gap-2">
						<label class="w-24 htmx-formlabel">Empf&auml;nger</label>
						<span class="text-sm font-semibold" style="color: var(--color-content-primary);">{$touser.username}</span>
					</div>

					<!-- Betreff -->
					<div class="flex items-center gap-2">
						<label class="w-24 htmx-formlabel">Betreff</label>
						<input type="text" name="subject" size="28" maxlength="{$config.input_sizes.subject}"
						       value="{if $msg|isset}{$msg.subject}{/if}" tabindex="30001"
						       class="rounded px-2 py-1 text-sm flex-1 focus:outline-none focus:ring-1"
						       style="border: 1px solid var(--color-border-default); background-color: var(--color-surface-primary); color: var(--color-content-primary); --tw-ring-color: var(--color-accent);"/>
					</div>

					<!-- Nachricht (Editor) -->
					<div>
						<label class="block mb-1 htmx-formlabel">Nachricht</label>
						{include file="partial_editor.tpl" editor_content=$msg._body|default:''}
					</div>

					<!-- Aktionen -->
					<div class="flex items-center justify-end gap-3">
						<button type="submit" class="htmx-btn-primary text-sm px-6 py-1" tabindex="30003">Absenden</button>
						<a href="pxmboard.php?mode=privatemessagelist&amp;type={$config.type}"
						   hx-get="pxmboard.php?mode=privatemessagelist&type={$config.type}"
						   hx-target="#htmxModalBody"
						   class="htmx-btn text-sm px-4 py-1 inline-block">Abbrechen</a>
					</div>

				</div>
			</div>
		</form>
