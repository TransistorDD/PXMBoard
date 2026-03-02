{*
  User Profile Template (Partial) - HTMX Skin

  Displays a user's public profile with avatar, personal info, contact
  details and admin/moderator actions. Loaded into modal dialog.

  Parameters:
  - $user: User data (username, status, fname, lname, city, msgquan, regdate,
           imgfile, email, url, hobby, lchange, id)
  - $config.profile_img_dir: Path to profile image directory
  - $config.logedin: Login status (1 = logged in)
  - $config.admin: Admin status (1 = admin)
  - $config.moderator: Moderator status (1 = moderator)
  - $config.board: Current board object (optional)
*}

		<!-- Header -->
		<div class="px-4 py-2 rounded-t-lg font-semibold" style="background-color: var(--color-surface-header); color: var(--color-content-inverse); border: 1px solid var(--color-border-default); border-bottom: 0;">
			Userprofil f&uuml;r {$user.username}
			{if $user.status != 1}
				<span style="color: var(--color-accent-danger);"> (gesperrt)</span>
			{/if}
		</div>

		<div class="rounded-b-lg shadow" style="border: 1px solid var(--color-border-default); background-color: var(--color-surface-primary);">

			<!-- Avatar and Basic Info -->
			<div class="flex gap-6 p-5">
				<!-- Avatar -->
				<div class="shrink-0">
					{if $user.imgfile == ''}
						<div class="w-24 h-28 rounded-lg flex items-center justify-center" style="background-color: var(--color-surface-secondary); border: 1px solid var(--color-border-light);">
							<svg class="w-12 h-12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round" style="color: var(--color-content-secondary);">
								<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
								<circle cx="12" cy="7" r="4"/>
							</svg>
						</div>
					{else}
						<img src="{$config.profile_img_dir}{$user.imgfile}" alt="{$user.username}" class="w-24 rounded-lg object-cover" style="border: 1px solid var(--color-border-light);">
					{/if}
				</div>

				<!-- Personal Info -->
				<div class="flex-1 space-y-2">
					<div class="flex">
						<span class="w-36 text-sm font-medium" style="color: var(--color-content-secondary);">Vorname</span>
						<span class="text-sm" style="color: var(--color-content-primary);">{$user.fname}</span>
					</div>
					<div class="flex">
						<span class="w-36 text-sm font-medium" style="color: var(--color-content-secondary);">Nachname</span>
						<span class="text-sm" style="color: var(--color-content-primary);">{$user.lname}</span>
					</div>
					<div class="flex">
						<span class="w-36 text-sm font-medium" style="color: var(--color-content-secondary);">Wohnort</span>
						<span class="text-sm" style="color: var(--color-content-primary);">{$user.city}</span>
					</div>
					<div class="flex">
						<span class="w-36 text-sm font-medium" style="color: var(--color-content-secondary);">Nachrichten</span>
						<span class="text-sm" style="color: var(--color-content-primary);">{$user.msgquan}</span>
					</div>
					<div class="flex">
						<span class="w-36 text-sm font-medium" style="color: var(--color-content-secondary);">Mitglied seit</span>
						<span class="text-sm" style="color: var(--color-content-primary);">{$user.regdate}</span>
					</div>
				</div>
			</div>

			<!-- Contact Info -->
			<div class="px-5 py-4 space-y-2" style="border-top: 1px solid var(--color-border-light);">
				<div class="flex">
					<span class="w-36 text-sm font-medium" style="color: var(--color-content-secondary);">Email</span>
					<a href="mailto:{$user.email}" class="text-sm hover:underline" style="color: var(--color-link);">{$user.email}</a>
				</div>
				<div class="flex">
					<span class="w-36 text-sm font-medium" style="color: var(--color-content-secondary);">Homepage</span>
					{if $user.url}
						<a href="{$user.url}" target="_blank" class="text-sm hover:underline" style="color: var(--color-link);">{$user.url}</a>
					{/if}
				</div>
				<div class="flex">
					<span class="w-36 text-sm font-medium shrink-0" style="color: var(--color-content-secondary);">Hobbys</span>
					<span class="text-sm whitespace-pre-wrap" style="color: var(--color-content-primary);">{$user.hobby}</span>
				</div>
				<div class="flex">
					<span class="w-36 text-sm font-medium" style="color: var(--color-content-secondary);">Letztes Update</span>
					<span class="text-sm" style="color: var(--color-content-primary);">{$user.lchange}</span>
				</div>
			</div>

			<!-- Actions -->
			<div class="px-5 py-4 text-center space-y-2" style="border-top: 1px solid var(--color-border-light);">
				{if $config.logedin == 1}
					<a href="pxmboard.php?mode=privatemessageform{if $config.board|isset}&brdid={$config.board.id}{/if}&toid={$user.id}"
					   hx-get="pxmboard.php?mode=privatemessageform{if $config.board|isset}&brdid={$config.board.id}{/if}&toid={$user.id}"
					   hx-target="#htmxModalBody"
					   class="htmx-btn-primary text-sm px-5 py-1.5 inline-block">Private Nachricht schreiben</a>
				{/if}
				{if $config.admin == 1}
					<div class="pt-1">
						<a href="pxmboard.php?mode=admuserform{if $config.board|isset}&brdid={$config.board.id}{/if}&usrid={$user.id}"
						   target="admin"
						   class="text-xs hover:underline" style="color: var(--color-link);">Userdaten editieren</a>
					</div>
				{/if}
				{if $config.moderator == 1}
					{if $user.status == 1}
						<div>
							<a href="pxmboard.php?mode=userchangestatus{if $config.board|isset}&brdid={$config.board.id}{/if}&usrid={$user.id}"
							   class="text-xs hover:underline" style="color: var(--color-accent-danger);">User sperren</a>
						</div>
					{elseif $user.status == 4}
						<div>
							<a href="pxmboard.php?mode=userchangestatus{if $config.board|isset}&brdid={$config.board.id}{/if}&usrid={$user.id}"
							   class="text-xs hover:underline" style="color: var(--color-accent);">User freigeben</a>
						</div>
					{/if}
				{/if}
			</div>
		</div>
