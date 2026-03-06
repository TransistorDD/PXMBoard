		<div class="shadow rounded-lg overflow-hidden" style="background-color: var(--color-surface-primary);">
			<div class="px-3 py-2 font-semibold text-sm" style="background-color: var(--color-surface-header); color: var(--color-content-inverse);">Beobachtete Nachrichten</div>

			<table class="htmx-table w-full">
				<thead>
					<tr>
						<th>Board</th>
						<th>Thema</th>
						<th>Datum</th>
						<th class="text-center w-12"><svg class="w-4 h-4 inline-block" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg></th>
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
									title="{if $notif.notification_active}Beobachten deaktivieren{else}Beobachten{/if}">
								{if $notif.notification_active}<svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>{else}<svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/><path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/><path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/><line x1="2" y1="2" x2="22" y2="22"/></svg>{/if}
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
