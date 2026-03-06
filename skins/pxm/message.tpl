{if $msg}
<div class="rounded-lg border border-border-light overflow-hidden">
	<!-- Kopfzeile -->
	<div class="px-4 py-3 flex items-center justify-between bg-surface-secondary border-b border-border-light text-content-primary">
		<div class="flex items-center gap-3">
			{if $msg.user.imgfile}
				<img src="{$config.profile_img_dir}{$msg.user.imgfile}" alt="{$msg.user.username}" class="h-10 w-10 rounded object-cover shrink-0">
			{else}
				<div class="h-10 w-10 rounded shrink-0 flex items-center justify-center bg-surface-tertiary">
					<svg class="h-6 w-6 text-content-secondary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
						<circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.58-7 8-7s8 3 8 7" stroke-linecap="round"/>
					</svg>
				</div>
			{/if}
			<div>
				<div class="font-semibold">
				{if $msg.user.id > 0}
				<a href="pxmboard.php?mode=userprofile&usrid={$msg.user.id}"
				   hx-get="pxmboard.php?mode=userprofile&usrid={$msg.user.id}"
				   hx-target="#htmxModalBody"
				   hx-swap="innerHTML"
				   data-modal-title="Profil"
				   hx-on::before-request="document.getElementById('htmxModalTitle').textContent=this.dataset.modalTitle;document.getElementById('htmxModal').showModal();"
				   class="hover:underline text-content-primary">{$msg.user.username}</a>
				{else}
				{$msg.user.username}
				{/if}
				</div>
				<div class="text-xs text-content-secondary">
					am {$msg.date} Uhr
					{if $msg.user.email != ""}&nbsp;<a href="mailto:{$msg.user.email}" class="text-link">({$msg.user.email})</a>{/if}
				</div>
			</div>
		</div>
		<div class="flex items-center gap-3">
			{if $msg.readcount > 0}
			<div class="flex items-center gap-1 text-sm text-content-secondary" title="Gelesen von {$msg.readcount} registrierten Nutzern">
				<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
					<path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z"/>
				</svg>
				<span>{$msg.readcount}</span>
			</div>
			{/if}
			{if $config.admin == 1 or $config.moderator == 1}
			<div>
				<select id="admin-dropdown-{$msg.id}" onchange="adminaction(this.value,{$config.board.id},{$msg.id},{$msg.thread.id},this)" class="text-xs rounded px-2 py-1 bg-surface-secondary text-content-primary border border-border-default">
					<option value="">IP: {$msg.ip}</option>
					{if $msg.replyto.id>0}
					<option value="deletemessage">L&ouml;schen</option>
					<option value="deletesubthread">Subthread l&ouml;schen</option>
					<option value="extractsubthread">Subthread extrahieren</option>
					<option value="selectmove" class="option-select-move">Subthread verschieben</option>
					<option value="inserthere" class="option-insert-here" style="display:none">Hier einf&uuml;gen</option>
					{else}
					<option value="deletethread">Thread l&ouml;schen</option>
					<option value="selectmove" class="option-select-move">Subthread verschieben</option>
					<option value="inserthere" class="option-insert-here" style="display:none">Hier einf&uuml;gen</option>
					{/if}
				</select>
			</div>
			{/if}
		</div>
	</div>

	<!-- Thema -->
	<div class="px-4 py-2 bg-surface-secondary border-b border-border-light text-content-primary">
{if $msg.replyto.id>0}
		<div>
			<span>Thema: <span class="font-semibold">{$msg.subject}</span></span>
			<span class="text-xs ml-8">Antwort auf: <a href="pxmboard.php?mode=message&brdid={$config.board.id}&msgid={$msg.replyto.id}" hx-get="pxmboard.php?mode=message&brdid={$config.board.id}&msgid={$msg.replyto.id}" hx-target="#message-container" hx-swap="innerHTML" class="font-semibold hover:underline text-link">{$msg.replyto.subject}</a> von <span class="font-medium">{$msg.replyto.user.username}</span></span>
		</div>
{else}
		<div>Thema: <span class="font-semibold">{$msg.subject}</span></div>
{/if}
	</div>

	<!-- Nachrichteninhalt -->
	<div class="px-4 py-3 bg-surface-primary text-content-primary">
		<div class="prose max-w-none">{$msg._body nofilter}</div>
		{if $config.usesignatures>0}
		<div class="mt-4 pt-2 text-xs text-content-secondary">{$msg.user._signature nofilter}</div>
		{/if}
	</div>

	<!-- Aktionsleiste -->
	<div class="px-4 py-2 flex items-center justify-end gap-1.5 text-xs bg-surface-secondary border-t border-border-light text-content-primary">
		<!-- Gruppe 1: Kommunikation -->
		{if !$msg.is_draft}
		<button type="button"
		   hx-get="pxmboard.php?mode=messageform&brdid={$config.board.id}&msgid={$msg.id}"
		   hx-target="#message-container"
		   hx-swap="innerHTML"
		   class="inline-flex items-center justify-center w-8 h-8 rounded hover:bg-surface-tertiary transition-colors text-content-primary cursor-pointer border-none bg-transparent"
		   title="Antworten">
			<svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/><path d="M15 14v-5H8"/><polyline points="10 7 8 9 10 11"/></svg>
		</button>
		{/if}
{if $config.admin == 1 || $config.moderator == 1 || $config.edit == 1}
		<button type="button"
		   hx-get="pxmboard.php?mode=messageeditform&brdid={$config.board.id}&msgid={$msg.id}"
		   hx-target="#message-container"
		   hx-swap="innerHTML"
		   class="inline-flex items-center justify-center w-8 h-8 rounded hover:bg-surface-tertiary transition-colors text-content-primary cursor-pointer border-none bg-transparent"
		   title="Editieren">
			<svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3Z"/></svg>
		</button>
{/if}
{if $config.logedin == 1}
		{if !$msg.is_draft}
		<button type="button"
		   hx-get="pxmboard.php?mode=privatemessageform&brdid={$config.board.id}&msgid={$msg.id}&toid={$msg.user.id}"
		   hx-target="#htmxModalBody"
		   hx-swap="innerHTML"
		   data-modal-title="Private Nachricht"
		   hx-on::before-request="document.getElementById('htmxModalTitle').textContent=this.dataset.modalTitle;document.getElementById('htmxModal').showModal();"
		   class="inline-flex items-center justify-center w-8 h-8 rounded hover:bg-surface-tertiary transition-colors text-content-primary cursor-pointer border-none bg-transparent"
		   title="Private Nachricht">
			<svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="8" cy="7" r="3"/><path d="M2 20c0-3.314 2.686-6 6-6h1"/><path d="M14 12h6a1 1 0 0 1 1 1v4a1 1 0 0 1-1 1h-2.5l-2 2.5V18h-.5a1 1 0 0 1-1-1v-4a1 1 0 0 1 1-1Z"/></svg>
		</button>
		{/if}
		<!-- Trenner: Kommunikation | Benachrichtigungen -->
		<div class="h-5 w-px opacity-30 shrink-0 bg-current mx-1"></div>
		<!-- Gruppe 2: Benachrichtigungen -->
{if $msg.user.id == $config.user.id or $config.admin == 1 or $config.moderator == 1}
		<button onclick="toggleNotifyOnReply(this, {$msg.id}, {$config.board.id}); return false;"
				class="inline-flex items-center justify-center w-8 h-8 rounded hover:bg-surface-tertiary transition-colors text-content-primary cursor-pointer border-none bg-transparent"
				title="{if $msg.notify_on_reply == 1}Mailbenachrichtigung deaktivieren{else}Mailbenachrichtigung aktivieren{/if}"
				data-active="{$msg.notify_on_reply}">
{if $msg.notify_on_reply == 1}
			<svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M16 8v5a3 3 0 0 0 6 0v-1a10 10 0 1 0-3.92 7.94"/></svg>
{else}
			<svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M16 8v5a3 3 0 0 0 6 0v-1a10 10 0 1 0-3.92 7.94"/><line x1="2" y1="2" x2="22" y2="22"/></svg>
{/if}
		</button>
{/if}
		<button onclick="toggleMessageNotification({$msg.id}, {$config.board.id}, this); return false;"
				class="inline-flex items-center justify-center w-8 h-8 rounded hover:bg-surface-tertiary transition-colors text-content-primary cursor-pointer border-none bg-transparent"
				title="{if $msg.notification_active}Beobachten deaktivieren{else}Beobachten{/if}">
			{if $msg.notification_active}<svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>{else}<svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/><path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/><path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/><line x1="2" y1="2" x2="22" y2="22"/></svg>{/if}
		</button>
{/if}
	</div>
</div>

{/if}
{if $config.logedin == 1}<span id="badge-data" data-pm="{$config.user.priv_message_unread_count}" data-notif="{$config.user.notification_unread_count}" hidden></span>{/if}
