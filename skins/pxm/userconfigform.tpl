{*
  User Configuration Form Template (Partial) - HTMX Skin

  User settings form with grouped sections: Display, Email/Notifications,
  and Signature editor.

  Parameters:
  - $user: User data (username, skin, privatemail, sort, toff,
           embed_external, visible, privnotification, _signature)
  - $skin: Array of available skins (each with .id and .name)
  - $error: Array of error messages (optional)
*}

		<form action="pxmboard.php" method="post" hx-post="pxmboard.php" hx-target="#htmxModalBody">
			<input type="hidden" name="mode" value="userconfigsave"/>

			<!-- Header -->
			<div class="px-4 py-2 rounded-t-lg font-semibold" style="background-color: var(--color-surface-header); color: var(--color-content-inverse); border: 1px solid var(--color-border-default); border-bottom: 0;">
				Einstellungen f&uuml;r {$user.username}
			</div>

			<div class="rounded-b-lg shadow" style="border: 1px solid var(--color-border-default); background-color: var(--color-surface-primary);">

				<!-- Inline Errors -->
				<div class="px-5 pt-4">
					{include file="partial_inline_errors.tpl"}
				</div>

				<!-- Section: Anzeige -->
				<div class="px-5 pt-4 pb-2">
					<h3 class="text-xs font-semibold uppercase tracking-wide mb-3" style="color: var(--color-content-secondary);">Anzeige</h3>
					<div class="space-y-3">
						<div class="flex items-center gap-3">
							<label class="w-56 htmx-formlabel">Skin</label>
							<select name="skinid" class="rounded px-3 py-1.5 text-sm focus:outline-none focus:ring-1" style="border: 1px solid var(--color-border-default); background-color: var(--color-surface-primary); color: var(--color-content-primary); --tw-ring-color: var(--color-accent);">
								<option value="0">default</option>
{foreach from=$skin item=skin}
								<option value="{$skin.id}"{if $skin.id == $user.skin} selected="selected"{/if}>{$skin.name}</option>
{/foreach}
							</select>
						</div>
						<div class="flex items-center gap-3">
							<label class="w-56 htmx-formlabel">Sortiermodus</label>
							<select name="sort" class="rounded px-3 py-1.5 text-sm focus:outline-none focus:ring-1" style="border: 1px solid var(--color-border-default); background-color: var(--color-surface-primary); color: var(--color-content-primary); --tw-ring-color: var(--color-accent);">
								<option value=""{if $user.sort == ''} selected="selected"{/if}>default</option>
								<option value="thread"{if $user.sort == 'thread'} selected="selected"{/if}>thread</option>
								<option value="last"{if $user.sort == 'last'} selected="selected"{/if}>last reply</option>
							</select>
						</div>
						<div class="flex items-center gap-3">
							<label class="w-56 htmx-formlabel">Timeoffset (in Stunden)</label>
							<input type="text" name="toff" value="{$user.toff}" size="2" maxlength="2" class="rounded px-3 py-1.5 text-sm w-16 focus:outline-none focus:ring-1" style="border: 1px solid var(--color-border-default); background-color: var(--color-surface-primary); color: var(--color-content-primary); --tw-ring-color: var(--color-accent);"/>
						</div>
						<div class="flex items-center gap-3">
							<label class="w-56 htmx-formlabel">Externe Inhalte einbetten?</label>
							<label class="toggle-switch">
								<input type="checkbox" name="embed_external" value="1"{if $user.embed_external == 1} checked="checked"{/if}/>
								<span class="toggle-switch-track"></span>
							</label>
						</div>
						<div class="flex items-center gap-3">
							<label class="w-56 htmx-formlabel">Sichtbar in Who's Online?</label>
							<label class="toggle-switch">
								<input type="checkbox" name="visible" value="1"{if $user.visible == 1} checked="checked"{/if}/>
								<span class="toggle-switch-track"></span>
							</label>
						</div>
					</div>
				</div>

				<!-- Divider -->
				<div class="mx-5 my-3" style="border-top: 1px solid var(--color-border-light);"></div>

				<!-- Section: Email & Benachrichtigungen -->
				<div class="px-5 pb-2">
					<h3 class="text-xs font-semibold uppercase tracking-wide mb-3" style="color: var(--color-content-secondary);">Email &amp; Benachrichtigungen</h3>
					<div class="space-y-3">
						<div class="flex items-center gap-3">
							<label class="w-56 htmx-formlabel">Private Mail-Adresse</label>
							<input type="text" name="email" value="{$user.privatemail}" size="30" maxlength="{$config.input_sizes.email}" class="rounded px-3 py-1.5 text-sm flex-1 focus:outline-none focus:ring-1" style="border: 1px solid var(--color-border-default); background-color: var(--color-surface-primary); color: var(--color-content-primary); --tw-ring-color: var(--color-accent);"/>
						</div>
						<div class="flex items-center gap-3">
							<label class="w-56 htmx-formlabel">PM-Benachrichtigung per Email?</label>
							<label class="toggle-switch">
								<input type="checkbox" name="privnotification" value="1"{if $user.privnotification == 1} checked="checked"{/if}/>
								<span class="toggle-switch-track"></span>
							</label>
						</div>
					</div>
				</div>

				<!-- Divider -->
				<div class="mx-5 my-3" style="border-top: 1px solid var(--color-border-light);"></div>

				<!-- Section: Signatur -->
				<div class="px-5 pb-4">
					<h3 class="text-xs font-semibold uppercase tracking-wide mb-3" style="color: var(--color-content-secondary);">Signatur</h3>
					{include file="partial_editor.tpl" editor_content=$user._signature|default:''}
				</div>

				<!-- Submit -->
				<div class="px-5 pb-5 text-center">
					<button type="submit" class="htmx-btn-primary text-sm px-8 py-2">Speichern</button>
				</div>
			</div>
		</form>
