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
				<select id="admin-dropdown-{$msg.id}" onchange="adminaction(this.value,{$config.board.id},{$msg.id},{$msg.thread.id})" class="text-xs rounded px-2 py-1 bg-surface-secondary text-content-primary border border-border-default">
					<option value="">IP: {$msg.ip}</option>
					{if $msg.replyto.id>0}
					<option value="deletemessage">L&ouml;schen</option>
					<option value="deletesubthread">Subthread l&ouml;schen</option>
					<option value="extractsubthread">Subthread extrahieren</option>
					<option value="selectmove" class="option-select-move">Nachricht verschieben</option>
					<option value="inserthere" class="option-insert-here" style="display:none">Hier einf&uuml;gen</option>
					{else}
					<option value="deletethread">Thread l&ouml;schen</option>
					<option value="movethread">Thread verschieben</option>
					<option value="selectmove" class="option-select-move">Als Subthread verschieben</option>
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
	<div class="px-4 py-2 flex items-center justify-between text-xs bg-surface-secondary border-t border-border-light text-content-primary">
		<div class="space-x-2">
{if $config.logedin == 1}
		<a href="pxmboard.php?mode=privatemessageform&brdid={$config.board.id}&msgid={$msg.id}&toid={$msg.user.id}"
		   hx-get="pxmboard.php?mode=privatemessageform&brdid={$config.board.id}&msgid={$msg.id}&toid={$msg.user.id}"
		   hx-target="#htmxModalBody"
		   hx-swap="innerHTML"
		   data-modal-title="Private Nachricht"
		   hx-on::before-request="document.getElementById('htmxModalTitle').textContent=this.dataset.modalTitle;document.getElementById('htmxModal').showModal();"
		   class="hover:underline text-link">Private Nachricht</a> |
{if $msg.user.id == $config.user.id or $config.admin == 1 or $config.moderator == 1}
		<a href="#" onclick="toggleNotifyOnReply(this, {$msg.id}, {$config.board.id}); return false;" class="hover:underline text-link" data-active="{$msg.notify_on_reply}">Mailbenachrichtigung {if $msg.notify_on_reply == 1}deaktivieren{else}aktivieren{/if}</a> |
{/if}
{if $config.admin == 1 || $config.moderator == 1 || $config.edit == 1}
		<a href="pxmboard.php?mode=messageeditform&brdid={$config.board.id}&msgid={$msg.id}"
		   hx-get="pxmboard.php?mode=messageeditform&brdid={$config.board.id}&msgid={$msg.id}"
		   hx-target="#message-container"
		   hx-swap="innerHTML"
		   class="hover:underline text-link">Editieren</a> |
{/if}
{/if}
		<a href="pxmboard.php?mode=messageform&brdid={$config.board.id}&msgid={$msg.id}"
		   hx-get="pxmboard.php?mode=messageform&brdid={$config.board.id}&msgid={$msg.id}"
		   hx-target="#message-container"
		   hx-swap="innerHTML"
		   class="font-medium hover:underline text-link">Antworten</a>
		</div>
{if $config.logedin == 1}
		<div class="flex items-center">
			<button onclick="toggleMessageNotification({$msg.id}, {$config.board.id}, this); return false;"
					class="hover:scale-110 transition-transform cursor-pointer border-none bg-transparent text-content-primary"
					title="{if $msg.notification_active}Benachrichtigungen deaktivieren{else}Benachrichtigungen aktivieren{/if}">
				{if $msg.notification_active}<svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>{else}<svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M8.7 3A6 6 0 0 1 18 8c0 2.9.86 5.4 1.64 7"/><path d="M6 6a6 6 0 0 0-.7 2c0 7-3 9-3 9h14"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/><line x1="2" y1="2" x2="22" y2="22"/></svg>{/if}
			</button>
		</div>
{/if}
	</div>
</div>
<script>if(!document.getElementById('threadlist-container')){ldelim}window.location.replace('pxmboard.php?mode=board&brdid={$config.board.id}&thrdid={$msg.thread.id}&msgid={$msg.id}'){rdelim}else{ldelim}!window._skipPushState&&history.pushState(null,'','pxmboard.php?mode=board&brdid={$config.board.id}&thrdid={$msg.thread.id}&msgid={$msg.id}');window._skipPushState=false{rdelim}</script>
{/if}
{if $config.logedin == 1}<span id="badge-data" data-pm="{$config.user.priv_message_unread_count}" data-notif="{$config.user.notification_unread_count}" hidden></span>{/if}
