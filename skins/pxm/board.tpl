{include file="layout_header.tpl"}

	<!-- Board Layout -->
	<main id="main-content" class="flex-1 min-h-0">
		<div id="board-layout" class="htmx-board-layout">

			<!-- Threadliste (per HTMX automatisch geladen) -->
			<div id="threadlist-container" class="m-3 rounded-lg bg-surface-primary border border-border-light"
				 hx-get="pxmboard.php?mode=threadlist&brdid={$config.board.id}"
				 hx-trigger="load"
				 hx-swap="innerHTML">
			</div>

			<!-- Teiler H1: Threadliste / Thread-Baum (Stacked) bzw. Threadliste / Nachricht (Seite) -->
			<div class="htmx-divider" data-divider="h"></div>

			<!-- Detail-Page: Message + Thread (fuer Mobile als zweite Seite) -->
			<div id="detail-page">

				<!-- Nachricht (per HTMX geladen wenn msgid vorhanden) -->
				<div id="message-container" class="m-3"
					 {if isset($config.msgid) && $config.msgid > 0}
					 hx-get="pxmboard.php?mode=message&brdid={$config.board.id}&msgid={$config.msgid}"
					 hx-trigger="load"
					 hx-swap="innerHTML"
					 {/if}>
				</div>

				<!-- Teiler H2: Nachricht / Thread-Baum (nur Stacked) -->
				<div class="htmx-divider" data-divider="h2"></div>

				<!-- Thread-Baum (per HTMX geladen wenn thrdid vorhanden) -->
				<div id="thread-container" class="m-3 rounded-lg bg-surface-primary border border-border-light"
					 {if isset($config.thrdid) && $config.thrdid > 0}
					 hx-get="pxmboard.php?mode=thread&brdid={$config.board.id}&thrdid={$config.thrdid}"
					 hx-trigger="load"
					 hx-swap="innerHTML"
					 {/if}>
				</div>

			</div>

			<!-- Teiler V: Inhalt / Thread-Baum (nur Seite) -->
			<div class="htmx-divider" data-divider="v"></div>

		</div>
	</main>

	<!-- Mobile Footer Navigation -->
	{if isset($config.board)}
	<nav id="mobile-footer" class="htmx-mobile-footer">
		<button onclick="mobileGoBack()" class="htmx-mobile-footer-btn" title="Zur&uuml;ck">
			<svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
		</button>
		{if isset($boards) && isset($boards.board)}
		<div x-data="{ldelim} open: false {rdelim}" class="relative">
			<button @click="open = !open" class="flex items-center gap-1 border border-border-default rounded px-3 py-1 text-sm bg-surface-primary text-content-primary hover:bg-hover-bg transition-colors">
				<span>{$config.board.name}</span>
				<svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
			</button>
			<div x-show="open" @click.outside="open = false" x-transition class="absolute left-0 bottom-full mb-1 w-48 rounded-lg shadow-xl z-50 py-1 bg-surface-primary text-content-primary">
				{foreach from=$boards.board item=board}
				{if $board.status != 5}
				<a href="pxmboard.php?mode=board&brdid={$board.id}" class="block px-3 py-1.5 text-sm hover:bg-hover-bg text-content-primary {if $config.board.id == $board.id}font-semibold{/if}">{$board.name}</a>
				{/if}
				{/foreach}
			</div>
		</div>
		{/if}
		<a onclick="mobileNewPost({$config.board.id}); return false;" href="pxmboard.php?mode=messageform&brdid={$config.board.id}" class="htmx-mobile-footer-newpost">Neuer Beitrag</a>
	</nav>
	{/if}

{if $config.admin == 1 or $config.moderator == 1}
	<!-- Move Badge: shown when a message is selected for moving -->
	<div id="move-badge" class="hidden fixed bottom-0 left-0 right-0 z-50 flex items-center gap-3 px-4 py-2.5 text-sm border-t border-border-default bg-accent-warning text-content-primary">
		<svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 9l4-4 4 4"/><path d="M9 5v14"/><path d="M19 15l-4 4-4-4"/><path d="M15 19V5"/></svg>
		<span id="move-badge-message" class="flex-1 font-medium"></span>
		<button id="btn-cancel-move" class="px-3 py-1 rounded text-xs font-medium bg-black/10 hover:bg-black/20 transition-colors">Auswahl aufheben</button>
	</div>
{/if}

{include file="layout_footer.tpl"}
