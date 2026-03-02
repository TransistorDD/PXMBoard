{*
  Private Message List Template (Partial) - HTMX Skin

  Shows inbox/outbox listing of private messages with tab navigation.
  Includes delete, pagination, and HTMX navigation to individual PMs.

  Parameters:
  - $config.type: 'inbox' or 'outbox' (required)
  - $config.previd: Previous page ID (optional)
  - $config.nextid: Next page ID (optional)
  - $msg: Array of private messages
  - $msg[].id: Message ID
  - $msg[].subject: Message subject
  - $msg[].date: Message date
  - $msg[].unread: Whether the message is unread (optional)
  - $msg[].user.id: Sender/recipient user ID
  - $msg[].user.username: Sender/recipient username
  - $msg[].user.highlight: Whether user is highlighted
  - $error: Array of error messages (optional)
*}

		<!-- PM Tabs -->
		{include file="partial_pm_tabs.tpl"}

		{include file="partial_inline_errors.tpl"}

		<!-- Nachrichtenliste -->
		<div class="rounded-lg shadow" style="background-color: var(--color-surface-primary); border: 1px solid var(--color-border-default);">
			<div class="px-4 py-2 rounded-t-lg font-semibold" style="background-color: var(--color-surface-header); color: var(--color-content-inverse);">
				{if $config.type == 'outbox'}Gesendete Nachrichten{else}Posteingang{/if}
			</div>
			<table class="w-full text-sm">
				<thead>
					<tr style="border-bottom: 1px solid var(--color-border-default);">
						<th class="px-4 py-2 text-left" style="color: var(--color-content-secondary);">Betreff</th>
						<th class="px-4 py-2 text-left" style="color: var(--color-content-secondary);">{if $config.type == 'outbox'}Empf&auml;nger{else}Absender{/if}</th>
						<th class="px-4 py-2 text-right" style="color: var(--color-content-secondary);">Datum</th>
						<th class="px-4 py-2 text-center" style="color: var(--color-content-secondary);">Aktion</th>
					</tr>
				</thead>
				<tbody>
{if $msg}
{foreach from=$msg item=pm}
					<tr class="hover:bg-[var(--color-hover-bg)]" style="border-bottom: 1px solid var(--color-border-light);">
						<td class="px-4 py-2">
{if $pm.unread|default:false}
							<span class="inline-block w-2 h-2 rounded-full mr-1" style="background-color: var(--color-accent);"></span>
{/if}
							<a href="pxmboard.php?mode=privatemessage&amp;type={$config.type}&amp;msgid={$pm.id}"
							   hx-get="pxmboard.php?mode=privatemessage&type={$config.type}&msgid={$pm.id}"
							   hx-target="#htmxModalBody"
							   class="hover:underline{if $pm.unread|default:false} font-semibold{/if}" style="color: var(--color-link);">
								{$pm.subject}
							</a>
						</td>
						<td class="px-4 py-2{if $pm.user.highlight == 1} font-medium{/if}" style="color: {if $pm.user.highlight == 1}var(--color-accent){else}var(--color-content-secondary){/if};">
							{$pm.user.username}
						</td>
						<td class="px-4 py-2 text-right whitespace-nowrap" style="color: var(--color-content-secondary);">
							{$pm.date}
						</td>
						<td class="px-4 py-2 text-center">
							<a href="pxmboard.php?type={$config.type}&amp;mode=privatemessagedelete&amp;msgid={$pm.id}"
							   hx-get="pxmboard.php?type={$config.type}&mode=privatemessagedelete&msgid={$pm.id}"
							   hx-target="#htmxModalBody"
							   hx-push-url="false"
							   hx-confirm="Soll diese Nachricht geloescht werden?"
							   class="hover:underline" style="color: var(--color-accent-danger);" title="L&ouml;schen">
								<svg class="w-4 h-4 inline-block" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
							</a>
						</td>
					</tr>
{/foreach}
{else}
					<tr>
						<td colspan="4" class="px-4 py-6 text-center" style="color: var(--color-content-secondary);">
							Keine Nachrichten vorhanden.
						</td>
					</tr>
{/if}
				</tbody>
			</table>
		</div>

		<!-- Pagination -->
		<div class="text-center py-3 text-xs" style="color: var(--color-content-secondary);">
{if isset($config.previd) && $config.previd != ''}
			<a href="pxmboard.php?mode=privatemessagelist&amp;type={$config.type}&amp;page={$config.previd}"
			   hx-get="pxmboard.php?mode=privatemessagelist&type={$config.type}&page={$config.previd}"
			   hx-target="#htmxModalBody"
			   class="hover:underline" style="color: var(--color-link);">&laquo; Zur&uuml;ck</a> |
{else}
			- |
{/if}
{if $msg}
			<a href="pxmboard.php?type={$config.type}&amp;mode=privatemessagedelete&amp;msgid=-1"
			   hx-get="pxmboard.php?type={$config.type}&mode=privatemessagedelete&msgid=-1"
			   hx-target="#htmxModalBody"
			   hx-push-url="false"
			   hx-confirm="Sollen alle Nachrichten geloescht werden?"
			   class="hover:underline" style="color: var(--color-accent-danger);">Alle Nachrichten l&ouml;schen</a> |
{/if}
{if isset($config.nextid) && $config.nextid != ''}
			<a href="pxmboard.php?mode=privatemessagelist&amp;type={$config.type}&amp;page={$config.nextid}"
			   hx-get="pxmboard.php?mode=privatemessagelist&type={$config.type}&page={$config.nextid}"
			   hx-target="#htmxModalBody"
			   class="hover:underline" style="color: var(--color-link);">Weiter &raquo;</a>
{else}
			-
{/if}
		</div>
{if $config.logedin == 1}<span id="badge-data" data-pm="{$config.user.priv_message_unread_count}" data-notif="{$config.user.notification_unread_count}" hidden></span>{/if}
