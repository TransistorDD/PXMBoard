		<div class="shadow rounded-lg overflow-hidden" style="background-color: var(--color-surface-primary);">
			<div class="px-3 py-2 font-semibold text-sm" style="background-color: var(--color-surface-header); color: var(--color-content-inverse);">Gefundene Benutzer</div>

			<div class="p-3" style="border: 1px solid var(--color-border-default); border-top: 0;">
				<div class="grid grid-cols-2 gap-2">
{foreach from=$user item=usr}
					<a href="pxmboard.php?mode=userprofile&brdid={$config.board.id}&usrid={$usr.id}" hx-get="pxmboard.php?mode=userprofile&brdid={$config.board.id}&usrid={$usr.id}" hx-target="#htmxModalBody" class="px-2 py-1 rounded hover:underline" style="color: var(--color-link);" onmouseover="this.style.backgroundColor='var(--color-hover-bg)'" onmouseout="this.style.backgroundColor='transparent'">{$usr.username}</a>
{foreachelse}
					<p class="col-span-2 text-center py-4" style="color: var(--color-content-secondary);">Keine Benutzer gefunden.</p>
{/foreach}
				</div>
			</div>
		</div>

		<!-- Pagination -->
		<div class="text-center py-2 text-xs" style="color: var(--color-content-secondary);">
{if isset($config.previd) && $config.previd != ''}
			<a href="pxmboard.php?mode=usersearch&brdid={$config.board.id}&nick={$config.username}&page={$config.previd}" hx-get="pxmboard.php?mode=usersearch&brdid={$config.board.id}&nick={$config.username}&page={$config.previd}" hx-target="#htmxModalBody" class="hover:underline" style="color: var(--color-link);">&laquo; Zur&uuml;ck</a>
{else}
			-
{/if}
			|
{if isset($config.nextid) && $config.nextid != ''}
			<a href="pxmboard.php?mode=usersearch&brdid={$config.board.id}&nick={$config.username}&page={$config.nextid}" hx-get="pxmboard.php?mode=usersearch&brdid={$config.board.id}&nick={$config.username}&page={$config.nextid}" hx-target="#htmxModalBody" class="hover:underline" style="color: var(--color-link);">Weiter &raquo;</a>
{else}
			-
{/if}
		</div>

