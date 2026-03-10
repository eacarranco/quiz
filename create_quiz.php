<?php
include ('auth.php');
include ('header_adminlte.php');
?>

<div class="alert alert-warning alert-dismissible fade show">
	<button type="button" class="close" data-bs-dismiss="alert">&times;</button>
	<h4>Creación de Cuestionarios Deshabilitada</h4>
	<p>Esta funcionalidad está siendo rediseñada. Vuelve a la lista de cuestionarios.</p>
	<a href="./quiz.php" class="btn btn-primary">Volver a Cuestionarios</a>
</div>

<!-- Modal Agregar Categoría -->
<div class="modal fade" id="modal_new_category" tabindex="-1" role="dialog">
	<div class="modal-dialog modal-dialog-centered">
		<div class="modal-content">
			<div class="modal-header bg-primary text-white">
				<h5 class="modal-title"><i class="fa fa-folder-plus"></i> Agregar Nueva Categoría</h5>
				<button type="button" class="close text-white" data-bs-dismiss="modal">
					<span>&times;</span>
				</button>
			</div>
			<div class="modal-body">
				<div id="msg_category"></div>
				<div class="form-group">
					<label for="cat_name"><strong>Nombre de la Categoría</strong></label>
					<input type="text" class="form-control form-control-lg" id="cat_name" placeholder="Ej: Matemáticas, Inglés, etc." required>
				</div>
				<div class="form-group">
					<label for="cat_description"><strong>Descripción (Opcional)</strong></label>
					<textarea class="form-control" id="cat_description" rows="3" placeholder="Descripción de la categoría"></textarea>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
				<button type="button" class="btn btn-primary" id="btn_save_category">
					<i class="fa fa-save"></i> Guardar Categoría
				</button>
			</div>
		</div>
	</div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
	const form = document.getElementById('create_quiz_form');
	const btnNewCategory = document.getElementById('btn_new_category');
	const btnSaveCategory = document.getElementById('btn_save_category');
	const modalNewCategory = document.getElementById('modal_new_category');
	const catNameInput = document.getElementById('cat_name');
	const catDescInput = document.getElementById('cat_description');
	const selectCategory = document.getElementById('quiz_cat_id');
	const msgCategoryDiv = document.getElementById('msg_category');

	// Abrir modal
	if (btnNewCategory) {
		btnNewCategory.addEventListener('click', function(e) {
			e.preventDefault();
			catNameInput.value = '';
			catDescInput.value = '';
			msgCategoryDiv.innerHTML = '';
			const modal = new bootstrap.Modal(modalNewCategory);
			modal.show();
		});
	}

	// Guardar categoría
	if (btnSaveCategory) {
		btnSaveCategory.addEventListener('click', function() {
			const catName = catNameInput.value.trim();
			const catDesc = catDescInput.value.trim();

			if (!catName) {
				msgCategoryDiv.innerHTML = '<div class="alert alert-warning"><i class="fa fa-warning"></i> Ingrese el nombre de la categoría</div>';
				return;
			}

			btnSaveCategory.disabled = true;
			btnSaveCategory.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Guardando...';

			fetch('./save_category.php', {
				method: 'POST',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
				body: 'cat_name=' + encodeURIComponent(catName) + '&cat_description=' + encodeURIComponent(catDesc)
			})
			.then(r => r.json())
			.then(data => {
				if (data.status === 1) {
					const opt = document.createElement('option');
					opt.value = data.id;
					opt.textContent = catName;
					selectCategory.appendChild(opt);
					selectCategory.value = data.id;

					msgCategoryDiv.innerHTML = '<div class="alert alert-success"><i class="fa fa-check"></i> Categoría creada exitosamente</div>';
					
					setTimeout(() => {
						const m = bootstrap.Modal.getInstance(modalNewCategory);
						if (m) m.hide();
					}, 1000);
				} else {
					msgCategoryDiv.innerHTML = '<div class="alert alert-danger"><i class="fa fa-times"></i> Error: ' + data.msg + '</div>';
				}
				btnSaveCategory.disabled = false;
				btnSaveCategory.innerHTML = '<i class="fa fa-save"></i> Guardar Categoría';
			})
			.catch(err => {
				msgCategoryDiv.innerHTML = '<div class="alert alert-danger"><i class="fa fa-times"></i> Error en la solicitud</div>';
				btnSaveCategory.disabled = false;
				btnSaveCategory.innerHTML = '<i class="fa fa-save"></i> Guardar Categoría';
			});
		});

		catNameInput.addEventListener('keypress', function(e) {
			if (e.key === 'Enter') {
				e.preventDefault();
				btnSaveCategory.click();
			}
		});
	}

	// Enviar formulario quiz
	if (form) {
		form.addEventListener('submit', function(e) {
			e.preventDefault();

			const title = document.getElementById('title').value.trim();
			const category = document.getElementById('quiz_cat_id').value;
			const qpoints = document.getElementById('qpoints').value;

			if (!title || !category || !qpoints) {
				alert('âš ï¸ Por favor completa todos los campos obligatorios');
				return;
			}

			const formData = new FormData(this);

			fetch('./save_quiz.php', {
				method: 'POST',
				body: formData
			})
			.then(r => r.json())
			.then(data => {
				if (data.status === 1) {
					alert('âœ… Cuestionario creado exitosamente');
					window.location.href = './quiz_view.php?id=' + data.id;
				} else {
					alert('âŒ Error: ' + data.msg);
				}
			})
			.catch(err => {
				console.error(err);
				alert('âŒ Error al procesar');
			});
		});
	}
});
</script>

<?php include('footer_adminlte.php'); ?>


