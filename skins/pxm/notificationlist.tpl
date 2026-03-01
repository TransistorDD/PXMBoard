		<div class="shadow rounded-lg overflow-hidden" style="background-color: var(--color-surface-primary);">
			<div class="px-3 py-2 font-semibold text-sm" style="background-color: var(--color-surface-header); color: var(--color-content-inverse);">Benachrichtigungen</div>

{if $notifications}
			<table class="htmx-table w-full">
				<thead>
					<tr>
						<th class="w-16">Typ</th>
						<th>Nachricht</th>
						<th>Datum</th>
					</tr>
				</thead>
				<tbody>
{foreach from=$notifications item=notification}
					<tr class="cursor-pointer" {if $notification.is_unread}style="background-color: var(--color-surface-secondary);"{/if} onmouseover="this.style.backgroundColor='var(--color-hover-bg)'" onmouseout="this.style.backgroundColor='{if $notification.is_unread}var(--color-surface-secondary){else}transparent{/if}'">
						<td class="text-center whitespace-nowrap">
							{if $notification.type == 'reply'}
								<svg class="w-4 h-4 inline-block" style="color: var(--color-link);" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 17-5-5 5-5"/><path d="M20 18v-2a4 4 0 0 0-4-4H4"/></svg>
							{elseif $notification.type == 'private_message'}
								<svg class="w-4 h-4 inline-block" style="color: var(--color-status-open);" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
							{elseif $notification.type == 'mention'}
								<span style="color: var(--color-accent);">@</span>
							{elseif $notification.type == 'draft_reminder'}
								<svg class="w-4 h-4 inline-block" style="color: var(--color-status-locked);" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
							{elseif $notification.type == 'thread_moved'}
								<svg class="w-4 h-4 inline-block" style="color: var(--color-accent);" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
							{elseif $notification.type == 'user_activated'}
								<svg class="w-4 h-4 inline-block" style="color: var(--color-status-open);" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
							{else}
								<svg class="w-4 h-4 inline-block" style="color: var(--color-content-secondary);" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>
							{/if}
						</td>
						<td>
							<a href="{$notification.link nofilter}" hx-get="{$notification.link nofilter}" hx-target="#main-content" hx-push-url="true" onclick="closeModal()" class="block hover:underline">
								<div class="font-semibold" style="color: {if $notification.is_unread}var(--color-content-primary){else}var(--color-content-secondary){/if};">{$notification.title}</div>
								<div class="text-xs" style="color: var(--color-content-secondary);">{$notification.message}</div>
							</a>
						</td>
						<td class="text-right whitespace-nowrap" style="color: var(--color-content-secondary);">{$notification.created_date}</td>
					</tr>
{/foreach}
				</tbody>
			</table>
{else}
			<p class="text-center py-4 text-sm" style="color: var(--color-content-secondary);">Keine Benachrichtigungen vorhanden.</p>
{/if}
		</div>

		<!-- Actions & Pagination -->
		<div class="text-center py-2 text-xs" style="color: var(--color-content-secondary);">
{if isset($config.previd) && $config.previd != ''}
			<a href="pxmboard.php?mode=notificationlist&page={$config.previd}" hx-get="pxmboard.php?mode=notificationlist&page={$config.previd}" hx-target="#htmxModalBody" class="hover:underline" style="color: var(--color-link);">&laquo; Zur&uuml;ck</a> |
{else}
			- |
{/if}
			<a href="pxmboard.php?mode=ajaxnotificationmarkallread"
			   hx-get="pxmboard.php?mode=ajaxnotificationmarkallread"
			   hx-swap="none"
			   hx-confirm="Alle Benachrichtigungen als gelesen markieren?"
			   hx-on:htmx:after-request="if(event.detail.successful) htmx.ajax('GET','pxmboard.php?mode=notificationlist',document.getElementById('htmxModalBody'))"
			   class="hover:underline" style="color: var(--color-link);">Alle als gelesen markieren</a>
{if isset($config.nextid) && $config.nextid != ''}
			| <a href="pxmboard.php?mode=notificationlist&page={$config.nextid}" hx-get="pxmboard.php?mode=notificationlist&page={$config.nextid}" hx-target="#htmxModalBody" class="hover:underline" style="color: var(--color-link);">Weiter &raquo;</a>
{else}
			| -
{/if}
		</div>

