{*
  Message Edit Form Template (Fragment) - HTMX Skin

  Loads into #message-container via hx-target.
  Supports draft mode (publish/save/delete) and normal edit mode.

  Parameters:
  - $msg: Message being edited (required)
  - $config.board.id: Current board ID
  - $config.is_draft: true if editing a draft
  - $error: Array of error messages (optional)
*}
<div class="rounded-lg border border-border-light overflow-hidden">
	<form action="pxmboard.php" method="post"
	      hx-post="pxmboard.php"
	      hx-target="#message-container"
	      hx-swap="innerHTML">
		<input type="hidden" name="mode" value="messageeditsave"/>
		<input type="hidden" name="brdid" value="{$config.board.id}"/>
		<input name="msgid" type="hidden" value="{$msg.id}"/>

		<!-- Header -->
		<div class="px-4 py-2 rounded-t-lg font-semibold text-sm" style="background-color: var(--color-surface-secondary); color: var(--color-content-secondary);">
			Nachricht bearbeiten
		</div>

		<div class="rounded-b-lg p-4 space-y-3" style="border: 1px solid var(--color-border-default); background-color: var(--color-surface-primary);">

			{include file="partial_inline_errors.tpl"}

			<!-- Subject -->
			<div class="flex items-center gap-2">
				<label class="w-24 shrink-0 htmx-formlabel">Titel</label>
				<input name="subject" type="text" size="61" maxlength="{$config.input_sizes.subject}" value="{$msg.subject}" tabindex="30001" class="rounded px-2 py-1 text-sm flex-1 focus:outline-none focus:ring-1" style="border: 1px solid var(--color-border-default); background-color: var(--color-surface-primary); color: var(--color-content-primary); --tw-ring-color: var(--color-accent);"/>
			</div>

			<!-- Editor -->
			<div>
				<label class="block mb-1 htmx-formlabel">Nachricht</label>
				{include file="partial_editor.tpl" editor_content=$msg._body}
			</div>

			<!-- Actions -->
			<div class="flex items-center justify-end gap-3">
{if $config.is_draft}
				<button type="submit" name="publish" value="abschicken" class="htmx-btn-primary text-sm px-4 py-1" tabindex="30003">Absenden</button>
				<button type="submit" name="save_draft" value="entwurf speichern" class="htmx-btn text-sm px-4 py-1" tabindex="30010">Entwurf speichern</button>
				<button type="submit" name="delete_draft" value="l&ouml;schen" class="htmx-btn-danger text-sm px-4 py-1" tabindex="30011" onclick="return confirm('Entwurf wirklich l&ouml;schen?');">L&ouml;schen</button>
				<a href="pxmboard.php?mode=message&brdid={$config.board.id}&msgid={$msg.id}"
				   hx-get="pxmboard.php?mode=message&brdid={$config.board.id}&msgid={$msg.id}"
				   hx-target="#message-container"
				   hx-swap="innerHTML"
				   class="htmx-btn text-sm px-4 py-1 inline-block">Abbrechen</a>
{else}
				<button type="submit" class="htmx-btn-primary text-sm px-4 py-1" tabindex="30003">Absenden</button>
				<a href="pxmboard.php?mode=message&brdid={$config.board.id}&msgid={$msg.id}"
				   hx-get="pxmboard.php?mode=message&brdid={$config.board.id}&msgid={$msg.id}"
				   hx-target="#message-container"
				   hx-swap="innerHTML"
				   class="htmx-btn text-sm px-4 py-1 inline-block">Abbrechen</a>
{/if}
			</div>
		</div>
	</form>
</div>
