{*
  User Profile Edit Form Template (Partial) - HTMX Skin

  Form for editing own profile with personal info, contact details,
  and profile image upload.

  Parameters:
  - $user: User data (username, fname, lname, city, email, icq, url,
           hobby, imgfile)
  - $config.profile_img_dir: Path to profile image directory
  - $error: Array of error messages (optional)
*}

		<form enctype="multipart/form-data" action="pxmboard.php" method="post" hx-post="pxmboard.php" hx-target="#htmxModalBody" hx-encoding="multipart/form-data">
			<input type="hidden" name="mode" value="userprofilesave"/>

			<!-- Header -->
			<div class="px-4 py-2 rounded-t-lg font-semibold" style="background-color: var(--color-surface-header); color: var(--color-content-inverse); border: 1px solid var(--color-border-default); border-bottom: 0;">
				Profil bearbeiten f&uuml;r {$user.username}
			</div>

			<div class="rounded-b-lg p-5 shadow space-y-4" style="border: 1px solid var(--color-border-default); border-top: 0; background-color: var(--color-surface-primary);">

				{include file="partial_inline_errors.tpl"}

				<!-- Profile Image -->
				<div class="flex items-start gap-4 pb-2" style="border-bottom: 1px solid var(--color-border-light);">
					<div class="shrink-0">
						{if $user.imgfile != ''}
							<img src="{$config.profile_img_dir}{$user.imgfile}" alt="{$user.username}" class="w-20 h-20 rounded-lg object-cover" style="border: 1px solid var(--color-border-light);">
						{else}
							<div class="w-20 h-20 rounded-lg flex items-center justify-center" style="background-color: var(--color-surface-secondary); border: 1px solid var(--color-border-light);">
								<svg class="w-10 h-10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round" style="color: var(--color-content-secondary);">
									<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
									<circle cx="12" cy="7" r="4"/>
								</svg>
							</div>
						{/if}
					</div>
					<div class="flex-1 space-y-2">
						<label class="block htmx-formlabel">Profilbild</label>
						<input type="file" name="pic" size="18" maxlength="150" class="text-sm" style="color: var(--color-content-primary);"/>
						<label class="flex items-center gap-1.5 text-xs cursor-pointer" style="color: var(--color-content-secondary);">
							<input type="checkbox" name="delpic" value="1"/>
							Bild l&ouml;schen?
						</label>
					</div>
				</div>

				<!-- Personal Info Fields -->
				<div class="space-y-3">
					<div class="flex items-center gap-3">
						<label class="w-28 shrink-0 htmx-formlabel">Vorname</label>
						<input type="text" name="fname" value="{$user.fname}" size="30" maxlength="{$config.input_sizes.firstname}" class="rounded px-3 py-1.5 text-sm flex-1 focus:outline-none focus:ring-1" style="border: 1px solid var(--color-border-default); background-color: var(--color-surface-primary); color: var(--color-content-primary); --tw-ring-color: var(--color-accent);"/>
					</div>
					<div class="flex items-center gap-3">
						<label class="w-28 shrink-0 htmx-formlabel">Nachname</label>
						<input type="text" name="lname" value="{$user.lname}" size="30" maxlength="{$config.input_sizes.lastname}" class="rounded px-3 py-1.5 text-sm flex-1 focus:outline-none focus:ring-1" style="border: 1px solid var(--color-border-default); background-color: var(--color-surface-primary); color: var(--color-content-primary); --tw-ring-color: var(--color-accent);"/>
					</div>
					<div class="flex items-center gap-3">
						<label class="w-28 shrink-0 htmx-formlabel">Wohnort</label>
						<input type="text" name="city" value="{$user.city}" size="30" maxlength="{$config.input_sizes.city}" class="rounded px-3 py-1.5 text-sm flex-1 focus:outline-none focus:ring-1" style="border: 1px solid var(--color-border-default); background-color: var(--color-surface-primary); color: var(--color-content-primary); --tw-ring-color: var(--color-accent);"/>
					</div>
					<div class="flex items-center gap-3">
						<label class="w-28 shrink-0 htmx-formlabel">Email</label>
						<input type="text" name="email" value="{$user.email}" size="30" maxlength="{$config.input_sizes.email}" class="rounded px-3 py-1.5 text-sm flex-1 focus:outline-none focus:ring-1" style="border: 1px solid var(--color-border-default); background-color: var(--color-surface-primary); color: var(--color-content-primary); --tw-ring-color: var(--color-accent);"/>
					</div>
					<div class="flex items-center gap-3">
						<label class="w-28 shrink-0 htmx-formlabel">Homepage</label>
						<input type="text" name="url" value="{$user.url}" size="30" maxlength="50" class="rounded px-3 py-1.5 text-sm flex-1 focus:outline-none focus:ring-1" style="border: 1px solid var(--color-border-default); background-color: var(--color-surface-primary); color: var(--color-content-primary); --tw-ring-color: var(--color-accent);"/>
					</div>
					<div class="flex gap-3">
						<label class="w-28 pt-1.5 shrink-0 htmx-formlabel">Hobbys</label>
						<textarea cols="29" rows="5" name="hobby" wrap="physical" class="rounded px-3 py-1.5 text-sm flex-1 focus:outline-none focus:ring-1" style="border: 1px solid var(--color-border-default); background-color: var(--color-surface-primary); color: var(--color-content-primary); --tw-ring-color: var(--color-accent);">{$user.hobby}</textarea>
					</div>
				</div>

				<!-- Submit -->
				<div class="text-center pt-2">
					<button type="submit" class="htmx-btn-primary text-sm px-8 py-2">Speichern</button>
				</div>
			</div>
		</form>
