{*
  User Search Template (Partial) - HTMX Skin

  Search form for finding users by username.
  If results exist ($user array), they are displayed below the form.

  Parameters:
  - $config.board.id: Current board ID
  - $config.username: Previously searched username (for pagination)
  - $config.previd: Previous page ID for pagination (or empty)
  - $config.nextid: Next page ID for pagination (or empty)
  - $user: Array of found user objects (id, username) - optional, present only with results
*}
		<!-- Suchformular -->
		<form action="pxmboard.php" method="post"
		      hx-post="pxmboard.php"
		      hx-target="#htmxModalBody">
			<input type="hidden" name="mode" value="usersearch"/>
			<input type="hidden" name="brdid" value="{$config.board.id}"/>

			<div class="px-4 py-2 rounded-t-lg font-semibold" style="background-color: var(--color-surface-header); color: var(--color-content-inverse);">
				Benutzersuche
			</div>
			<div class="rounded-b-lg p-4 shadow" style="border: 1px solid var(--color-border-default); border-top: 0; background-color: var(--color-surface-primary);">
				<div class="flex items-center gap-3">
					<label class="htmx-formlabel">Nutzername</label>
					<input type="text" name="nick" size="30" maxlength="{$config.input_sizes.username}"
					       class="rounded px-3 py-2 text-sm flex-1 focus:outline-none focus:ring-1"
					       style="border: 1px solid var(--color-border-default); background-color: var(--color-surface-primary); color: var(--color-content-primary); --tw-ring-color: var(--color-accent);"
					       placeholder="Benutzername eingeben..."/>
					<button type="submit" class="htmx-btn-primary text-sm px-6 py-2 rounded font-medium">Suchen</button>
				</div>
			</div>
		</form>

		<!-- Suchergebnisse -->
{if $user|default:false}
		<div class="mt-4">
			<div class="px-4 py-2 rounded-t-lg font-semibold text-sm" style="background-color: var(--color-surface-header); color: var(--color-content-inverse);">
				Gefundene Benutzer
			</div>
			<div class="rounded-b-lg p-4 shadow" style="border: 1px solid var(--color-border-default); border-top: 0; background-color: var(--color-surface-primary);">
				<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem;">
{foreach from=$user item=usr}
					<a href="pxmboard.php?mode=userprofile&brdid={$config.board.id}&usrid={$usr.id}"
					   hx-get="pxmboard.php?mode=userprofile&brdid={$config.board.id}&usrid={$usr.id}"
					   hx-target="#htmxModalBody"
					   class="px-2 py-1 rounded text-sm hover:underline"
					   style="color: var(--color-link);">
						{$usr.username}
					</a>
{/foreach}
				</div>
			</div>

			<!-- Pagination -->
			<div class="text-center py-3 text-xs" style="color: var(--color-content-secondary);">
{if isset($config.previd) && $config.previd != ''}
				<a href="pxmboard.php?mode=usersearch&brdid={$config.board.id}&nick={$config.username}&page={$config.previd}"
				   hx-get="pxmboard.php?mode=usersearch&brdid={$config.board.id}&nick={$config.username}&page={$config.previd}"
				   hx-target="#htmxModalBody"
				   class="hover:underline" style="color: var(--color-link);">&laquo; Zur&uuml;ck</a>
{else}
				-
{/if}
				|
{if isset($config.nextid) && $config.nextid != ''}
				<a href="pxmboard.php?mode=usersearch&brdid={$config.board.id}&nick={$config.username}&page={$config.nextid}"
				   hx-get="pxmboard.php?mode=usersearch&brdid={$config.board.id}&nick={$config.username}&page={$config.nextid}"
				   hx-target="#htmxModalBody"
				   class="hover:underline" style="color: var(--color-link);">Weiter &raquo;</a>
{else}
				-
{/if}
			</div>
		</div>
{/if}
