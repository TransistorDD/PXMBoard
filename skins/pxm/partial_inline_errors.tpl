{*
  Inline Errors Partial - HTMX Skin

  Iterates $error array and renders styled error divs with danger border/text.

  Parameters:
  - $error: Array of error messages (format: array of objects with 'text' property)

  Usage:
  {include file="partial_inline_errors.tpl"}
*}
{if $error|default:false}
	{foreach from=$error item=errormsg}
		<div class="px-3 py-2 rounded text-sm" style="background-color: var(--color-surface-secondary); border: 1px solid var(--color-accent-danger); color: var(--color-accent-danger);">
			{$errormsg.text}
		</div>
	{/foreach}
{/if}
