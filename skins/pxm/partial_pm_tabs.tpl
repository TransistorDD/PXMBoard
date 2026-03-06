{*
  PM Tabs Partial - PXM Skin

  Inbox/Outbox tabs for private messages.
  Active tab highlighted. Navigates within the modal body.

  Parameters:
  - $config.type: 'inbox' or 'outbox' (required)

  Usage:
  {include file="partial_pm_tabs.tpl"}
*}
<div class="flex mb-3" style="border-bottom: 1px solid var(--color-border-default);">
{if $config.type == 'outbox'}
	<a href="pxmboard.php?mode=privatemessagelist&amp;type=inbox"
	   hx-get="pxmboard.php?mode=privatemessagelist&type=inbox"
	   hx-target="#htmxModalBody"
	   class="px-4 py-2 hover:underline" style="color: var(--color-content-secondary);">
		Inbox
	</a>
	<a href="pxmboard.php?mode=privatemessagelist&amp;type=outbox"
	   hx-get="pxmboard.php?mode=privatemessagelist&type=outbox"
	   hx-target="#htmxModalBody"
	   class="px-4 py-2 font-semibold" style="color: var(--color-accent); border-bottom: 2px solid var(--color-accent);">
		Outbox
	</a>
{else}
	<a href="pxmboard.php?mode=privatemessagelist&amp;type=inbox"
	   hx-get="pxmboard.php?mode=privatemessagelist&type=inbox"
	   hx-target="#htmxModalBody"
	   class="px-4 py-2 font-semibold" style="color: var(--color-accent); border-bottom: 2px solid var(--color-accent);">
		Inbox
	</a>
	<a href="pxmboard.php?mode=privatemessagelist&amp;type=outbox"
	   hx-get="pxmboard.php?mode=privatemessagelist&type=outbox"
	   hx-target="#htmxModalBody"
	   class="px-4 py-2 hover:underline" style="color: var(--color-content-secondary);">
		Outbox
	</a>
{/if}
</div>
