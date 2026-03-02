		<div class="shadow rounded-lg overflow-hidden" style="background-color: var(--color-surface-primary);">
			<div class="px-3 py-2 font-semibold text-sm" style="background-color: var(--color-surface-header); color: var(--color-content-inverse);">Beobachtete Nachrichten</div>

			<table class="htmx-table w-full">
				<thead>
					<tr>
						<th>Board</th>
						<th>Thema</th>
						<th>Datum</th>
						<th class="text-center w-12"><svg class="w-4 h-4 inline-block" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg></th>
					</tr>
				</thead>
				<tbody>
{foreach from=$notifications item=notif}
					<tr onmouseover="this.style.backgroundColor='var(--color-hover-bg)'" onmouseout="this.style.backgroundColor='transparent'">
						<td style="color: var(--color-content-secondary);">{$notif.boardname}</td>
						<td>
							<a href="pxmboard.php?mode=board&brdid={$notif.boardid}&thrdid={$notif.threadid}&msgid={$notif.messageid}" class="hover:underline" style="color: var(--color-link);">{$notif.subject}</a>
						</td>
						<td class="text-right whitespace-nowrap" style="color: var(--color-content-secondary);">{$notif.date}</td>
						<td class="text-center">
							<button onclick="toggleMessageNotification({$notif.messageid}, {$notif.boardid}, this); return false;"
									class="hover:scale-110 transition-transform cursor-pointer border-none bg-transparent"
									title="{if $notif.notification_active}Benachrichtigungen deaktivieren{else}Benachrichtigungen aktivieren{/if}">
								{if $notif.notification_active}<svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>{else}<svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M8.7 3A6 6 0 0 1 18 8c0 2.9.86 5.4 1.64 7"/><path d="M6 6a6 6 0 0 0-.7 2c0 7-3 9-3 9h14"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/><line x1="2" y1="2" x2="22" y2="22"/></svg>{/if}
							</button>
						</td>
					</tr>
{foreachelse}
					<tr>
						<td colspan="4" class="text-center py-4" style="color: var(--color-content-secondary);">Keine beobachteten Nachrichten vorhanden</td>
					</tr>
{/foreach}
				</tbody>
			</table>
		</div>

		<!-- Pagination -->
		<div class="text-center py-2 text-xs" style="color: var(--color-content-secondary);">
{if isset($config.previd) && $config.previd != ''}
			<a href="pxmboard.php?mode=messagenotificationlist&page={$config.previd}" hx-get="pxmboard.php?mode=messagenotificationlist&page={$config.previd}" hx-target="#htmxModalBody" class="hover:underline" style="color: var(--color-link);">&laquo; Zur&uuml;ck</a> |
{else}
			- |
{/if}
{if isset($config.nextid) && $config.nextid != ''}
			<a href="pxmboard.php?mode=messagenotificationlist&page={$config.nextid}" hx-get="pxmboard.php?mode=messagenotificationlist&page={$config.nextid}" hx-target="#htmxModalBody" class="hover:underline" style="color: var(--color-link);">Weiter &raquo;</a>
{else}
			-
{/if}
		</div>
