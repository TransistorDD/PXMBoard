{include file="layout_header.tpl"}

	<main id="main-content" class="flex-1 container mx-auto px-4 py-6 max-w-5xl">

		{if $newestmember}
		<p class="text-sm mb-4 text-content-secondary">Neuestes Mitglied: <a href="pxmboard.php?mode=userprofile&usrid={$newestmember.user.id}"
		   hx-get="pxmboard.php?mode=userprofile&usrid={$newestmember.user.id}"
		   hx-target="#htmxModalBody"
		   hx-swap="innerHTML"
		   data-modal-title="Profil"
		   hx-on::before-request="document.getElementById('htmxModalTitle').textContent=this.dataset.modalTitle;document.getElementById('htmxModal').showModal();"
		   class="font-medium hover:underline text-link">{$newestmember.user.username}</a></p>
		{/if}

		<table class="htmx-table w-full shadow rounded-lg overflow-hidden bg-surface-primary">
			<thead>
				<tr>
					<th class="w-8"></th>
					<th>Name</th>
					<th>Thema</th>
					<th>Letzte Nachricht</th>
					<th>Moderator(en)</th>
					{if $config.admin == 1}
						<th>Admin</th>
					{/if}
				</tr>
			</thead>
			<tbody>
				{foreach from=$boards.board item=board}
				<tr class="bg-surface-primary hover:bg-hover-bg">
					<td class="text-center">
						{if $config.admin == 1}
							<button onclick="openStatusPopup(this, {$board.id})" class="status-icon-btn hover:opacity-70 transition-opacity cursor-pointer" title="{$board.status_label}">
								{if $board.status == 1}<svg class="w-5 h-5" viewBox="0 0 24 24"><circle cx="12" cy="12" r="6" class="fill-board-open"/></svg>
								{elseif $board.status == 2}<svg class="w-5 h-5 stroke-board-locked" viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
								{elseif $board.status == 3}<svg class="w-5 h-5 stroke-board-viewonly" viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/></svg>
								{elseif $board.status == 4}<svg class="w-5 h-5 stroke-board-protected" viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><rect x="9" y="11" width="6" height="5" rx="1"/><path d="M10.5 11V9.5a1.5 1.5 0 0 1 3 0V11"/></svg>
								{elseif $board.status == 5}<svg class="w-5 h-5" viewBox="0 0 24 24"><circle cx="12" cy="12" r="6" class="fill-board-closed"/></svg>
								{/if}
							</button>
						{else}
							<span title="{$board.status_label}">
								{if $board.status == 1}<svg class="w-5 h-5" viewBox="0 0 24 24"><circle cx="12" cy="12" r="6" class="fill-board-open"/></svg>
								{elseif $board.status == 2}<svg class="w-5 h-5 stroke-board-locked" viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
								{elseif $board.status == 3}<svg class="w-5 h-5 stroke-board-viewonly" viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/></svg>
								{elseif $board.status == 4}<svg class="w-5 h-5 stroke-board-protected" viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><rect x="9" y="11" width="6" height="5" rx="1"/><path d="M10.5 11V9.5a1.5 1.5 0 0 1 3 0V11"/></svg>
								{elseif $board.status == 5}<svg class="w-5 h-5" viewBox="0 0 24 24"><circle cx="12" cy="12" r="6" class="fill-board-closed"/></svg>
								{/if}
							</span>
						{/if}
					</td>
					<td class="font-medium">
						{if $board.status != 5 || $config.admin == 1}
							<a href="pxmboard.php?mode=board&brdid={$board.id}" class="hover:underline text-link">{$board.name}</a>
						{else}
							<span class="text-content-secondary">{$board.name}</span>
						{/if}
					</td>
					<td class="text-content-secondary">{$board.desc}</td>
					<td class="text-center text-content-secondary">{$board.lastmsg}</td>
					<td class="text-content-secondary">
						{foreach from=$board.moderator item=moderator}
							{$moderator.username}<br>
						{/foreach}
					</td>
					{if $config.admin == 1}
						<td class="text-center"><a href="pxmboard.php?mode=admboardform&id={$board.id}" target="admin" class="text-xs hover:underline text-accent">edit</a></td>
					{/if}
				</tr>
				{/foreach}
			</tbody>
		</table>

		{if $config.admin == 1}
			<div class="mt-2 text-center text-sm">
				<a href="pxmboard.php?mode=admboardform" target="admin" class="hover:underline text-link">Board hinzuf&uuml;gen</a> |
				<a href="pxmboard.php?mode=admlogin" target="admin" class="hover:underline text-link">Weitere Funktionen</a>
			</div>
		{/if}

		{if $config.logedin == 0}
		<div class="mt-6 shadow rounded-lg p-4 bg-surface-primary">
			<form action="pxmboard.php" method="post" class="flex flex-wrap items-center gap-3">
				<input type="hidden" name="mode" value="login">
				<input type="hidden" name="brdid" value="{if isset($config.board)}{$config.board.id}{/if}">
				<div class="flex items-center gap-2">
					<label class="text-sm text-content-primary">Nutzername</label>
					<input type="text" name="username" maxlength="{$config.input_sizes.username}" class="rounded px-2 py-1 text-sm w-40 border border-border-default bg-surface-primary text-content-primary focus:outline-none focus:ring-1 focus:ring-accent">
				</div>
				<div class="flex items-center gap-2">
					<label class="text-sm text-content-primary">Passwort</label>
					<input type="password" name="password" maxlength="{$config.input_sizes.password}" class="rounded px-2 py-1 text-sm w-32 border border-border-default bg-surface-primary text-content-primary focus:outline-none focus:ring-1 focus:ring-accent">
				</div>
				<button type="submit" class="htmx-btn-primary text-sm px-3 py-1">Login</button>
				<label class="flex items-center gap-2 cursor-pointer">
					<span class="toggle-switch">
						<input type="checkbox" name="staylogedin" value="1"/>
						<span class="toggle-switch-track"></span>
					</span>
					<span class="font-medium text-xs text-content-secondary">Angemeldet bleiben?</span>
				</label>
			</form>
			{if $error}
				<p class="mt-2 text-sm text-accent-danger">{$error.text}</p>
			{/if}
		</div>
		{/if}

		<div class="mt-6 shadow rounded-lg overflow-hidden bg-surface-primary">
			<div class="px-3 py-2 text-xs font-semibold uppercase tracking-wide bg-surface-secondary text-content-secondary border-b border-border-light">Neueste Beitr&auml;ge</div>
			{foreach from=$newestmessages.msg item=msg}
			<div class="px-3 py-2 text-sm border-b border-border-light hover:bg-hover-bg">
				<a href="pxmboard.php?mode=board&brdid={$msg.thread.brdid}&thrdid={$msg.thread.id}&msgid={$msg.id}" class="hover:underline text-link">{$msg.subject}</a>
				von <span class="{if $msg.user.highlight == 1}font-medium text-accent-deep{else}text-content-secondary{/if}">{$msg.user.username}</span>
				am {$msg.date} Uhr
			</div>
			{/foreach}
		</div>
	</main>

{if $config.admin == 1}
<!-- Board Status Popup (Admin) -->
<div id="boardStatusPopup" style="display:none; position:fixed; z-index:99999; background-color: var(--color-surface-primary); border: 1px solid var(--color-border-default);" class="rounded-lg shadow-2xl p-2 gap-1">
	<button data-boardid="0" onclick="changeBoardStatus(+this.dataset.boardid, 1)" class="hover:opacity-70 transition-opacity cursor-pointer p-1 rounded" title="Öffentlich">
		<svg class="w-5 h-5" viewBox="0 0 24 24"><circle cx="12" cy="12" r="6" class="fill-board-open"/></svg>
	</button>
	<button data-boardid="0" onclick="changeBoardStatus(+this.dataset.boardid, 2)" class="hover:opacity-70 transition-opacity cursor-pointer p-1 rounded" title="Nur Mitglieder">
		<svg class="w-5 h-5 stroke-board-locked" viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
	</button>
	<button data-boardid="0" onclick="changeBoardStatus(+this.dataset.boardid, 3)" class="hover:opacity-70 transition-opacity cursor-pointer p-1 rounded" title="Nur Lesen (Öffentlich)">
		<svg class="w-5 h-5 stroke-board-viewonly" viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/></svg>
	</button>
	<button data-boardid="0" onclick="changeBoardStatus(+this.dataset.boardid, 4)" class="hover:opacity-70 transition-opacity cursor-pointer p-1 rounded" title="Nur Lesen (Mitglieder)">
		<svg class="w-5 h-5 stroke-board-protected" viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><rect x="9" y="11" width="6" height="5" rx="1"/><path d="M10.5 11V9.5a1.5 1.5 0 0 1 3 0V11"/></svg>
	</button>
	<button data-boardid="0" onclick="changeBoardStatus(+this.dataset.boardid, 5)" class="hover:opacity-70 transition-opacity cursor-pointer p-1 rounded" title="Geschlossen">
		<svg class="w-5 h-5" viewBox="0 0 24 24"><circle cx="12" cy="12" r="6" class="fill-board-closed"/></svg>
	</button>
</div>
{/if}

{include file="layout_footer.tpl"}
