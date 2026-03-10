{*
  Message List Template (Partial) - PXM Skin

  Search results, loaded into #threadlist-container.
  Clicking a result loads thread+message via HTMX (hx-get) + handleCachedThreadLoad().

  Parameters:
  - $config.items: Total result count
  - $config.board.id: Board ID
  - $config.searchprofile: Active search profile
  - $config.previd / $config.nextid: Pagination
  - $config.curid / $config.count: Current page / page count
  - $msg: Array of results (or threads when grouped)
*}
<div class="shadow rounded-lg overflow-hidden" style="background-color: var(--color-surface-primary);">
	<div class="px-3 py-2 font-semibold text-sm flex items-center justify-between" style="background-color: var(--color-surface-secondary); color: var(--color-content-secondary);">
		<span>{$config.items} Gefundene Nachrichten</span>
		<a href="pxmboard.php?mode=messagesearch&brdid={$config.board.id}"
		   hx-get="pxmboard.php?mode=messagesearch&brdid={$config.board.id}"
		   hx-target="#threadlist-container"
		   hx-swap="innerHTML"
		   class="font-normal hover:underline text-xs" style="color: var(--color-link);">&laquo; Zur&uuml;ck zur Suche</a>
	</div>

	<div style="border: 1px solid var(--color-border-default); border-top: 0;">
	<div class="search-groups">
	{foreach from=$msg item=thread}
			<div>
				<div class="px-4 py-2 cursor-pointer flex items-center gap-2 text-sm" style="border-bottom: 1px solid var(--color-border-light);" onclick="searchGroupToggle(this)" onmouseover="this.style.backgroundColor='var(--color-hover-bg)'" onmouseout="this.style.backgroundColor='transparent'">
					<svg class="group-arrow w-4 h-4 shrink-0 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
						<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
					</svg>
					<div>
	{if $thread.root_message}
						<span class="font-medium" style="color: var(--color-content-primary);">Thread: {$thread.root_message.subject}</span>
						<span class="text-xs" style="color: var(--color-content-secondary);">
							von <span class="{if $thread.root_message.user.highlight == 1}font-medium{/if}" {if $thread.root_message.user.highlight == 1}style="color: var(--color-accent-deep);"{/if}>{$thread.root_message.user.username}</span>
							am {$thread.root_message.date}
						</span>
	{else}
						<span class="font-medium" style="color: var(--color-content-primary);">Thread (Root-Nachricht nicht gefunden)</span>
	{/if}
						<span class="text-xs ml-2" style="color: var(--color-content-secondary);">({$thread.messages|@count} Treffer)</span>
					</div>
				</div>
				<div class="search-group-content" style="display: none; overflow: hidden;">
	{foreach from=$thread.messages item=result}
					<div class="py-2 pr-4 text-sm" style="border-bottom: 1px solid var(--color-border-light); padding-left: 2.5rem;">
						<a href="pxmboard.php?mode=board&brdid={$result.boardid}&thrdid={$result.threadid}&msgid={$result.id}"
					   hx-get="pxmboard.php?mode=message&brdid={$result.boardid}&msgid={$result.id}"
					   hx-target="#message-container"
					   hx-swap="innerHTML"
					   hx-push-url="pxmboard.php?mode=board&brdid={$result.boardid}&thrdid={$result.threadid}&msgid={$result.id}"
					   onclick="handleCachedThreadLoad({$result.boardid},{$result.id},{$result.threadid})"
					   class="hover:underline htmx-content-link">{$result.subject}</a>
						von <span class="{if $result.user.highlight == 1}font-medium{/if}" {if $result.user.highlight == 1}style="color: var(--color-accent-deep);"{/if}>{$result.user.username}</span>
						<span class="text-xs" style="color: var(--color-content-secondary);">am {$result.date}</span>
						{if $result.score>0}<span class="text-xs ml-1" style="color: var(--color-content-secondary);">(Relevanz: {$result.score})</span>{/if}
					</div>
	{/foreach}
				</div>
			</div>
	{/foreach}
	</div>
	<script>
	function searchGroupToggle(header) {
		var content = header.nextElementSibling;
		var isOpen = content.style.display !== 'none';
		var groups = header.closest('.search-groups');
		if (groups) {
			groups.querySelectorAll('.search-group-content').forEach(function(c) { c.style.display = 'none'; });
			groups.querySelectorAll('.group-arrow').forEach(function(a) { a.style.transform = ''; });
		}
		if (!isOpen) {
			content.style.display = 'block';
			header.querySelector('.group-arrow').style.transform = 'rotate(90deg)';
		}
	}
	(function() {
		var first = document.querySelector('.search-groups > div > div:first-child');
		if (first) searchGroupToggle(first);
	})();
	</script>
	</div>
</div>

<!-- Pagination -->
{if $config.count > 1}
<div class="text-center py-3 text-xs" style="color: var(--color-content-secondary);">
{if isset($config.previd) && $config.previd != ''}
		<a href="pxmboard.php?mode=messagesearch&brdid={$config.board.id}&searchid={$config.searchprofile.id}&page={$config.previd}"
		   hx-get="pxmboard.php?mode=messagesearch&brdid={$config.board.id}&searchid={$config.searchprofile.id}&page={$config.previd}"
		   hx-target="#threadlist-container"
		   hx-swap="innerHTML"
		   class="hover:underline" style="color: var(--color-link);">&laquo; Zur&uuml;ck</a> |
{/if}
	{section name=page start=1 loop=$config.count}
		{if $config.curid == $smarty.section.page.index}
			<span class="font-bold underline">{$smarty.section.page.index}</span>
		{else}
			<a href="pxmboard.php?mode=messagesearch&brdid={$config.board.id}&searchid={$config.searchprofile.id}&page={$smarty.section.page.index}"
			   hx-get="pxmboard.php?mode=messagesearch&brdid={$config.board.id}&searchid={$config.searchprofile.id}&page={$smarty.section.page.index}"
			   hx-target="#threadlist-container"
			   hx-swap="innerHTML"
			   class="hover:underline" style="color: var(--color-link);">{$smarty.section.page.index}</a>
		{/if}
	{/section}
	{if $config.curid == $config.count}
		<span class="font-bold underline">{$config.count}</span>
	{else}
		<a href="pxmboard.php?mode=messagesearch&brdid={$config.board.id}&searchid={$config.searchprofile.id}&page={$config.count}"
		   hx-get="pxmboard.php?mode=messagesearch&brdid={$config.board.id}&searchid={$config.searchprofile.id}&page={$config.count}"
		   hx-target="#threadlist-container"
		   hx-swap="innerHTML"
		   class="hover:underline" style="color: var(--color-link);">{$config.count}</a>
	{/if}
{if isset($config.nextid) && $config.nextid != ''}
		 | <a href="pxmboard.php?mode=messagesearch&brdid={$config.board.id}&searchid={$config.searchprofile.id}&page={$config.nextid}"
		   hx-get="pxmboard.php?mode=messagesearch&brdid={$config.board.id}&searchid={$config.searchprofile.id}&page={$config.nextid}"
		   hx-target="#threadlist-container"
		   hx-swap="innerHTML"
		   class="hover:underline" style="color: var(--color-link);">Weiter &raquo;</a>
{/if}
</div>
{/if}
{if $config.logedin == 1}<span id="badge-data" data-pm="{$config.user.priv_message_unread_count}" data-notif="{$config.user.notification_unread_count}" hidden></span>{/if}
