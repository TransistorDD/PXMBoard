{function name="threadnode"}
<li><div class="htmx-thread-msg-row" data-msgid="{$msg.id}">
	{if $msg.user.imgfile}
		<img class="h-5 w-5 rounded-full object-cover shrink-0" src="{$config.profile_img_dir}{$msg.user.imgfile}" alt="{$msg.user.username}">
	{/if}
	<span class="{if $config.logedin == 1 && $msg.new == 1}font-semibold{/if}">
		<a href="pxmboard.php?mode=message&brdid={$config.board.id}&msgid={$msg.id}"
		   hx-get="pxmboard.php?mode=message&brdid={$config.board.id}&msgid={$msg.id}"
		   hx-target="#message-container"
		   hx-swap="innerHTML"
		   onclick="selectMessage({$msg.id})"
		   name="p{$msg.id}"
		   data-msgid="{$msg.id}"
		   class="hover:underline text-link">{$msg.subject}</a>
	</span>
	<span class="htmx-msg-meta">
		von <span class="{if $msg.user.highlight == 1}font-medium text-accent-deep{/if}">{$msg.user.username}</span>
		am {$msg.date} Uhr{if $config.logedin == 1 && $msg.new == 1} <span class="text-xs font-semibold text-accent-danger">(neu)</span>{/if}
	</span>
</div>
{if $msg.msg|isset}
	<ul class="htmx-thread-tree">
{foreach from=$msg.msg item=msgpart}{threadnode msg=$msgpart}{/foreach}
	</ul>
{/if}
</li>
{/function}
{if $thread and $thread.msg and $thread.msg[0]}
{assign var="root" value=$thread.msg[0]}
<div data-thrdid="{$thread.id}">
	<!-- Thread-Kopf: Wurzelnachricht als Header-Zeile -->
	<div class="htmx-thread-root-header" data-msgid="{$root.id}">
		{if $root.user.imgfile}
			<img class="h-4 w-4 rounded-full object-cover shrink-0" src="{$config.profile_img_dir}{$root.user.imgfile}" alt="{$root.user.username}">
		{/if}
		<span class="font-semibold">
			<a href="pxmboard.php?mode=message&brdid={$config.board.id}&msgid={$root.id}"
			   hx-get="pxmboard.php?mode=message&brdid={$config.board.id}&msgid={$root.id}"
			   hx-target="#message-container"
			   hx-swap="innerHTML"
			   onclick="selectMessage({$root.id})"
			   data-msgid="{$root.id}"
			   class="hover:underline">{$root.subject}</a>
		</span>
		<span class="htmx-msg-meta">
			von <span class="{if $root.user.highlight == 1}font-medium text-accent-deep{/if}">{$root.user.username}</span>
			am {$root.date} Uhr{if $config.logedin == 1 && $root.new == 1} <span class="text-xs font-semibold text-accent-danger">(neu)</span>{/if}
		</span>
		{if $config.admin == 1 or $config.moderator == 1}
		<div class="ml-auto">
			<select onchange="adminaction(this.value,{$config.board.id},{$thread.id})" class="text-xs rounded px-2 py-0 bg-surface-secondary text-content-primary border border-border-default">
				<option value="">Aktion...</option>
				<option value="threadstatus">{if $thread.active == 1}Schliessen{else}&Ouml;ffnen{/if}</option>
				<option value="fixthread">{if $thread.fixed == 1}L&ouml;sen{else}Fixieren{/if}</option>
				<option value="movethread">Verschieben</option>
				<option value="deletethread">L&ouml;schen</option>
			</select>
		</div>
		{/if}
	</div>

	<!-- Antworten-Baum: nur Kinder der Wurzelnachricht -->
	<div class="p-2 overflow-auto">
		{if $root.msg|isset}
		<ul class="htmx-thread-tree">
{foreach from=$root.msg item=msgpart}{threadnode msg=$msgpart}{/foreach}
		</ul>
		{/if}
	</div>
</div>
{/if}
