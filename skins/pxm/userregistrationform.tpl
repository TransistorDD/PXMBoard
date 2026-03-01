{*
  User Registration Form Template (Partial) - HTMX Skin

  New user registration form with required account fields and optional
  profile information.

  Parameters:
  - $config.board: Current board object (optional, for brdid hidden field)
  - $error: Array of error messages (optional)
*}

		<form action="pxmboard.php" method="post" hx-post="pxmboard.php" hx-target="#htmxModalBody">
			<input type="hidden" name="mode" value="userregistration"/>
			<input type="hidden" name="brdid" value="{if isset($config.board)}{$config.board.id}{/if}"/>

			<!-- Section: Anmeldedaten -->
			<div class="px-4 py-2 rounded-t-lg font-semibold" style="background-color: var(--color-surface-header); color: var(--color-content-inverse); border: 1px solid var(--color-border-default); border-bottom: 0;">
				Anmeldedaten
			</div>
			<div class="p-5 space-y-3" style="border-left: 1px solid var(--color-border-default); border-right: 1px solid var(--color-border-default); background-color: var(--color-surface-primary);">

				{include file="partial_inline_errors.tpl"}

				<div class="flex items-center gap-3">
					<label class="w-44 shrink-0 htmx-formlabel">Nutzername *</label>
					<input type="text" name="nick" size="30" maxlength="{$config.input_sizes.username}" class="rounded px-3 py-1.5 text-sm flex-1 focus:outline-none focus:ring-1" style="border: 1px solid var(--color-border-default); background-color: var(--color-surface-primary); color: var(--color-content-primary); --tw-ring-color: var(--color-accent);"/>
				</div>
				<div class="flex items-center gap-3">
					<label class="w-44 shrink-0 htmx-formlabel">Passwort *</label>
					<input type="password" name="pass" size="30" maxlength="{$config.input_sizes.password}" class="rounded px-3 py-1.5 text-sm flex-1 focus:outline-none focus:ring-1" style="border: 1px solid var(--color-border-default); background-color: var(--color-surface-primary); color: var(--color-content-primary); --tw-ring-color: var(--color-accent);"/>
				</div>
				<div class="flex items-center gap-3">
					<label class="w-44 shrink-0 htmx-formlabel">Passwort best&auml;tigen *</label>
					<input type="password" name="pass2" size="30" maxlength="{$config.input_sizes.password}" class="rounded px-3 py-1.5 text-sm flex-1 focus:outline-none focus:ring-1" style="border: 1px solid var(--color-border-default); background-color: var(--color-surface-primary); color: var(--color-content-primary); --tw-ring-color: var(--color-accent);"/>
				</div>
				<div class="flex items-center gap-3">
					<label class="w-44 shrink-0 htmx-formlabel">E-Mail *</label>
					<input type="text" name="email" size="30" maxlength="{$config.input_sizes.email}" class="rounded px-3 py-1.5 text-sm flex-1 focus:outline-none focus:ring-1" style="border: 1px solid var(--color-border-default); background-color: var(--color-surface-primary); color: var(--color-content-primary); --tw-ring-color: var(--color-accent);"/>
				</div>
			</div>

			<!-- Section: Zusaetzliche Informationen -->
			<div class="px-4 py-2 font-semibold" style="background-color: var(--color-surface-header); color: var(--color-content-inverse); border-left: 1px solid var(--color-border-default); border-right: 1px solid var(--color-border-default);">
				Zus&auml;tzliche Informationen
			</div>
			<div class="p-5 space-y-3" style="border-left: 1px solid var(--color-border-default); border-right: 1px solid var(--color-border-default); background-color: var(--color-surface-primary);">
				<div class="flex items-center gap-3">
					<label class="w-44 shrink-0 htmx-formlabel">Vorname</label>
					<input type="text" name="fname" size="30" maxlength="{$config.input_sizes.firstname}" class="rounded px-3 py-1.5 text-sm flex-1 focus:outline-none focus:ring-1" style="border: 1px solid var(--color-border-default); background-color: var(--color-surface-primary); color: var(--color-content-primary); --tw-ring-color: var(--color-accent);"/>
				</div>
				<div class="flex items-center gap-3">
					<label class="w-44 shrink-0 htmx-formlabel">Nachname</label>
					<input type="text" name="lname" size="30" maxlength="{$config.input_sizes.lastname}" class="rounded px-3 py-1.5 text-sm flex-1 focus:outline-none focus:ring-1" style="border: 1px solid var(--color-border-default); background-color: var(--color-surface-primary); color: var(--color-content-primary); --tw-ring-color: var(--color-accent);"/>
				</div>
				<div class="flex items-center gap-3">
					<label class="w-44 shrink-0 htmx-formlabel">Wohnort</label>
					<input type="text" name="city" size="30" maxlength="{$config.input_sizes.city}" class="rounded px-3 py-1.5 text-sm flex-1 focus:outline-none focus:ring-1" style="border: 1px solid var(--color-border-default); background-color: var(--color-surface-primary); color: var(--color-content-primary); --tw-ring-color: var(--color-accent);"/>
				</div>
				<div class="flex items-center gap-3">
					<label class="w-44 shrink-0 htmx-formlabel">&Ouml;ffentliche E-Mail</label>
					<input type="text" name="pubemail" size="30" maxlength="{$config.input_sizes.email}" class="rounded px-3 py-1.5 text-sm flex-1 focus:outline-none focus:ring-1" style="border: 1px solid var(--color-border-default); background-color: var(--color-surface-primary); color: var(--color-content-primary); --tw-ring-color: var(--color-accent);"/>
				</div>
				<div class="flex items-center gap-3">
					<label class="w-44 shrink-0 htmx-formlabel">Homepage</label>
					<input type="text" name="url" size="30" maxlength="50" class="rounded px-3 py-1.5 text-sm flex-1 focus:outline-none focus:ring-1" style="border: 1px solid var(--color-border-default); background-color: var(--color-surface-primary); color: var(--color-content-primary); --tw-ring-color: var(--color-accent);"/>
				</div>
				<div class="flex items-center gap-3">
					<label class="w-44 shrink-0 htmx-formlabel">ICQ</label>
					<input type="text" name="icq" size="30" maxlength="10" class="rounded px-3 py-1.5 text-sm flex-1 focus:outline-none focus:ring-1" style="border: 1px solid var(--color-border-default); background-color: var(--color-surface-primary); color: var(--color-content-primary); --tw-ring-color: var(--color-accent);"/>
				</div>
				<div class="flex gap-3">
					<label class="w-44 pt-1.5 shrink-0 htmx-formlabel">Hobbys</label>
					<textarea cols="29" rows="5" name="hobby" wrap="physical" class="rounded px-3 py-1.5 text-sm flex-1 focus:outline-none focus:ring-1" style="border: 1px solid var(--color-border-default); background-color: var(--color-surface-primary); color: var(--color-content-primary); --tw-ring-color: var(--color-accent);"></textarea>
				</div>
			</div>

			<!-- Agreement & Submit -->
			<div class="rounded-b-lg p-5 space-y-4" style="border: 1px solid var(--color-border-default); border-top: 0; background-color: var(--color-surface-primary);">
				{if $config.agreement|default:false}
				<div class="flex items-start gap-3">
					<input type="checkbox" name="agreement" value="1" id="agreement" class="mt-1"/>
					<label for="agreement" class="htmx-formlabel">
						Ich akzeptiere die <a href="{$config.agreement_url|default:'#'}" target="_blank" class="hover:underline" style="color: var(--color-link);">Nutzungsbedingungen</a>.
					</label>
				</div>
				{/if}

				<div class="text-center">
					<button type="submit" class="htmx-btn-primary text-sm px-8 py-2">Registrieren</button>
				</div>
			</div>
		</form>
