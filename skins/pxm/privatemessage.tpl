{*
  Private Message View Template (Partial) - HTMX Skin

  Shows a single private message with sender info, body, and action buttons.
  Includes tab navigation and reply/delete actions via HTMX.

  Parameters:
  - $config.type: 'inbox' or 'outbox' (required)
  - $config.profile_img_dir: Profile image directory
  - $config.usesignatures: Whether signatures are enabled
  - $msg.id: Message ID
  - $msg.subject: Message subject
  - $msg.date: Message date
  - $msg._body: Rendered message body (HTML)
  - $msg.user.id: Sender user ID
  - $msg.user.username: Sender username
  - $msg.user.email: Sender email (optional)
  - $msg.user.imgfile: Sender avatar filename (optional)
  - $msg.user._signature: Rendered user signature (optional)
*}

		<!-- PM Tabs -->
		{include file="partial_pm_tabs.tpl"}

		<!-- Nachricht -->
		<div class="rounded-lg shadow" style="background-color: var(--color-surface-primary); border: 1px solid var(--color-border-default);">

			<!-- Absender-Header -->
			<div class="px-4 py-2 rounded-t-lg flex items-center gap-2" style="background-color: var(--color-surface-header); color: var(--color-content-inverse); border-bottom: 0;">
				{if $msg.user.imgfile}
					<img src="{$config.profile_img_dir}{$msg.user.imgfile}" alt="{$msg.user.username}" class="h-8 w-8 rounded-full object-cover">
				{/if}
				<div>
					<span class="font-semibold">
						<a href="pxmboard.php?mode=userprofile&amp;usrid={$msg.user.id}"
						   hx-get="pxmboard.php?mode=userprofile&usrid={$msg.user.id}"
						   hx-target="#htmxModalBody"
						   style="color: var(--color-content-inverse);" class="hover:underline">
							{$msg.user.username}
						</a>
					</span>
					{if $msg.user.email != ''}
					&nbsp;<a href="mailto:{$msg.user.email}" class="text-xs hover:underline" style="color: var(--color-accent);">({$msg.user.email})</a>
					{/if}
					<span class="text-xs ml-2" style="color: var(--color-content-secondary);">am {$msg.date} Uhr</span>
				</div>
			</div>

			<!-- Betreff -->
			<div class="px-4 py-2" style="background-color: var(--color-surface-secondary); border-bottom: 1px solid var(--color-border-light);">
				<span style="color: var(--color-content-secondary);">Betreff:</span> <span class="font-semibold" style="color: var(--color-content-primary);">{$msg.subject}</span>
			</div>

			<!-- Nachrichteninhalt -->
			<div class="px-4 py-3" style="color: var(--color-content-primary);">
				<div class="prose max-w-none">{$msg._body nofilter}</div>
				{if $config.usesignatures > 0 && $msg.user._signature}
				<div class="mt-4 pt-2 text-xs" style="border-top: 1px solid var(--color-border-light); color: var(--color-content-secondary);">
					{$msg.user._signature nofilter}
				</div>
				{/if}
			</div>

			<!-- Aktionen -->
			<div class="px-4 py-2 rounded-b-lg text-center text-xs space-x-3" style="background-color: var(--color-surface-secondary); border-top: 1px solid var(--color-border-light);">
				<a href="pxmboard.php?mode=privatemessagelist&amp;type={$config.type}"
				   hx-get="pxmboard.php?mode=privatemessagelist&type={$config.type}"
				   hx-target="#htmxModalBody"
				   class="hover:underline" style="color: var(--color-link);">
					Zur&uuml;ck
				</a>
				{if $config.type == 'inbox'}
				|
				<a href="pxmboard.php?mode=privatemessageform&amp;type=outbox&amp;toid={$msg.user.id}&amp;pmsgid={$msg.id}"
				   hx-get="pxmboard.php?mode=privatemessageform&type=outbox&toid={$msg.user.id}&pmsgid={$msg.id}"
				   hx-target="#htmxModalBody"
				   class="hover:underline" style="color: var(--color-link);">
					Antworten
				</a>
				{/if}
				|
				<a href="pxmboard.php?type={$config.type}&amp;mode=privatemessagedelete&amp;msgid={$msg.id}"
				   hx-get="pxmboard.php?type={$config.type}&mode=privatemessagedelete&msgid={$msg.id}"
				   hx-target="#htmxModalBody"
				   hx-push-url="false"
				   hx-confirm="Soll diese Nachricht geloescht werden?"
				   class="hover:underline" style="color: var(--color-accent-danger);">
					L&ouml;schen
				</a>
			</div>
		</div>
