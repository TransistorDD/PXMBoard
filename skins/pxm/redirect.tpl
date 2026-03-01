{*
  Redirect Template (Partial) - HTMX Skin

  Shown after an action that requires a full-page redirect.
  Renders a message and triggers a JS redirect after 5 seconds.

  Parameters:
  - $redirect_url: Target URL for redirect (required)
  - $message: Redirect message (optional)
  - $config.webmaster: Webmaster email (required)
*}
<div class="w-full max-w-md mx-auto">
	<!-- Redirect Card -->
	<div class="px-4 py-2 rounded-t-lg font-semibold" style="background-color: var(--color-surface-header); color: var(--color-content-inverse); border: 1px solid var(--color-border-default); border-bottom: 0;">
		Weiterleitung
	</div>
	<div class="p-4 text-center" style="border-left: 1px solid var(--color-border-default); border-right: 1px solid var(--color-border-default); background-color: var(--color-surface-primary); color: var(--color-content-primary);">
		{if $message}
			{$message}
		{else}
			Die Aktion wurde erfolgreich ausgef&uuml;hrt. Sie werden weitergeleitet...
		{/if}
		<div class="mt-3 text-xs" style="color: var(--color-content-secondary);">
			Falls die automatische Weiterleitung nicht funktioniert, klicken Sie <a href="{$redirect_url}" class="hover:underline" style="color: var(--color-link);">hier</a>.
		</div>
	</div>
	<div class="rounded-b-lg px-4 py-2 text-center text-xs" style="border: 1px solid var(--color-border-default); border-top: 0; background-color: var(--color-surface-primary); color: var(--color-content-secondary);">
		<a href="mailto:{$config.webmaster}" class="hover:underline" style="color: var(--color-link);">Mail Webmaster</a>
	</div>
</div>

<script>
	setTimeout(function() {ldelim}
		window.location.href = '{$redirect_url}';
	{rdelim}, 5000);
</script>
