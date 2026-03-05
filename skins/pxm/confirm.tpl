{*
  Confirm Template (Partial) - pxm Skin

  Always renders as inline fragment (no layout wrapper).

  Parameters:
  - $message: Confirmation message (optional, default: "Die Aktion wurde erfolgreich ausgeführt.")
  - $config.title: Card heading (optional, default: "Bestätigung")
  - $config.webmaster: Webmaster email (required)
  - $msg.id: Optional link to saved message
*}

			<!-- Confirm Card -->
			<div class="px-4 py-2 rounded-t-lg font-semibold" style="background-color: var(--color-accent-success); color: var(--color-content-inverse); border: 1px solid var(--color-accent-success); border-bottom: 0;">
				{$config.title|default:'Bestätigung'}
			</div>
			<div class="p-4 text-center" style="border-left: 1px solid var(--color-border-default); border-right: 1px solid var(--color-border-default); background-color: var(--color-surface-primary); color: var(--color-content-primary);">
				{$message|default:'Die Aktion wurde erfolgreich ausgeführt.'}

{* Optional link to saved message *}
{if $msg.id|default:false}
				<br><br>
				<a href="pxmboard.php?mode=message&brdid={$msg.board.id}&thrdid={$msg.thread.id}&msgid={$msg.id}#msg{$msg.id}"
				   hx-get="pxmboard.php?mode=message&brdid={$msg.board.id}&msgid={$msg.id}"
				   hx-target="#message-container"
				   hx-swap="innerHTML"
				   hx-on:click="document.getElementById('htmxModal').close()"
				   class="hover:underline" style="color: var(--color-link);">Zur gespeicherten Nachricht</a>
{/if}
			</div>
			<div class="rounded-b-lg px-4 py-2 text-center text-xs" style="border: 1px solid var(--color-border-default); border-top: 0; background-color: var(--color-surface-primary); color: var(--color-content-secondary);">
				<a href="mailto:{$config.webmaster}" class="hover:underline" style="color: var(--color-link);">Mail Webmaster</a>
			</div>
