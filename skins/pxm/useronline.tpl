{*
  Users Online Template (Partial) - PXM Skin

  Shows a list of users currently online with their activity info.

  Parameters:
  - $users: Object with counts (all, visible, invisible)
  - $user: Array of online user objects (id, username)
  - $config.board.id: Current board ID
  - $config.previd: Previous page ID for pagination (or empty)
  - $config.nextid: Next page ID for pagination (or empty)
*}
		<!-- Header -->
		<div class="px-4 py-2 rounded-t-lg font-semibold" style="background-color: var(--color-surface-header); color: var(--color-content-inverse);">
			Benutzer Online
		</div>

		<!-- Zusammenfassung -->
		<div class="px-4 py-3 text-center text-sm" style="border-left: 1px solid var(--color-border-default); border-right: 1px solid var(--color-border-default); background-color: var(--color-surface-primary); color: var(--color-content-secondary);">
			{$users.all} Benutzer online ({$users.visible} sichtbar - {$users.invisible} versteckt)
		</div>

		<!-- Benutzerliste -->
		<div class="rounded-b-lg p-4 shadow" style="border: 1px solid var(--color-border-default); border-top: 0; background-color: var(--color-surface-primary);">
{if $user}
			<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem;">
{foreach from=$user item=usr}
				<a href="pxmboard.php?mode=userprofile&brdid={$config.board.id}&usrid={$usr.id}"
				   hx-get="pxmboard.php?mode=userprofile&brdid={$config.board.id}&usrid={$usr.id}" hx-target="#htmxModalBody"
				   class="px-2 py-1 rounded text-sm hover:underline"
				   style="color: var(--color-link);">
					{$usr.username}
				</a>
{/foreach}
			</div>
{else}
			<div class="text-center text-sm" style="color: var(--color-content-secondary);">
				Keine Benutzer online.
			</div>
{/if}
		</div>

		<!-- Pagination -->
		<div class="text-center py-3 text-xs" style="color: var(--color-content-secondary);">
{if isset($config.previd) && $config.previd != ''}
			<a href="pxmboard.php?mode=useronline&brdid={$config.board.id}&page={$config.previd}"
			   hx-get="pxmboard.php?mode=useronline&brdid={$config.board.id}&page={$config.previd}"
			   hx-target="#htmxModalBody"
			   class="hover:underline" style="color: var(--color-link);">&laquo; Zur&uuml;ck</a>
{else}
			-
{/if}
			|
{if isset($config.nextid) && $config.nextid != ''}
			<a href="pxmboard.php?mode=useronline&brdid={$config.board.id}&page={$config.nextid}"
			   hx-get="pxmboard.php?mode=useronline&brdid={$config.board.id}&page={$config.nextid}"
			   hx-target="#htmxModalBody"
			   class="hover:underline" style="color: var(--color-link);">Weiter &raquo;</a>
{else}
			-
{/if}
		</div>
