	<!-- Footer -->
	{if !isset($config.board)}
	<footer class="text-center py-0.5 text-sm shrink-0 text-content-secondary">
		powered by <a href="https://github.com/TransistorDD/PXMBoard" target="_blank" rel="noopener noreferrer" class="hover:underline text-link">pxmboard</a>
	</footer>
	{/if}

	<!-- Modal Dialog -->
	<dialog id="htmxModal" class="rounded-lg shadow-2xl p-0 w-full max-w-3xl backdrop:bg-black/50 fixed inset-0 m-auto h-fit bg-surface-primary text-content-primary">
		<div class="p-4">
			<div class="flex justify-between items-center mb-3">
				<h2 id="htmxModalTitle" class="text-lg font-bold"></h2>
				<button hx-on:click="document.getElementById('htmxModal').close()" class="htmx-close-btn">&times;</button>
			</div>
			<div id="htmxModalBody" class="overflow-y-auto" style="max-height:32rem;"></div>
		</div>
	</dialog>

	<!-- Confirm Dialog -->
	<dialog id="htmxConfirmDialog" class="rounded-lg shadow-2xl p-0 max-w-sm backdrop:bg-black/50 fixed inset-0 m-auto h-fit bg-surface-primary text-content-primary">
		<div class="p-4">
			<p id="htmxConfirmQuestion" class="mb-4 text-content-primary"></p>
			<div class="flex justify-end gap-2">
				<button id="htmxConfirmOk" class="htmx-btn-primary text-sm px-4 py-1">Best&auml;tigen</button>
				<button id="htmxConfirmCancel" class="htmx-btn text-sm px-4 py-1">Abbrechen</button>
			</div>
		</div>
	</dialog>

	<!-- Scripts -->
	<script src="js/htmx.min.js"></script>
	<script defer src="js/alpine.min.js"></script>
	<script src="js/message-move.js"></script>
	<script src="js/pxmboard.js"></script>
	<script src="js/editor-bundle.js"></script>
</body>
</html>
