{*
  Error Template (Partial) - HTMX Skin

  Always renders as inline fragment (no layout wrapper).

  Parameters:
  - $error.text: Error message (required)
  - $config.webmaster: Webmaster email (required)
*}

			<!-- Error Card -->
			<div class="px-4 py-2 rounded-t-lg font-semibold" style="background-color: var(--color-accent-danger); color: var(--color-content-inverse); border: 1px solid var(--color-accent-danger); border-bottom: 0;">
				Fehler
			</div>
			<div class="p-4" style="border-left: 1px solid var(--color-border-default); border-right: 1px solid var(--color-border-default); background-color: var(--color-surface-primary); color: var(--color-content-primary);">
				{$error.text}
			</div>
			<div class="rounded-b-lg px-4 py-2 text-center text-xs" style="border: 1px solid var(--color-border-default); border-top: 0; background-color: var(--color-surface-primary); color: var(--color-content-secondary);">
				<a href="mailto:{$config.webmaster}" class="hover:underline" style="color: var(--color-link);">Mail Webmaster</a>
			</div>
