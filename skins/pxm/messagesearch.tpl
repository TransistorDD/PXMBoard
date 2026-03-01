<!-- Suchformular -->
<form action="pxmboard.php" method="get"
      hx-get="pxmboard.php"
      hx-target="#threadlist-container"
      hx-swap="innerHTML">
	<input type="hidden" name="mode" value="messagesearch"/>
	<input type="hidden" name="brdid" value="{$config.board.id}"/>
	<div class="px-4 py-2 rounded-t-lg font-semibold text-sm" style="background-color: var(--color-surface-secondary); color: var(--color-content-secondary);">Nachrichtensuche</div>
	<div class="rounded-b-lg p-4 shadow" style="border: 1px solid var(--color-border-default); background-color: var(--color-surface-primary);">
{if $error}
		<div class="px-3 py-2 rounded text-sm mb-4" style="background-color: var(--color-surface-secondary); color: var(--color-accent-danger); border: 1px solid var(--color-border-default);">{$error.text}</div>
{/if}
		<!-- Zweispaltiges Grid Layout -->
		<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
			<!-- Linke Spalte -->
			<div class="space-y-3">
				<div>
					<label class="block mb-1 font-medium text-xs" style="color: var(--color-content-secondary);">Suche in Nachricht nach</label>
					<input type="text" name="smsg" maxlength="{$config.input_sizes.searchstring}" class="w-full rounded px-3 py-2 text-sm focus:outline-none focus:ring-1" style="border: 1px solid var(--color-border-default); background-color: var(--color-surface-primary); color: var(--color-content-primary); --tw-ring-color: var(--color-accent);" placeholder="Suchbegriff eingeben..."/>
				</div>
				<div>
					<label class="block mb-1 font-medium text-xs" style="color: var(--color-content-secondary);">Suche in Forum</label>
					<select name="sbrdid[]" class="w-full rounded px-3 py-2 text-sm focus:outline-none focus:ring-1" style="border: 1px solid var(--color-border-default); background-color: var(--color-surface-primary); color: var(--color-content-primary); --tw-ring-color: var(--color-accent);">
						<option value="0">Alle Foren</option>
{foreach from=$boards.board item=board}
{if $board.status != 5}
	{if $config.board.id == $board.id}
						<option value="{$board.id}" selected="selected">{$board.name}</option>
	{else}
						<option value="{$board.id}">{$board.name}</option>
	{/if}
{/if}
{/foreach}
					</select>
				</div>
			</div>

			<!-- Rechte Spalte -->
			<div class="space-y-3">
				<div>
					<label class="block mb-1 font-medium text-xs" style="color: var(--color-content-secondary);">Suche Nachrichten von</label>
					<input type="text" name="susr" maxlength="{$config.input_sizes.username}" class="w-full rounded px-3 py-2 text-sm focus:outline-none focus:ring-1" style="border: 1px solid var(--color-border-default); background-color: var(--color-surface-primary); color: var(--color-content-primary); --tw-ring-color: var(--color-accent);" placeholder="Benutzername eingeben..."/>
				</div>
				<div>
					<label class="block mb-1 font-medium text-xs" style="color: var(--color-content-secondary);">Innerhalb der letzten</label>
					<select name="days" class="w-full rounded px-3 py-2 text-sm focus:outline-none focus:ring-1" style="border: 1px solid var(--color-border-default); background-color: var(--color-surface-primary); color: var(--color-content-primary); --tw-ring-color: var(--color-accent);">
						<option value="30">30 Tage</option>
						<option value="90" selected="selected">90 Tage</option>
						<option value="180">180 Tage</option>
						<option value="365">365 Tage</option>
						<option value="0">Komplett</option>
					</select>
				</div>
			</div>
		</div>

		<!-- Toggle Switch und Suchbutton in einer Zeile -->
		<div style="display: flex; align-items: center; justify-content: space-between;">
			<label class="flex items-center gap-2 cursor-pointer">
				<span class="toggle-switch">
					<input type="checkbox" name="group_by_thread" value="1" checked="checked"/>
					<span class="toggle-switch-track"></span>
				</span>
				<span class="font-medium text-xs" style="color: var(--color-content-secondary);">Nach Threads gruppieren</span>
			</label>
			<button type="submit" class="htmx-btn-primary text-sm px-8 py-2 rounded font-medium">Suchen</button>
		</div>
	</div>
</form>

<!-- Gespeicherte Suchprofile -->
{if $searchprofiles.searchprofile}
<div class="mt-4">
	<div class="px-4 py-2 rounded-t-lg font-semibold text-sm" style="background-color: var(--color-surface-secondary); color: var(--color-content-secondary);">Das interessiert unsere Nutzer</div>
	<div class="rounded-b-lg shadow" style="border: 1px solid var(--color-border-default); background-color: var(--color-surface-primary);">
{foreach from=$searchprofiles.searchprofile item=searchprofile}
		<div class="px-4 py-2" style="border-top: 1px solid var(--color-border-light);">
			<a href="pxmboard.php?mode=messagesearch&brdid={$config.board.id}&searchid={$searchprofile.id}"
			   hx-get="pxmboard.php?mode=messagesearch&brdid={$config.board.id}&searchid={$searchprofile.id}"
			   hx-target="#threadlist-container"
			   hx-swap="innerHTML"
			   class="hover:underline" style="color: var(--color-link);">
			{if !$searchprofile.searchstring}Nachrichten{else}{$searchprofile.searchstring}{/if}
			{if $searchprofile.username} von {$searchprofile.username}{/if}
			</a>
			{if $searchprofile.days>0}<span class="text-xs" style="color: var(--color-content-secondary);"> innerhalb der letzten {$searchprofile.days} Tage</span>{/if}
			<span class="text-xs ml-2" style="color: var(--color-content-secondary);">gesucht am {$searchprofile.date}</span>
		</div>
{/foreach}
	</div>
</div>
{/if}
