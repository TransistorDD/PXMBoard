<!-- Thread-Header -->
<div class="htmx-thread-list-header" hx-target="#threadlist-container" hx-swap="innerHTML" hx-sync="#threadlist-container:replace">
	<span class="htmx-col-status"></span>
	<span class="htmx-col-subject"><a href="pxmboard.php?mode=threadlist&brdid={$config.board.id}&date={$config.timespan}&sort=subject" hx-get="pxmboard.php?mode=threadlist&brdid={$config.board.id}&date={$config.timespan}&sort=subject" class="hover:opacity-80">Thema</a></span>
	<span class="htmx-col-author"><a href="pxmboard.php?mode=threadlist&brdid={$config.board.id}&date={$config.timespan}&sort=username" hx-get="pxmboard.php?mode=threadlist&brdid={$config.board.id}&date={$config.timespan}&sort=username" class="hover:opacity-80">Autor</a></span>
	<span class="htmx-col-date text-right"><a href="pxmboard.php?mode=threadlist&brdid={$config.board.id}&date={$config.timespan}&sort=thread" hx-get="pxmboard.php?mode=threadlist&brdid={$config.board.id}&date={$config.timespan}&sort=thread" class="hover:opacity-80">Datum</a></span>
	<span class="htmx-col-views text-right"><a href="pxmboard.php?mode=threadlist&brdid={$config.board.id}&date={$config.timespan}&sort=views" hx-get="pxmboard.php?mode=threadlist&brdid={$config.board.id}&date={$config.timespan}&sort=views" class="hover:opacity-80">Views</a></span>
	<span class="htmx-col-replies text-center"><a href="pxmboard.php?mode=threadlist&brdid={$config.board.id}&date={$config.timespan}&sort=replies" hx-get="pxmboard.php?mode=threadlist&brdid={$config.board.id}&date={$config.timespan}&sort=replies" class="hover:opacity-80">#</a></span>
	<span class="htmx-col-lastpost text-right"><a href="pxmboard.php?mode=threadlist&brdid={$config.board.id}&date={$config.timespan}&sort=last" hx-get="pxmboard.php?mode=threadlist&brdid={$config.board.id}&date={$config.timespan}&sort=last" class="hover:opacity-80">Letzter Beitrag</a></span>
</div>

<!-- Thread-Zeilen -->
{foreach from=$thread item=thread}
<div class="htmx-thread-row {if $thread.fixed == 1}htmx-thread-row-pinned{elseif $thread.active == 1}htmx-thread-row-active{else}htmx-thread-row-closed{/if}{if $config.logedin == 1 && $thread.thread_msg_read == 1} htmx-msg-read{/if}" id="thread_{$thread.threadid}" data-brdid="{$config.board.id}" data-msgid="{$thread.id}" data-thrdid="{$thread.threadid}" data-lastid="{$thread.lastid}">
	<span class="htmx-col-status" title="{if $thread.fixed == 1}Angepinnt{elseif $thread.active == 1}Aktiv{else}Geschlossen{/if}">
		<span class="thread-status-icon">
			{if $thread.fixed == 1}<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="17" x2="12" y2="22"/><path d="M5 17h14v-1.76a2 2 0 0 0-1.11-1.79l-1.78-.9A2 2 0 0 1 15 10.76V6h1a2 2 0 0 0 0-4H8a2 2 0 0 0 0 4h1v4.76a2 2 0 0 1-1.11 1.79l-1.78.9A2 2 0 0 0 5 15.24Z"/></svg>
			{elseif $thread.active == 1}<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
			{else}<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>{/if}
		</span>
		<span class="thread-status-spinner" hidden aria-hidden="true"></span>
	</span>
	<a class="htmx-col-subject hover:underline"
	   href="pxmboard.php?mode=board&brdid={$config.board.id}&thrdid={$thread.threadid}&msgid={$thread.id}"
	   hx-get="pxmboard.php?mode=thread&brdid={$config.board.id}&thrdid={$thread.threadid}"
	   hx-target="#thread-container"
	   hx-swap="innerHTML"
	   hx-push-url="pxmboard.php?mode=board&brdid={$config.board.id}&thrdid={$thread.threadid}&msgid={$thread.id}"
	   onclick="handleThreadlistSubjectClick({$config.board.id},{$thread.id},{$thread.threadid})">{$thread.subject}</a>
	<span class="htmx-col-author text-content-secondary">
		{if $thread.user.id > 0}<a href="pxmboard.php?mode=userprofile&usrid={$thread.user.id}"
		   hx-get="pxmboard.php?mode=userprofile&usrid={$thread.user.id}"
		   hx-target="#htmxModalBody"
		   hx-swap="innerHTML"
		   data-modal-title="Profil"
		   hx-on::before-request="document.getElementById('htmxModalTitle').textContent=this.dataset.modalTitle;document.getElementById('htmxModal').showModal();"
		   class="hover:underline text-content-secondary">{/if}
		<span class="{if $thread.user.highlight == 1}font-medium text-accent-deep{/if}">{$thread.user.username}</span>
		{if $thread.user.id > 0}</a>{/if}
	</span>
	<span class="htmx-col-date text-right whitespace-nowrap text-content-secondary">{$thread.date}</span>
	<span class="htmx-col-views text-right text-content-secondary">{$thread.views}</span>
	<span class="htmx-col-replies text-center">
		<a href="pxmboard.php?mode=board&brdid={$config.board.id}&thrdid={$thread.threadid}"
		   hx-get="pxmboard.php?mode=thread&brdid={$config.board.id}&thrdid={$thread.threadid}"
		   hx-target="#thread-container"
		   hx-swap="innerHTML"
		   hx-push-url="pxmboard.php?mode=board&brdid={$config.board.id}&thrdid={$thread.threadid}"
		   onclick="handleReplyCountClick({$thread.threadid})"
	   class="hover:underline htmx-content-link">{$thread.msgquan}</a>
	</span>
	<span class="htmx-col-lastpost text-right whitespace-nowrap text-content-secondary">
		<a href="pxmboard.php?mode=board&brdid={$config.board.id}&thrdid={$thread.threadid}&msgid={$thread.lastid}"
		   hx-get="pxmboard.php?mode=message&brdid={$config.board.id}&msgid={$thread.lastid}"
		   hx-target="#message-container"
		   hx-swap="innerHTML"
		   hx-push-url="pxmboard.php?mode=board&brdid={$config.board.id}&thrdid={$thread.threadid}&msgid={$thread.lastid}"
		   onclick="handleThreadlistLastMsgClick({$config.board.id},{$thread.lastid},{$thread.threadid})"
		   class="htmx-content-link">{$thread.lastdate}</a>
		{if $config.logedin == 1 && $thread.msgquan > 0 && $thread.lastnew == 1}<span title="Neue Antwort" class="text-accent-danger"><svg class="w-2 h-2 inline-block" viewBox="0 0 8 8"><circle cx="4" cy="4" r="4" fill="currentColor"/></svg></span>{/if}
	</span>
</div>
{/foreach}

<!-- Pagination -->
<div class="text-center py-2 text-xs text-content-secondary" hx-target="#threadlist-container" hx-swap="innerHTML" hx-sync="#threadlist-container:replace">
{if isset($config.previd) && $config.previd != ''}
	<a href="pxmboard.php?mode=threadlist&brdid={$config.board.id}&date={$config.timespan}&sort={$config.sort}&page={$config.previd}" hx-get="pxmboard.php?mode=threadlist&brdid={$config.board.id}&date={$config.timespan}&sort={$config.sort}&page={$config.previd}" class="hover:underline text-link">&laquo; Zur&uuml;ck</a> |
{else}
	- |
{/if}
{if isset($config.nextid) && $config.nextid != ''}
	<a href="pxmboard.php?mode=threadlist&brdid={$config.board.id}&date={$config.timespan}&sort={$config.sort}&page={$config.nextid}" hx-get="pxmboard.php?mode=threadlist&brdid={$config.board.id}&date={$config.timespan}&sort={$config.sort}&page={$config.nextid}" class="hover:underline text-link">Weiter &raquo;</a>
{else}
	-
{/if}
</div>
{if $config.logedin == 1}<span id="badge-data" data-pm="{$config.user.priv_message_unread_count}" data-notif="{$config.user.notification_unread_count}" hidden></span>{/if}
