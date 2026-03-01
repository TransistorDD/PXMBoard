<div class="shadow rounded-lg overflow-hidden" style="background-color: var(--color-surface-primary);">
	<div class="px-3 py-2 font-semibold text-sm" style="background-color: var(--color-surface-header); color: var(--color-content-inverse);">Entw&uuml;rfe</div>

	<table class="htmx-table w-full">
		<thead>
			<tr>
				<th>Board</th>
				<th>Thema</th>
				<th>Datum</th>
			</tr>
		</thead>
		<tbody>
{foreach from=$drafts item=draft}
			<tr onmouseover="this.style.backgroundColor='var(--color-hover-bg)'" onmouseout="this.style.backgroundColor='transparent'">
				<td style="color: var(--color-content-secondary);">{$draft.boardname}</td>
				<td>
					<a href="pxmboard.php?mode=board&brdid={$draft.boardid}&thrdid={$draft.threadid}&msgid={$draft.id}" hx-get="pxmboard.php?mode=board&brdid={$draft.boardid}&thrdid={$draft.threadid}&msgid={$draft.id}" hx-target="#main-content" hx-push-url="true" onclick="closeModal()" class="hover:underline" style="color: var(--color-link);">{$draft.subject}</a>
				</td>
				<td class="text-right whitespace-nowrap" style="color: var(--color-content-secondary);">{$draft.date}</td>
			</tr>
{foreachelse}
			<tr>
				<td colspan="3" class="text-center py-4" style="color: var(--color-content-secondary);">Keine Entw&uuml;rfe vorhanden</td>
			</tr>
{/foreach}
		</tbody>
	</table>
</div>

<!-- Pagination -->
<div class="text-center py-2 text-xs" style="color: var(--color-content-secondary);">
{if isset($config.previd) && $config.previd != ''}
	<a href="pxmboard.php?mode=messagedraftlist&page={$config.previd}" hx-get="pxmboard.php?mode=messagedraftlist&page={$config.previd}" hx-target="#htmxModalBody" class="hover:underline" style="color: var(--color-link);">&laquo; Zur&uuml;ck</a> |
{else}
	- |
{/if}
{if isset($config.nextid) && $config.nextid != ''}
	<a href="pxmboard.php?mode=messagedraftlist&page={$config.nextid}" hx-get="pxmboard.php?mode=messagedraftlist&page={$config.nextid}" hx-target="#htmxModalBody" class="hover:underline" style="color: var(--color-link);">Weiter &raquo;</a>
{else}
	-
{/if}
</div>
