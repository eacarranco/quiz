<?php
    include ('auth.php');
    include ('db_connect.php');

	$conn->query("CREATE TABLE IF NOT EXISTS levels (
		id INT NOT NULL AUTO_INCREMENT,
		level_name VARCHAR(100) NOT NULL,
		state TINYINT(1) NOT NULL DEFAULT 1,
		date_updated DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		UNIQUE KEY uq_level_name (level_name)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

	$has_level_id = $conn->query("SHOW COLUMNS FROM students LIKE 'level_id'");
	if ($has_level_id && $has_level_id->num_rows === 0) {
		$conn->query("ALTER TABLE students ADD COLUMN level_id INT NULL AFTER user_id");
	}

	$default_chk = $conn->query("SELECT id FROM levels WHERE level_name = 'Default' LIMIT 1");
	if (!$default_chk || $default_chk->num_rows === 0) {
		$conn->query("UPDATE levels SET level_name = 'Default' WHERE level_name = '1A' LIMIT 1");
	}

	$conn->query("INSERT IGNORE INTO levels (level_name, state) VALUES ('Default', 1)");

	$default_row = $conn->query("SELECT id FROM levels WHERE level_name = 'Default' LIMIT 1");
	$default_id = ($default_row && $default_row->num_rows > 0) ? intval($default_row->fetch_assoc()['id']) : 0;
	if ($default_id > 0) {
		$conn->query("UPDATE students SET level_id = {$default_id}, level_section = 'Default' WHERE TRIM(level_section) = '1A'");
		$conn->query("UPDATE students SET level_id = {$default_id}, level_section = 'Default' WHERE (level_id IS NULL OR level_id = 0)");
	}

	$levels = array();
	$levels_qry = $conn->query("SELECT id, level_name FROM levels WHERE state = 1 ORDER BY level_name ASC");
	if ($levels_qry && $levels_qry->num_rows > 0) {
		while ($lvl = $levels_qry->fetch_assoc()) {
			$levels[] = $lvl;
		}
	}

    $title = 'Gestión de Estudiantes';
    include ('header_adminlte.php');
?>

<div class="row">
	<div class="col-12">
		<div class="card">
			<div class="card-header">
				<h3 class="card-title">Estudiantes Registrados</h3>
				<div class="card-tools">
					<button class="btn btn-primary btn-sm" id="new_student">
						<i class="fa fa-plus"></i> Agregar Estudiante
					</button>
				</div>
			</div>
			<div class="card-body">
				<table class="table table-bordered table-striped" id='table'>
					<thead>
						<tr>
							<th style="width: 5%; text-align: center;">#</th>
							<th style="width: 40%;"><i class="fa fa-user"></i> Nombre</th>
							<th style="width: 25%;"><i class="fa fa-graduation-cap"></i> Nivel</th>
							<th style="width: 30%; text-align: center;"><i class="fa fa-cogs"></i> Opciones</th>
						</tr>
					</thead>
					<tbody>
					<?php
					$qry = $conn->query("SELECT s.*,u.name,l.level_name FROM students s left join users u on s.user_id = u.id left join levels l on l.id = s.level_id order by u.name asc ");
					$i = 1;
					if($qry->num_rows > 0){
						while($row= $qry->fetch_assoc()){
						?>
					<tr>
					<td style="text-align: center; vertical-align: middle;"><strong><?php echo $i++ ?></strong></td>
					<td style="vertical-align: middle;"><?php echo htmlspecialchars($row['name']) ?></td>
					<td style="vertical-align: middle;"><?php echo htmlspecialchars(!empty($row['level_name']) ? $row['level_name'] : $row['level_section']) ?></td>
					<td style="text-align: center; vertical-align: middle;">
						<div class="dropdown d-inline-block text-start">
							<button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
								<i class="fa fa-cog"></i> Opciones
							</button>
							<ul class="dropdown-menu dropdown-menu-end">
								<li>
									<button type="button" class="dropdown-item edit_student" data-id="<?php echo $row['id']?>">
										<i class="fa fa-edit me-2"></i> Editar
									</button>
								</li>
								<li><hr class="dropdown-divider"></li>
								<li>
									<button type="button" class="dropdown-item text-danger remove_student" data-id="<?php echo $row['id']?>">
										<i class="fa fa-trash me-2"></i> Eliminar
									</button>
								</li>
							</ul>
						</div>
					</td>
					</tr>
					<?php
					}
					} else {
						//echo '<tr><td colspan="4" style="text-align: center; padding: 20px;" class="text-muted">No hay estudiantes registrados</td></tr>';
					}
					?>
					</tbody>
				</table>
			</div>
		</div>
	</div>
</div>

<!-- Modal Gestionar Estudiante -->
<div class="modal fade" id="manage_student" tabindex="-1" role="dialog" aria-hidden="true">
		<div class="modal-dialog modal-dialog-centered" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title" id="myModallabel">Agregar Estudiante</h5>
					<button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<form id='student-frm'>
					<div class="modal-body">
						<div id="msg"></div>
						<div class="form-group">
							<label>Nombre</label>
							<input type="hidden" name="id" />
							<input type="hidden" name="uid" />
							<input type="hidden" name="user_type" value="3" />
						<input type="text" name="name" autocomplete="name" required class="form-control" placeholder="Ingrese nombre completo" />
						</div>
						<div class="form-group">
							<label>Nivel/Grado</label>
							<select name="level_id" required class="form-control">
								<option value="">Seleccione nivel</option>
								<?php foreach($levels as $lvl): ?>
								<option value="<?php echo intval($lvl['id']) ?>"><?php echo htmlspecialchars($lvl['level_name']) ?></option>
								<?php endforeach; ?>
							</select>
							<?php if(count($levels) === 0): ?>
							<small class="text-danger d-block mt-1">No hay niveles disponibles. Registra niveles para crear estudiantes.</small>
							<?php endif; ?>
						</div>
						<div class="form-group">
							<label>Usuario</label>
						<input type="text" name="username" autocomplete="username" required class="form-control" placeholder="Nombre de usuario" />
						</div>
						<div class="form-group">
							<label>Contraseña</label>
						<input type="password" name="password" autocomplete="current-password" required class="form-control" placeholder="Ingrese contraseña" />
						</div>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
						<button type="submit" class="btn btn-primary" name="save">
							<i class="fa fa-save"></i> Guardar
						</button>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>

	</div>
</div>

<?php include('footer_adminlte.php'); ?>

<script>
	// Inicializar DataTable
	function initializeDataTable() {
		if ($.fn.dataTable.isDataTable('#table')) {
			$('#table').DataTable().destroy();
		}
		$('#table').DataTable({
			"paging": true,
			"lengthChange": true,
			"searching": true,
			"ordering": true,
			"info": true,
			"autoWidth": false,
			"responsive": false,
			"order": [[1, "asc"]],
			"language": {
				"url": "https://cdn.datatables.net/plug-ins/1.10.21/i18n/Spanish.json"
			}
		});
	}

	$(function(){
		// Fijar aria-hidden para accesibilidad WCAG
		// Estrategia: Remover aria-hidden cuando Bootstrap intenta agregarlo
		var modalElements = document.querySelectorAll('#manage_student');
		
		modalElements.forEach(function(modal) {
			// Observer para remover aria-hidden si se agrega
			var observer = new MutationObserver(function(mutations) {
				mutations.forEach(function(mutation) {
					if (mutation.type === 'attributes' && mutation.attributeName === 'aria-hidden') {
						modal.removeAttribute('aria-hidden');
					}
				});
			});
			
			observer.observe(modal, { attributes: true, attributeFilter: ['aria-hidden'] });
		});
		
		// Remover aria-hidden al mostrar el modal
		$('#manage_student').on('show.bs.modal', function () {
			$(this).removeAttr('aria-hidden');
		});

		initializeDataTable();
		$(document).on('click', '#new_student', function(){
			$('#msg').html('')
			$('#manage_student .modal-title').html('Agregar Nuevo Estudiante')
			$('#manage_student #student-frm').get(0).reset()
			$('#manage_student').modal('show')
		})
		$(document).on('click', '.edit_student', function(){
			var id = $(this).attr('data-id')
			$.ajax({
				url:'./get_student.php?id='+id,
				error:err=>console.log(err),
				success:function(resp){
					if(typeof resp != undefined){
						resp = JSON.parse(resp)
						$('[name="id"]').val(resp.id)
						$('[name="uid"]').val(resp.uid)
						$('[name="name"]').val(resp.name)
						$('[name="level_id"]').val(resp.level_id)
						$('[name="username"]').val(resp.username)
						$('[name="password"]').val(resp.password)
						$('#manage_student .modal-title').html('Editar Estudiante')
						$('#manage_student').modal('show')
					}
				}
			})
		})
		$(document).on('click', '.remove_student', function(){
			var id = $(this).attr('data-id')
			if(confirm('¿Está seguro que desea eliminar este estudiante?')){
				$.ajax({
					url:'./delete_student.php?id='+id,
					error:err=>console.log(err),
					success:function(resp){
						if(resp == true)
							location.reload()
					}
				})
			}
		})
		$('#student-frm').submit(function(e){
			e.preventDefault();
			$('#student-frm [name="save"]').attr('disabled',true)
			$('#student-frm [name="save"]').html('<i class="fa fa-spinner fa-spin"></i> Guardando...')
			$('#msg').html('')

			$.ajax({
				url:'./save_student.php',
				method:'POST',
				data:$(this).serialize(),
				error:err=>{
					console.log(err)
					alert('Ocurrió un error')
					$('#student-frm [name="save"]').removeAttr('disabled')
					$('#student-frm [name="save"]').html('<i class="fa fa-save"></i> Guardar')
				},
				success:function(resp){
					if(typeof resp != undefined){
						resp = JSON.parse(resp)
						if(resp.status == 1){
							alert('Estudiante guardado correctamente');
							location.reload()
						}else{
							$('#msg').html('<div class="alert alert-danger">'+resp.msg+'</div>')
							$('#student-frm [name="save"]').removeAttr('disabled')
							$('#student-frm [name="save"]').html('<i class="fa fa-save"></i> Guardar')
						}
					}
				}
			})
		})
	})
</script>


