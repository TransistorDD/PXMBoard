		<div class="shadow rounded-lg overflow-hidden" style="background-color: var(--color-surface-primary);">
			<div class="px-3 py-2 font-semibold text-sm" style="background-color: var(--color-surface-header); color: var(--color-content-inverse);">Aktive Ger&auml;te</div>

			<table class="htmx-table w-full">
				<thead>
					<tr>
						<th>Ger&auml;t</th>
						<th>IP-Adresse</th>
						<th>Erstellt</th>
						<th>Zuletzt verwendet</th>
						<th>Aktion</th>
					</tr>
				</thead>
				<tbody>
{if $tickets|@count > 0}
{foreach from=$tickets item=ticket}
					<tr {if $ticket.token == $current_ticket}style="background-color: var(--color-surface-secondary);"{/if} onmouseover="this.style.backgroundColor='var(--color-hover-bg)'" onmouseout="this.style.backgroundColor='{if $ticket.token == $current_ticket}var(--color-surface-secondary){else}transparent{/if}'">
						<td>
							{$ticket.device_info|escape}
							{if $ticket.token == $current_ticket}<span class="font-semibold" style="color: var(--color-link);"> (Dieses Ger&auml;t)</span>{/if}
						</td>
						<td style="color: var(--color-content-secondary);">{$ticket.ipaddress|escape}</td>
						<td class="whitespace-nowrap" style="color: var(--color-content-secondary);">{$ticket.created_timestamp|date_format:"%d.%m.%Y %H:%M"}</td>
						<td class="whitespace-nowrap" style="color: var(--color-content-secondary);">{$ticket.last_used_timestamp|date_format:"%d.%m.%Y %H:%M"}</td>
						<td>
							<a href="pxmboard.php?mode=userdevicelogout&amp;ticketid={$ticket.id}" hx-get="pxmboard.php?mode=userdevicelogout&ticketid={$ticket.id}" hx-target="#htmxModalBody" class="text-xs hover:underline" style="color: var(--color-accent-danger);">Ausloggen</a>
							{if $ticket.token == $current_ticket}<span class="text-xs font-semibold" style="color: var(--color-content-secondary);">(aktuell)</span>{/if}
						</td>
					</tr>
{/foreach}
{else}
					<tr>
						<td colspan="5" class="text-center py-4" style="color: var(--color-content-secondary);">
							Keine aktiven Sitzungen vorhanden.
						</td>
					</tr>
{/if}
				</tbody>
			</table>
		</div>


