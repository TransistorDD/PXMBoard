{*
  Send Password Form Template (Partial) - HTMX Skin

  Form for requesting a password reset. Loaded into modal via openModal().

  Parameters:
  - $config.board.id: Current board ID (optional)
  - $error: Array of error messages (optional)
*}
<form action="pxmboard.php" method="post"
      hx-post="pxmboard.php"
      hx-target="#htmxModalBody">
	<input type="hidden" name="mode" value="usersendpwd"/>
	<input type="hidden" name="brdid" value="{if isset($config.board)}{$config.board.id}{/if}"/>

	<!-- Header -->
	<div class="px-4 py-2 rounded-t-lg font-semibold" style="background-color: var(--color-surface-header); color: var(--color-content-inverse);">
		Anmeldedaten anfordern
	</div>

	<div class="rounded-b-lg p-4 space-y-4 shadow" style="border: 1px solid var(--color-border-default); border-top: 0; background-color: var(--color-surface-primary);">

		{include file="partial_inline_errors.tpl"}

		<!-- Nutzername -->
		<div>
			<label class="block mb-1 htmx-formlabel">Nutzername</label>
			<input type="text" name="username" size="30" maxlength="{$config.input_sizes.username}"
			       class="w-full rounded px-3 py-2 text-sm focus:outline-none focus:ring-1"
			       style="border: 1px solid var(--color-border-default); background-color: var(--color-surface-primary); color: var(--color-content-primary); --tw-ring-color: var(--color-accent);"
			       placeholder="Benutzername eingeben..."/>
		</div>

		<!-- E-Mail -->
		<div>
			<label class="block mb-1 htmx-formlabel">E-Mail bei Registrierung</label>
			<input type="text" name="email" size="30" maxlength="{$config.input_sizes.email}"
			       class="w-full rounded px-3 py-2 text-sm focus:outline-none focus:ring-1"
			       style="border: 1px solid var(--color-border-default); background-color: var(--color-surface-primary); color: var(--color-content-primary); --tw-ring-color: var(--color-accent);"
			       placeholder="E-Mail-Adresse eingeben..."/>
		</div>

		<!-- Submit -->
		<div class="text-center pt-2">
			<button type="submit" class="htmx-btn-primary text-sm px-8 py-2 rounded font-medium">Passwort anfordern</button>
		</div>
	</div>
</form>
