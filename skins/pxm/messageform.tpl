{*
  Message Form Template (Fragment) - HTMX Skin

  Loads into #message-container via hx-target.
  Handles: new thread, reply, guest quickpost, drafts.

  Parameters:
  - $msg: Message being replied to (optional, id=0 for new thread)
  - $config.board.id: Current board ID
  - $config.logedin: Login status
  - $config.quickpost: Guest quickpost allowed
  - $config.is_draft: true if editing a draft
  - $error: Array of error messages (optional)
*}
<div class="rounded-lg border border-border-light overflow-hidden">
	<form action="pxmboard.php" method="post"
	      hx-post="pxmboard.php"
	      hx-target="#message-container"
	      hx-swap="innerHTML">
		<input type="hidden" name="mode" value="messagesave"/>
		<input type="hidden" name="brdid" value="{$config.board.id}"/>
		<input name="msgid" type="hidden" value="{$msg.id}"/>

		<!-- Header -->
		<div class="px-4 py-2 rounded-t-lg font-semibold text-sm" style="background-color: var(--color-surface-secondary); color: var(--color-content-secondary);">
{if $msg and $msg.id > 0}
			Antwort auf den Beitrag &quot;{$msg.subject}&quot; posten:
{else}
			Neuen Thread erstellen:
{/if}
		</div>

		<div class="rounded-b-lg p-4 space-y-3" style="border: 1px solid var(--color-border-default); background-color: var(--color-surface-primary);">

			{include file="partial_inline_errors.tpl"}

{if $config.logedin != 1 && $config.quickpost}
			<!-- Guest Quickpost Fields -->
			<div class="flex items-center gap-2">
				<label class="w-24 shrink-0 htmx-formlabel">Nutzername</label>
				<input name="nick" type="text" size="30" maxlength="{$config.input_sizes.username}" tabindex="30001" class="rounded px-2 py-1 text-sm flex-1 focus:outline-none focus:ring-1" style="border: 1px solid var(--color-border-default); background-color: var(--color-surface-primary); color: var(--color-content-primary); --tw-ring-color: var(--color-accent);"/>
			</div>
	{if $config.quickpost}
			<div class="flex items-center gap-2">
				<label class="w-24 shrink-0 htmx-formlabel">Passwort</label>
				<input name="pass" type="password" size="20" maxlength="{$config.input_sizes.password}" tabindex="30002" class="rounded px-2 py-1 text-sm w-48 focus:outline-none focus:ring-1" style="border: 1px solid var(--color-border-default); background-color: var(--color-surface-primary); color: var(--color-content-primary); --tw-ring-color: var(--color-accent);"/>
			</div>
	{/if}
{/if}

		<!-- Subject -->
			<div class="flex items-center gap-2">
				<label class="w-24 shrink-0 htmx-formlabel">Titel</label>
				<input name="subject" type="text" size="61" maxlength="{$config.input_sizes.subject}" value="{if $msg}{$msg.subject}{/if}" tabindex="30004" class="rounded px-2 py-1 text-sm flex-1 focus:outline-none focus:ring-1" style="border: 1px solid var(--color-border-default); background-color: var(--color-surface-primary); color: var(--color-content-primary); --tw-ring-color: var(--color-accent);"/>
			</div>

			<!-- Editor -->
			<div>
				<label class="block mb-1 htmx-formlabel">Nachricht</label>
				{include file="partial_editor.tpl" editor_content=$msg._body|default:''}
			</div>

			<!-- Actions -->
			<div class="flex items-center justify-between gap-3">
				<div>
					<label class="flex items-center gap-2 cursor-pointer text-sm">
						<span class="toggle-switch">
							<input type="checkbox" name="notify_on_reply" value="1" tabindex="30006"/>
							<span class="toggle-switch-track"></span>
						</span>
						<span style="color: var(--color-content-primary);">Mailbenachrichtigung?</span>
					</label>
				</div>
				<div class="flex items-center gap-3">
					<button type="submit" name="publish" value="abschicken" class="htmx-btn-primary text-sm px-4 py-1" tabindex="30007">Absenden</button>
					<button type="submit" name="btn_draft" value="entwurf speichern" class="htmx-btn text-sm px-4 py-1" tabindex="30008">Entwurf speichern</button>
{if !$msg || $msg.id == 0}
					<a href="pxmboard.php?mode=threadlist&brdid={$config.board.id}"
					   hx-get="pxmboard.php?mode=threadlist&brdid={$config.board.id}"
					   hx-target="#threadlist-container"
					   hx-swap="innerHTML"
					   class="htmx-btn text-sm px-4 py-1 inline-block">Abbrechen</a>
{else}
					<a href="pxmboard.php?mode=message&brdid={$config.board.id}&msgid={$msg.id}"
					   hx-get="pxmboard.php?mode=message&brdid={$config.board.id}&msgid={$msg.id}"
					   hx-target="#message-container"
					   hx-swap="innerHTML"
					   class="htmx-btn text-sm px-4 py-1 inline-block">Abbrechen</a>
{/if}
				</div>
			</div>
		</div>
	</form>
</div>
