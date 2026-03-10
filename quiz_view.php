<?php
include('auth.php');
include('db_connect.php');
$qry = $conn->query("SELECT * FROM quiz_list where id = " . $_GET['id'])->fetch_array();
$title = htmlspecialchars($qry['title']);
include('header_adminlte.php');
?>

<div class="row">
	<div class="col-md-6">
		<div class="card">
			<div class="card-header">
				<h3 class="card-title">Preguntas</h3>
				<div class="card-tools">
					<button class="btn btn-primary btn-sm" id="new_question"><i class="fa fa-plus"></i> Agregar
						Pregunta</button>
				</div>
			</div>
			<div class="card-body">
				<ul class="list-group">
					<?php
					$cont = 0;
					$qry = $conn->query("SELECT * FROM questions where qid = " . $_GET['id'] . " order by id asc");
					while ($row = $qry->fetch_array()) {
						$cont++;
						?>
						<li class="list-group-item d-flex justify-content-between align-items-start gap-2">
							<div><?php echo $cont . '. ' . $row['question'] ?></div>
							<div class="dropdown">
								<button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
									<i class="fa fa-cog"></i> Opciones
								</button>
								<ul class="dropdown-menu dropdown-menu-end">
									<li>
										<button class="dropdown-item edit_question" data-id="<?php echo $row['id'] ?>" type="button">
											<i class="fa fa-edit me-2"></i> Editar
										</button>
									</li>
									<li><hr class="dropdown-divider"></li>
									<li>
										<button class="dropdown-item text-danger remove_question" data-id="<?php echo $row['id'] ?>" type="button">
											<i class="fa fa-trash me-2"></i> Eliminar
										</button>
									</li>
								</ul>
							</div>
						</li>
						<?php
					}
					?>
				</ul>
			</div>
		</div>
	</div>
	<div class="col-md-6">
		<div class="card">
			<div class="card-header">
				<h3 class="card-title">Estudiantes</h3>
				<div class="card-tools">
					<button class="btn btn-primary btn-sm" id="new_student"><i class="fa fa-plus"></i> Agregar
						Estudiante</button>
				</div>
			</div>
			<div class="card-body">
				<ul class="list-group">
					<?php
					$qry = $conn->query("SELECT u.*,q.id as qid FROM users u left join quiz_student_list q on u.id = q.user_id where q.quiz_id = " . $_GET['id'] . " order by u.name asc");
					while ($row = $qry->fetch_array()) {
						?>
						<li class="list-group-item d-flex justify-content-between align-items-center gap-2">
							<span><?php echo ucwords($row['name']) ?></span>
							<div class="dropdown">
								<button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
									<i class="fa fa-cog"></i> Opciones
								</button>
								<ul class="dropdown-menu dropdown-menu-end">
									<li>
										<button class="dropdown-item text-danger remove_student" data-id="<?php echo $row['id'] ?>"
											data-qid='<?php echo $row['qid'] ?>' type="button">
											<i class="fa fa-trash me-2"></i> Eliminar
										</button>
									</li>
								</ul>
							</div>
						</li>
						<?php
					}
					?>
				</ul>
			</div>
		</div>
	</div>
</div>

<!-- Modal Gestionar Pregunta -->
<div class="modal fade" id="manage_question" tabindex="-1" role="dialog" aria-labelledby="manage_question_title">
	<div class="modal-dialog modal-centered" role="document">
		<div class="modal-content">
			<div class="modal-header">

				<h4 class="modal-title" id="manage_question_title"> Nueva Pregunta</h4>
				<button type="button" class="close" data-bs-dismiss="modal" aria-label="Close"><span
						aria-hidden="true">&times;</span></button>
			</div>
			<form id='question-frm'>
				<div class="modal-body">
					<div id="msg"></div>
					<div class="form-row">
						<div class="form-group col-md-12">
								<label><strong>Pregunta</strong></label>
								<input type="hidden" name="qid" value="<?php echo $_GET['id'] ?>" />
								<input type="hidden" name="id" />
							<textarea rows='2' name="question" required="required" class="form-control"
								placeholder="Ingrese la pregunta..."></textarea>
						</div>
					</div>

					<label><strong>Opciones de Respuesta:</strong></label>
					<div id="error-msg" class="alert alert-danger" style="display:none;"></div>

					<!-- Fila 1: Opciones A y B -->
					<div class="form-row">
						<div class="form-group col-md-6">
							<div class="card border-primary mb-3" style="border-radius: 6px;">
								<div class="card-body p-2">
									<div class="form-check mb-2">
										<input type="radio" name="is_right" value="0" class="form-check-input"
											id="option_a_correct" aria-label="Respuesta correcta A">
										<label class="form-check-label" for="option_a_correct">
											<strong>Opción A (Correcta)</strong>
										</label>
									</div>
									<textarea rows="2" name="question_opt[0]" required
										class="form-control form-control-sm"
										placeholder="Ingrese la opción A..."></textarea>
								</div>
							</div>
						</div>
						<div class="form-group col-md-6">
							<div class="card border-secondary mb-3" style="border-radius: 6px;">
								<div class="card-body p-2">
									<div class="form-check mb-2">
										<input type="radio" name="is_right" value="1" class="form-check-input"
											id="option_b_correct" aria-label="Respuesta correcta B">
										<label class="form-check-label" for="option_b_correct">
											<strong>Opción B (Correcta)</strong>
										</label>
									</div>
									<textarea rows="2" name="question_opt[1]" required
										class="form-control form-control-sm"
										placeholder="Ingrese la opción B..."></textarea>
								</div>
							</div>
						</div>
					</div>

					<!-- Fila 2: Opciones C y D -->
					<div class="form-row">
						<div class="form-group col-md-6">
							<div class="card border-secondary mb-3" style="border-radius: 6px;">
								<div class="card-body p-2">
									<div class="form-check mb-2">
										<input type="radio" name="is_right" value="2" class="form-check-input"
											id="option_c_correct" aria-label="Respuesta correcta C">
										<label class="form-check-label" for="option_c_correct">
											<strong>Opción C (Correcta)</strong>
										</label>
									</div>
									<textarea rows="2" name="question_opt[2]" required
										class="form-control form-control-sm"
										placeholder="Ingrese la opción C..."></textarea>
								</div>
							</div>
						</div>
						<div class="form-group col-md-6">
							<div class="card border-secondary mb-3" style="border-radius: 6px;">
								<div class="card-body p-2">
									<div class="form-check mb-2">
										<input type="radio" name="is_right" value="3" class="form-check-input"
											id="option_d_correct" aria-label="Respuesta correcta D">
										<label class="form-check-label" for="option_d_correct">
											<strong>Opción D (Correcta)</strong>
										</label>
									</div>
									<textarea rows="2" name="question_opt[3]" required
										class="form-control form-control-sm"
										placeholder="Ingrese la opción D..."></textarea>
								</div>
							</div>
						</div>
					</div>

					<div class="alert alert-info mt-3">
						<small><i class="fa fa-info-circle"></i> Selecciona el radio button de la opción que es la
							respuesta correcta.</small>
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
					<button type="submit" class="btn btn-primary" name="save"><i class="fa fa-save"></i>
						Guardar</button>
				</div>
			</form>
		</div>
	</div>
</div>

<div class="modal fade" id="manage_student" tabindex="-1" role="dialog" aria-labelledby="manage_student_title">
	<div class="modal-dialog modal-centered" role="document">
		<div class="modal-content">
			<div class="modal-header">

				<h4 class="modal-title" id="manage_student_title">Agregar nuevo estudiante/s</h4>
				<button type="button" class="close" data-bs-dismiss="modal" aria-label="Close"><span
						aria-hidden="true">&times;</span></button>
			</div>
			<form id='student-frm'>
				<div class="modal-body">
					<div id="msg"></div>
					<div class="form-group">
						<label>Student/s</label>
						<br>
						<input type="hidden" name="qid" value="<?php echo $_GET['id'] ?>" />
						<select rows='3' name="user_id[]" required="required" multiple class="form-control select2"
							style="width: 100% !important">
							<?php
							$student = $conn->query('SELECT u.*,s.level_section as ls FROM users u left join students s on u.id = s.user_id where u.user_type = 3 ');
							while ($row = $student->fetch_assoc()) {
								?>
								<option value="<?php echo $row['id'] ?>">
									<?php echo ucwords($row['name']) . ' ' . $row['ls'] ?>
								</option>
							<?php } ?>
						</select>

						</select>
					</div>
				</div>
				<div class="modal-footer">
					<button class="btn btn-primary" name="save"><i class="fa fa-save"></i> Guardar</button>
				</div>
			</form>
		</div>
	</div>
</div>

<?php include('footer_adminlte.php'); ?>

<script>
	$(function () {
		// Inicializar Select2 sin tema (usaremos CSS personalizado)
		$(".select2").select2({
			placeholder: "Seleccionar aquí",
			width: '100%',
			allowClear: true
		});

		// Fijar aria-hidden para accesibilidad WCAG
		// Estrategia: Remover aria-hidden cuando Bootstrap intenta agregarlo
		var modalElements = document.querySelectorAll('#manage_question, #manage_student');

		modalElements.forEach(function (modal) {
			// Observer para remover aria-hidden si se agrega
			var observer = new MutationObserver(function (mutations) {
				mutations.forEach(function (mutation) {
					if (mutation.type === 'attributes' && mutation.attributeName === 'aria-hidden') {
						modal.removeAttribute('aria-hidden');
					}
				});
			});

			observer.observe(modal, { attributes: true, attributeFilter: ['aria-hidden'] });
		});

		// Remover aria-hidden al mostrar el modal
		$('#manage_question, #manage_student').on('show.bs.modal', function () {
			$(this).removeAttr('aria-hidden');
		});

		// DataTable para lista de preguntas
		// (Las tablas en esta página son listas, no DataTables)
		$(document).on('click', '#new_question', function () {
			var $modal = $('#manage_question');
			var $form = $modal.find('#question-frm');
			var $errorMsg = $modal.find('#error-msg');

			$('#msg').html('')
			$modal.find('.modal-title').html('Agregar nueva pregunta')
			$form.get(0).reset()

			$form.find('input[name="is_right"]').prop('checked', false)
			$errorMsg.hide()
			$modal.modal('show')
		})
		$(document).on('click', '#new_student', function () {
			$('#msg').html('')
			$('#manage_student').modal('show')
		})
		$(document).on('click', '.edit_question', function () {
			var id = $(this).attr('data-id')
			$.ajax({
				url: './get_question.php?id=' + id,
				error: err => console.log(err),
				success: function (resp) {
					if (typeof resp != undefined) {
						resp = JSON.parse(resp)
						var $modal = $('#manage_question');
						var $form = $modal.find('#question-frm');
						var $errorMsg = $modal.find('#error-msg');

						// Cachear todas las selecciones para evitar múltiples queries del DOM
						var $idInput = $form.find('[name="id"]');
						var $questionField = $form.find('[name="question"]');
						var $radioButtons = $form.find('input[name="is_right"]');

						// Aplicar cambios en batch
						$idInput.val(resp.qdata.id)
						$questionField.val(resp.qdata.question)
						$radioButtons.prop('checked', false)

						// Procesar opciones de respuesta
						resp.odata.forEach(function (data, k) {
							$form.find('[name="question_opt[' + k + ']"]').val(data.option_txt)
							if (parseInt(data.is_right) === 1) {
								$form.find('input[name="is_right"][value="' + k + '"]').prop('checked', true)
							}
						})

						$modal.find('.modal-title').html('Editar Pregunta')
						$errorMsg.hide()
						$modal.modal('show')
					}
				}
			})
		})
		$(document).on('click', '.remove_question', function () {
			var id = $(this).attr('data-id')
			var conf = confirm('Esta seguro de eliminar esta pregunta?');
			if (conf == true) {
				$.ajax({
					url: './delete_question.php?id=' + id,
					error: err => console.log(err),
					success: function (resp) {
						if (resp == true)
							location.reload()
					}
				})
			}
		})
		$(document).on('click', '.remove_student', function () {
			var qid = $(this).attr('data-qid')
			var conf = confirm('Esta seguro de eliminar el estudiante?');
			if (conf == true) {
				$.ajax({
					url: './delete_quiz_student.php?qid=' + qid,
					error: err => console.log(err),
					success: function (resp) {
						if (resp == true)
							location.reload()
					}
				})
			}
		})
		$('#question-frm').submit(function (e) {
			e.preventDefault();
			var $form = $(this);
			var $errorMsg = $form.find('#error-msg');
			var $questionField = $form.find('[name="question"]');
			var $radioButtons = $form.find('input[name="is_right"]');
			var $submitBtn = $form.find('[name="save"]');

			$errorMsg.hide().html('');

			// Validar que la pregunta no esté vacía
			var question = $questionField.val().trim();
			if (!question) {
				$errorMsg.html('<i class="fa fa-exclamation-circle"></i> La pregunta es requerida.').show();
				return false;
			}

			// Validar que todas las opciones tengan texto
			for (var i = 0; i < 4; i++) {
				var optText = $form.find('[name="question_opt[' + i + ']"]').val().trim();
				if (!optText) {
					$errorMsg.html('<i class="fa fa-exclamation-circle"></i> Todas las opciones de respuesta son requeridas.').show();
					return false;
				}
			}

			// Validar que una respuesta correcta esté seleccionada
			var $checkedRadio = $radioButtons.filter(':checked');
			if ($checkedRadio.length === 0) {
				$errorMsg.html('<i class="fa fa-exclamation-circle"></i> Debe seleccionar una respuesta correcta.').show();
				return false;
			}

			// Convertir los radio buttons al formato esperado por save_question.php
			// El valor seleccionado es el índice (0, 1, 2 o 3) del radio button marcado
			var correctIndex = parseInt($checkedRadio.val());
			// Remover cualquier input hidden previo de is_right
			$form.find('input[type="hidden"][name^="is_right"]').remove();
			// Agregar los inputs hidden con valores 1 o 0 según corresponda
			for (var i = 0; i < 4; i++) {
				var value = (i === correctIndex) ? '1' : '0';
				$form.append('<input type="hidden" name="is_right[' + i + ']" value="' + value + '">');
			}

			$submitBtn.attr('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Guardando...');

			$.ajax({
				url: './save_question.php',
				method: 'POST',
				data: $form.serialize(),
				error: err => {
					console.log(err);
					$errorMsg.html('<i class="fa fa-exclamation-circle"></i> Ocurrió un error al guardar.').show();
					$submitBtn.removeAttr('disabled').html('<i class="fa fa-save"></i> Guardar');
				},
				success: function (resp) {
					if (resp == 1) {
						alert('Información guardada correctamente.');
						location.reload();
					} else {
						$errorMsg.html('<i class="fa fa-exclamation-circle"></i> Error al guardar la pregunta.').show();
						$submitBtn.removeAttr('disabled').html('<i class="fa fa-save"></i> Guardar');
					}
				}
			});
		})
		$('#student-frm').submit(function (e) {
			e.preventDefault();
			var $form = $(this);
			var $submitBtn = $form.find('[name="submit"]');
			var $msgDiv = $form.find('#msg');

			$submitBtn.attr('disabled', true).html('Guardando...')
			$msgDiv.html('')

			$.ajax({
				url: './quiz_student.php',
				method: 'POST',
				data: $form.serialize(),
				error: err => {
					console.log(err)
					alert('An error occured')
					$submitBtn.removeAttr('disabled').html('Guardar')
				},
				success: function (resp) {
					if (resp == 1) {
						alert('Información guardada correctamente.');
						location.reload()
					}
				}
			})
		})
	})
</script>

