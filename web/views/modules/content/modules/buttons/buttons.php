<div class="col-12 col-lg-6">
	<div class="d-flex flex-row-reverse">

		<?php if (!empty($_SESSION["admin"])): ?>
			<div class="p-1">
				<button type="button" class="btn btn-sm py-2 px-3 btn-info font-weight-bold rounded shadow-sm" id="startAll">
					<i class="bi bi-arrow-up-circle pe-1"></i> Start Up
				</button>
			</div>
		<?php else: ?>
			<div class="p-1">
				<button type="button" class="btn btn-sm py-2 px-3 btn-info font-weight-bold rounded shadow-sm" data-bs-toggle="modal" data-bs-target="#myLogin">
					<i class="bi bi-arrow-up-circle pe-1"></i> Start Up
				</button>
			</div>
		<?php endif; ?>

		<div class="p-1">
			<input
				type="file"
				class="d-none"
				id="customFile"
				accept="image/*,video/*,audio/*,.pdf,.zip"
				multiple
				onchange="uploadFiles(event, 'btn')">
			<label class="btn btn-sm py-2 px-3 btn-success font-weight-bold rounded shadow-sm" for="customFile" role="button" aria-label="Añadir archivos">
				<i class="bi bi-plus-lg pe-1"></i> Add Files
			</label>
		</div>

	</div>
</div>