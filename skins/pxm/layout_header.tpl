<!DOCTYPE html>
<html lang="de" data-theme="light">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>{if isset($config.board) && $config.board.name}{$config.board.name} - {/if}PXMBoard</title>
	<link href="css/pxmboard.css?v={$config.css_version}" rel="stylesheet">
	<link rel="manifest" href="manifest.json">
	<meta name="theme-color" content="#17A2B8">
	<meta name="csrf-token" content="{$config.csrf_token}">
	<meta name="mobile-web-app-capable" content="yes">
	<meta name="apple-mobile-web-app-capable" content="yes">
	<meta name="apple-mobile-web-app-status-bar-style" content="default">
	<meta name="apple-mobile-web-app-title" content="PXMBoard">
	<link rel="apple-touch-icon" href="images/pwa-icon-192.png">
	<script>
	if ('serviceWorker' in navigator) {
		navigator.serviceWorker.register('sw.js');
	}
	</script>
</head>
<body class="h-dvh flex flex-col overflow-hidden bg-surface-secondary text-content-primary">

	<!-- Globaler HTMX Lade-Indicator -->
	<div id="loading-indicator" class="htmx-indicator htmx-loading-bar">
	</div>

	<!-- Header -->
	<header class="htmx-header {if isset($config.board)}h-14{else}h-20{/if} flex items-center px-4 shrink-0 relative">
		<!-- Logo -->
		<a href="pxmboard.php" class="flex items-center shrink-0">
			<img src="images/pxmboard_logo.png" alt="PXMBoard Logo" class="{if isset($config.board)}h-8{else}h-14{/if}">
		</a>

		<!-- Willkommenstext (nur Boardliste) -->
		{if !isset($config.board) && $config.logedin == 1}
		<div class="ml-4 text-base font-medium">Herzlich Willkommen {$config.user.username}</div>
		{/if}

		<!-- Board-Auswahl (nur auf Boardseite, in Mobile im Footer) -->
		{if isset($config.board) && isset($boards) && isset($boards.board)}
		<div x-data="{ldelim} open: false {rdelim}" class="ml-3 relative mobile-hide">
			<button @click="open = !open" class="flex items-center gap-1 border border-white/30 bg-white/20 rounded px-2 py-0.5 text-xs hover:bg-white/30 transition-colors">
				<span>{if isset($config.board)}{$config.board.name}{else}Board{/if}</span>
				<svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
			</button>
			<div x-show="open" @click.outside="open = false" x-transition class="absolute left-0 mt-1 w-48 rounded-lg shadow-xl z-50 py-1 bg-surface-primary text-content-primary">
				{foreach from=$boards.board item=board}
				{if $board.status != 5}
				<a href="pxmboard.php?mode=board&brdid={$board.id}" class="block px-3 py-1.5 text-sm hover:bg-hover-bg text-content-primary {if isset($config.board) && $config.board.id == $board.id}font-semibold{/if}">{$board.name}</a>
				{/if}
				{/foreach}
			</div>
		</div>
		{/if}

		<!-- Neuer Beitrag Button (in Mobile im Footer) -->
		{if isset($config.board)}
		<a href="pxmboard.php?mode=messageform&brdid={$config.board.id}"
		   hx-get="pxmboard.php?mode=messageform&brdid={$config.board.id}"
		   hx-target="#message-container"
		   hx-swap="innerHTML"
		   class="mobile-hide rounded-full px-4 py-1.5 text-xs font-medium absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 bg-accent-danger text-content-inverse hover:bg-accent-danger-hover transition-colors">Neuer Beitrag</a>
		{/if}

		<!-- Navigation Icons -->
		<nav class="ml-auto flex items-center gap-3">

			<!-- Suche -->
			{if isset($config.board)}
			<a href="pxmboard.php?mode=messagesearch&brdid={$config.board.id}"
			   hx-get="pxmboard.php?mode=messagesearch&brdid={$config.board.id}"
			   hx-target="#threadlist-container"
			   hx-swap="innerHTML"
			   hx-push-url="true"
			   class="flex items-center justify-center hover:opacity-75 transition-opacity"
			   title="Suche">
				<svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
			</a>
			{/if}

			{if $config.logedin == 1}
				<!-- Online-Liste -->
				{if isset($config.board)}
				<a href="pxmboard.php?mode=useronline&brdid={$config.board.id}"
				   hx-get="pxmboard.php?mode=useronline&brdid={$config.board.id}"
				   hx-target="#htmxModalBody"
				   hx-swap="innerHTML"
				   data-modal-title="Online-Liste"
				   hx-on::before-request="document.getElementById('htmxModalTitle').textContent=this.dataset.modalTitle;document.getElementById('htmxModal').showModal();"
				   class="flex items-center justify-center hover:opacity-75 transition-opacity"
				   title="Online-Liste">
					<svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
				</a>

				<!-- Benutzersuche -->
				<a href="pxmboard.php?mode=usersearch&brdid={$config.board.id}"
				   hx-get="pxmboard.php?mode=usersearch&brdid={$config.board.id}"
				   hx-target="#htmxModalBody"
				   hx-swap="innerHTML"
				   data-modal-title="Benutzersuche"
				   hx-on::before-request="document.getElementById('htmxModalTitle').textContent=this.dataset.modalTitle;document.getElementById('htmxModal').showModal();"
				   class="flex items-center justify-center hover:opacity-75 transition-opacity"
				   title="Benutzersuche">
					<svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/><circle cx="11" cy="9" r="2.5"/><path d="M7.5 15a4.5 4.5 0 0 1 7 0"/></svg>
				</a>

				<!-- Separator -->
				<div class="h-5 w-px opacity-30 shrink-0 bg-current"></div>
				{/if}

				<!-- Private Nachrichten -->
				<a href="pxmboard.php?mode=privatemessagelist"
				   hx-get="pxmboard.php?mode=privatemessagelist"
				   hx-target="#htmxModalBody"
				   hx-swap="innerHTML"
				   data-modal-title="Private Nachrichten"
				   hx-on::before-request="document.getElementById('htmxModalTitle').textContent=this.dataset.modalTitle;document.getElementById('htmxModal').showModal();"
				   class="relative flex items-center justify-center hover:opacity-75 transition-opacity"
				   title="Private Nachrichten">
					<svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
					<span id="pm-badge" class="absolute -top-2 -right-3 text-xs rounded-full h-4 w-4 flex items-center justify-center bg-accent-danger text-content-inverse{if $config.user.priv_message_unread_count <= 0} hidden{/if}">{$config.user.priv_message_unread_count}</span>
				</a>

				<!-- Benachrichtigungen -->
				<a href="pxmboard.php?mode=notificationlist"
				   hx-get="pxmboard.php?mode=notificationlist"
				   hx-target="#htmxModalBody"
				   hx-swap="innerHTML"
				   data-modal-title="Benachrichtigungen"
				   hx-on::before-request="document.getElementById('htmxModalTitle').textContent=this.dataset.modalTitle;document.getElementById('htmxModal').showModal();"
				   class="relative flex items-center justify-center hover:opacity-75 transition-opacity"
				   title="Benachrichtigungen">
					<svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>
					<span id="notification-badge" class="absolute -top-2 -right-3 text-xs rounded-full h-4 w-4 flex items-center justify-center bg-accent-danger text-content-inverse{if $config.user.notification_unread_count <= 0} hidden{/if}">{$config.user.notification_unread_count}</span>
				</a>

			{else}
				{if isset($config.board)}
				<!-- Login (nur auf Boardseite) -->
				<a href="pxmboard.php?mode=login"
				   class="flex items-center justify-center hover:opacity-75 transition-opacity"
				   title="Anmelden">
					<svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
				</a>

				<!-- Registrieren Icon (nur auf Boardseite) -->
				<a href="pxmboard.php?mode=userregistration"
				   hx-get="pxmboard.php?mode=userregistration"
				   hx-target="#htmxModalBody"
				   hx-swap="innerHTML"
				   data-modal-title="Registrieren"
				   hx-on::before-request="document.getElementById('htmxModalTitle').textContent=this.dataset.modalTitle;document.getElementById('htmxModal').showModal();"
				   class="flex items-center justify-center hover:opacity-75 transition-opacity"
				   title="Registrieren">
					<svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>
				</a>
				{else}
				<!-- Textlinks (nur auf Boardlistseite) -->
				<a href="pxmboard.php?mode=usersendpwd"
				   hx-get="pxmboard.php?mode=usersendpwd"
				   hx-target="#htmxModalBody"
				   hx-swap="innerHTML"
				   data-modal-title="Passwort anfordern"
				   hx-on::before-request="document.getElementById('htmxModalTitle').textContent=this.dataset.modalTitle;document.getElementById('htmxModal').showModal();"
				   class="text-sm hover:opacity-75 transition-opacity">Passwort vergessen?</a>
				<a href="pxmboard.php?mode=userregistration"
				   hx-get="pxmboard.php?mode=userregistration"
				   hx-target="#htmxModalBody"
				   hx-swap="innerHTML"
				   data-modal-title="Registrieren"
				   hx-on::before-request="document.getElementById('htmxModalTitle').textContent=this.dataset.modalTitle;document.getElementById('htmxModal').showModal();"
				   class="text-sm hover:opacity-75 transition-opacity">Registrieren</a>
				{/if}
			{/if}

			<!-- Einstellungen (Zahnrad) -->
			<div x-data="{ldelim} open: false {rdelim}" class="relative">
				<button @click="open = !open"
				        class="flex items-center justify-center hover:opacity-75 transition-opacity"
				        title="Einstellungen">
					<svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
				</button>
				<div x-show="open" @click.outside="open = false" x-transition class="absolute right-0 mt-2 w-56 rounded-lg shadow-xl z-50 py-2 bg-surface-primary text-content-primary">
					<!-- Design -->
					<div class="px-3 pt-1 pb-2 text-xs font-semibold uppercase tracking-wide text-content-secondary">Design</div>
					<div class="flex items-center justify-around px-3 pb-2">
						<button data-theme-option="light" onclick="setTheme('light')" @click="open = false" class="flex flex-col items-center gap-1 px-2 py-1.5 rounded-lg text-xs transition-colors text-content-primary">
							<svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M4.93 4.93l1.41 1.41m11.32 11.32 1.41 1.41M2 12h2m16 0h2M6.34 17.66l-1.41 1.41m12.73-12.73 1.41-1.41"/></svg>
							<span class="flex items-center gap-0.5"><span data-checkmark></span>Hell</span>
						</button>
						<button data-theme-option="dark" onclick="setTheme('dark')" @click="open = false" class="flex flex-col items-center gap-1 px-2 py-1.5 rounded-lg text-xs transition-colors text-content-primary">
							<svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
							<span class="flex items-center gap-0.5"><span data-checkmark></span>Dunkel</span>
						</button>
						<button data-theme-option="auto" onclick="setTheme('auto')" @click="open = false" class="flex flex-col items-center gap-1 px-2 py-1.5 rounded-lg text-xs transition-colors text-content-primary">
							<svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
							<span class="flex items-center gap-0.5"><span data-checkmark></span>Auto</span>
						</button>
					</div>
					<!-- Ansicht -->
					<div class="mx-3 my-1 border-t border-border-default"></div>
					<div class="px-3 pt-2 pb-2 text-xs font-semibold uppercase tracking-wide text-content-secondary">Ansicht</div>
					<div class="flex items-center justify-around px-3 pb-2">
						<button data-view-option="mobile" onclick="setView('mobile')" @click="open = false" class="flex flex-col items-center gap-1 px-2 py-1.5 rounded-lg text-xs transition-colors text-content-primary" title="Mobile Ansicht">
							<svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="2" width="14" height="20" rx="2"/><line x1="12" y1="18" x2="12" y2="18"/></svg>
							<span class="flex items-center gap-0.5"><span data-checkmark></span>Mobil</span>
						</button>
						<button data-view-option="desktop" onclick="setView('desktop')" @click="open = false" class="flex flex-col items-center gap-1 px-2 py-1.5 rounded-lg text-xs transition-colors text-content-primary" title="Desktop Ansicht">
							<svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
							<span class="flex items-center gap-0.5"><span data-checkmark></span>Desktop</span>
						</button>
						<button data-view-option="auto" onclick="setView('auto')" @click="open = false" class="flex flex-col items-center gap-1 px-2 py-1.5 rounded-lg text-xs transition-colors text-content-primary" title="Automatisch">
							<svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/><rect x="6" y="5" width="5" height="8" rx="1" stroke-dasharray="2 1"/></svg>
							<span class="flex items-center gap-0.5"><span data-checkmark></span>Auto</span>
						</button>
					</div>
					{if isset($config.board)}
					<!-- Anordnung (hidden in mobile view) -->
					<div class="mobile-hide">
					<div class="mx-3 my-1 border-t border-border-default"></div>
					<div class="px-3 pt-2 pb-2 text-xs font-semibold uppercase tracking-wide text-content-secondary">Anordnung</div>
					<div class="flex items-center justify-around px-3 pb-2">
						<button data-layout-option="sidebyside" onclick="setLayout('sidebyside')" @click="open = false" class="flex flex-col items-center gap-1 px-2 py-1.5 rounded-lg text-xs transition-colors text-content-primary" title="Zwei-Spalten-Layout">
							<svg class="w-8 h-6" viewBox="0 0 32 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
								<rect x="1" y="1" width="30" height="22" rx="2"/>
								<line x1="22" y1="1" x2="22" y2="23"/>
								<line x1="1" y1="12" x2="22" y2="12"/>
								<text x="4" y="9" font-size="4" fill="currentColor" stroke="none">TL</text>
								<text x="4" y="20" font-size="4" fill="currentColor" stroke="none">MSG</text>
								<text x="24" y="13" font-size="4" fill="currentColor" stroke="none">THR</text>
							</svg>
							<span class="flex items-center gap-0.5"><span data-checkmark></span>Seite</span>
						</button>
						<button data-layout-option="stacked" onclick="setLayout('stacked')" @click="open = false" class="flex flex-col items-center gap-1 px-2 py-1.5 rounded-lg text-xs transition-colors text-content-primary" title="Gestapeltes Layout">
							<svg class="w-8 h-6" viewBox="0 0 32 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
								<rect x="1" y="1" width="30" height="22" rx="2"/>
								<line x1="1" y1="9" x2="31" y2="9"/>
								<line x1="1" y1="17" x2="31" y2="17"/>
								<text x="12" y="7" font-size="4" fill="currentColor" stroke="none">TL</text>
								<text x="11" y="14" font-size="4" fill="currentColor" stroke="none">THR</text>
								<text x="10" y="22" font-size="4" fill="currentColor" stroke="none">MSG</text>
							</svg>
							<span class="flex items-center gap-0.5"><span data-checkmark></span>Stack</span>
						</button>
						<button data-layout-option="auto" onclick="setLayout('auto')" @click="open = false" class="flex flex-col items-center gap-1 px-2 py-1.5 rounded-lg text-xs transition-colors text-content-primary" title="Automatisch">
							<svg class="w-8 h-6" viewBox="0 0 32 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
								<rect x="1" y="1" width="30" height="22" rx="2"/>
								<line x1="22" y1="1" x2="22" y2="23" stroke-dasharray="2 1.5"/>
								<line x1="1" y1="12" x2="22" y2="12" stroke-dasharray="2 1.5"/>
							</svg>
							<span class="flex items-center gap-0.5"><span data-checkmark></span>Auto</span>
						</button>
					</div>
				</div>{* end mobile-hide *}
				<!-- Aufteilung -->
				<div class="mx-3 my-1 border-t border-border-default"></div>
				<div class="px-3 pt-2 pb-2 text-xs font-semibold uppercase tracking-wide text-content-secondary">Aufteilung</div>
				<div class="flex items-center justify-around px-3 pb-2">
					<button data-split-option="custom" class="flex flex-col items-center gap-1 px-2 py-1.5 rounded-lg text-xs transition-colors text-content-primary" title="Individuelle Aufteilung (per Ziehen einstellen)">
						<svg class="w-8 h-6" viewBox="0 0 32 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
							<rect x="1" y="1" width="30" height="22" rx="2"/>
							<line x1="20" y1="1" x2="20" y2="23"/>
							<line x1="1" y1="9" x2="20" y2="9"/>
							<circle cx="20" cy="12" r="2" fill="currentColor" stroke="none"/>
							<circle cx="10" cy="9" r="2" fill="currentColor" stroke="none"/>
						</svg>
						<span class="flex items-center gap-0.5"><span data-checkmark></span>Individuell</span>
					</button>
					<button data-split-option="auto" onclick="setSplitLayout('auto')" @click="open = false" class="flex flex-col items-center gap-1 px-2 py-1.5 rounded-lg text-xs transition-colors text-content-primary" title="Standard-Aufteilung zurücksetzen">
						<svg class="w-8 h-6" viewBox="0 0 32 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
							<rect x="1" y="1" width="30" height="22" rx="2"/>
							<line x1="20" y1="1" x2="20" y2="23"/>
							<line x1="1" y1="12" x2="20" y2="12"/>
						</svg>
						<span class="flex items-center gap-0.5"><span data-checkmark></span>Standard</span>
					</button>
				</div>
				{/if}{* end isset($config.board) *}
				</div>
			</div>

			{if $config.logedin == 1}
			<!-- Benutzer-Avatar-Dropdown -->
			<div x-data="{ldelim} open: false {rdelim}" class="relative">
				<button @click="open = !open" class="flex items-center hover:opacity-80 transition-opacity" title="{$config.user.username}">
					{if $config.user.imgfile}
						<img src="{$config.profile_img_dir}{$config.user.imgfile}" alt="{$config.user.username}" class="h-7 w-7 rounded-full object-cover">
					{else}
						<svg class="h-7 w-7 rounded-full" viewBox="0 0 28 28" fill="none">
							<circle cx="14" cy="14" r="14" fill="currentColor" opacity="0.2"/>
							<circle cx="14" cy="11" r="4" fill="currentColor" opacity="0.8"/>
							<path d="M5 24c0-4.97 4.03-9 9-9s9 4.03 9 9" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" fill="none" opacity="0.8"/>
						</svg>
					{/if}
				</button>
				<div x-show="open" @click.outside="open = false" @click="open = false" x-transition class="absolute right-0 mt-2 w-48 rounded-lg shadow-xl z-50 py-1 bg-surface-primary text-content-primary">
					<a href="pxmboard.php?mode=userprofileform"
					   hx-get="pxmboard.php?mode=userprofileform"
					   hx-target="#htmxModalBody"
					   hx-swap="innerHTML"
					   data-modal-title="Profil"
					   hx-on::before-request="document.getElementById('htmxModalTitle').textContent=this.dataset.modalTitle;document.getElementById('htmxModal').showModal();"
					   class="block px-4 py-2 text-sm hover:bg-hover-bg text-content-primary">Profil</a>
					<a href="pxmboard.php?mode=messagedraftlist"
					   hx-get="pxmboard.php?mode=messagedraftlist"
					   hx-target="#htmxModalBody"
					   hx-swap="innerHTML"
					   data-modal-title="Entw&uuml;rfe"
					   hx-on::before-request="document.getElementById('htmxModalTitle').textContent=this.dataset.modalTitle;document.getElementById('htmxModal').showModal();"
					   class="block px-4 py-2 text-sm hover:bg-hover-bg text-content-primary">Entw&uuml;rfe</a>
					<a href="pxmboard.php?mode=messagenotificationlist"
					   hx-get="pxmboard.php?mode=messagenotificationlist"
					   hx-target="#htmxModalBody"
					   hx-swap="innerHTML"
					   data-modal-title="Beobachten"
					   hx-on::before-request="document.getElementById('htmxModalTitle').textContent=this.dataset.modalTitle;document.getElementById('htmxModal').showModal();"
					   class="block px-4 py-2 text-sm hover:bg-hover-bg text-content-primary">Beobachten</a>
					<a href="pxmboard.php?mode=userchangepwd"
					   hx-get="pxmboard.php?mode=userchangepwd"
					   hx-target="#htmxModalBody"
					   hx-swap="innerHTML"
					   data-modal-title="Passwort &auml;ndern"
					   hx-on::before-request="document.getElementById('htmxModalTitle').textContent=this.dataset.modalTitle;document.getElementById('htmxModal').showModal();"
					   class="block px-4 py-2 text-sm hover:bg-hover-bg text-content-primary">Passwort</a>
					<a href="pxmboard.php?mode=userdevicelist"
					   hx-get="pxmboard.php?mode=userdevicelist"
					   hx-target="#htmxModalBody"
					   hx-swap="innerHTML"
					   data-modal-title="Aktive Ger&auml;te"
					   hx-on::before-request="document.getElementById('htmxModalTitle').textContent=this.dataset.modalTitle;document.getElementById('htmxModal').showModal();"
					   class="block px-4 py-2 text-sm hover:bg-hover-bg text-content-primary">Aktive Ger&auml;te</a>
					<a href="pxmboard.php?mode=userconfigform"
					   hx-get="pxmboard.php?mode=userconfigform"
					   hx-target="#htmxModalBody"
					   hx-swap="innerHTML"
					   data-modal-title="Einstellungen"
					   hx-on::before-request="document.getElementById('htmxModalTitle').textContent=this.dataset.modalTitle;document.getElementById('htmxModal').showModal();"
					   class="block px-4 py-2 text-sm hover:bg-hover-bg text-content-primary">Einstellungen</a>
					<div class="my-1 border-t border-border-default"></div>
					<a href="pxmboard.php?mode=logout" class="block px-4 py-2 text-sm hover:bg-hover-bg text-content-primary">Abmelden</a>
				</div>
			</div>
			{/if}
		</nav>
	</header>
