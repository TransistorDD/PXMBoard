{*
  Change Password Form Template (Partial) - HTMX Skin

  Form for changing the user's password. Requires current password (not present in pxm skin,
  but specified for htmx), new password, and confirmation.

  Parameters:
  - $config.user.username: Current user's username
  - $error: Array of error messages (optional)
*}

		<form action="pxmboard.php" method="post" hx-post="pxmboard.php" hx-target="#htmxModalBody">
			<input type="hidden" name="mode" value="userchangepwd"/>

			<!-- Header -->
			<div class="px-4 py-2 rounded-t-lg font-semibold" style="background-color: var(--color-surface-header); color: var(--color-content-inverse);">
				Passwort f&uuml;r {$config.user.username}
			</div>

			<div class="rounded-b-lg p-4 space-y-4 shadow" style="border: 1px solid var(--color-border-default); border-top: 0; background-color: var(--color-surface-primary);">

				{include file="partial_inline_errors.tpl"}

				<!-- Aktuelles Passwort -->
				<div>
					<label class="block mb-1 htmx-formlabel">Aktuelles Passwort</label>
					<input type="password" name="oldpwd" size="20" maxlength="{$config.input_sizes.password}"
					       class="w-full rounded px-3 py-2 text-sm focus:outline-none focus:ring-1"
					       style="border: 1px solid var(--color-border-default); background-color: var(--color-surface-primary); color: var(--color-content-primary); --tw-ring-color: var(--color-accent);"/>
				</div>

				<!-- Neues Passwort -->
				<div>
					<label class="block mb-1 htmx-formlabel">Neues Passwort</label>
					<input type="password" name="pwd" size="20" maxlength="{$config.input_sizes.password}"
					       class="w-full rounded px-3 py-2 text-sm focus:outline-none focus:ring-1"
					       style="border: 1px solid var(--color-border-default); background-color: var(--color-surface-primary); color: var(--color-content-primary); --tw-ring-color: var(--color-accent);"/>
				</div>

				<!-- Passwort bestaetigen -->
				<div>
					<label class="block mb-1 htmx-formlabel">Passwort best&auml;tigen</label>
					<input type="password" name="pwdc" size="20" maxlength="{$config.input_sizes.password}"
					       class="w-full rounded px-3 py-2 text-sm focus:outline-none focus:ring-1"
					       style="border: 1px solid var(--color-border-default); background-color: var(--color-surface-primary); color: var(--color-content-primary); --tw-ring-color: var(--color-accent);"/>
				</div>

				<!-- Submit -->
				<div class="text-center pt-2">
					<button type="submit" class="htmx-btn-primary text-sm px-8 py-2 rounded font-medium">Passwort aendern</button>
				</div>
			</div>
		</form>

