<?php
include('auth.php');
include('db_connect.php');
include('student_scope.php');
$title = 'Listado de cuestionarios';
include 'header_adminlte.php';
?>

<style>
	/* Contenedor responsive para DataTable */
	.table-responsive-wrapper {
		overflow-x: auto;
		-webkit-overflow-scrolling: touch;
		width: 100%;
	}

	.table-responsive-wrapper table {
		width: 100%;
		max-width: 100%;
	}

	/* Estilos para mejor visualización en móvil */
	@media (max-width: 768px) {
		.table-responsive-wrapper {
			border: 1px solid #ddd;
			border-radius: 0.25rem;
		}

		.table-responsive-wrapper table {
			min-width: 600px;
		}

		.table-responsive-wrapper table td,
		.table-responsive-wrapper table th {
			white-space: nowrap;
			padding: 0.5rem !important;
			font-size: 0.85rem;
		}

		.table-responsive-wrapper .badge {
			font-size: 0.75rem;
		}

		.table-responsive-wrapper .btn {
			padding: 0.25rem 0.5rem;
			font-size: 0.75rem;
		}
	}
</style>

<div class="row">
	<div class="col-12">
		<div class="card">
			<div class="card-header">
				<h5 class="mb-0"><i class="fa fa-list"></i> Mis cuestionarios</h5>
			</div>
			<div class="card-body">
				<div class="table-responsive-wrapper">
					<table class="table table-bordered table-striped table-hover" id='table'>
						<colgroup>
							<col width="10%">
							<col width="30%">
							<col width="20%">
							<col width="20%">
							<col width="20%">
						</colgroup>
						<thead>
							<tr>
								<th style="width: 10%; text-align: center;">#</th>
								<th style="width: 30%;">Cuestionario</th>
								<th style="width: 20%; text-align: center;">Mejor Calificación</th>
								<th style="width: 20%; text-align: center;">Estado</th>
								<th style="width: 20%; text-align: center;">Acción</th>
							</tr>
						</thead>
						<tbody>
							<?php
							$loginId = intval($_SESSION['login_id']);
							$scope = getStudentScope($conn, $loginId, 'ql', 'e');
							$quiz_visibility_condition = $scope['quiz_visibility_condition'];

							$qry = $conn->query("SELECT DISTINCT ql.* FROM quiz_list ql WHERE ({$quiz_visibility_condition}) ORDER BY ql.title ASC");
							$i = 1;
							if ($qry && $qry->num_rows > 0) {
								while ($row = $qry->fetch_assoc()) {
									$status = $conn->query("SELECT max(score) as score, (select count(*) from questions where qid=quiz_id) as total_score from history where quiz_id = '" . $row['id'] . "' and user_id ='" . $_SESSION['login_id'] . "' group by quiz_id");
									$hist = $status->fetch_array();
									?>
									<tr>
										<td style="text-align: center; vertical-align: middle;"><?php echo $i++ ?></td>
										<td style="vertical-align: middle;"><?php echo $row['title'] ?></td>
										<td style="text-align: center; vertical-align: middle;"><span
												class="badge bg-info"><?php echo $status->num_rows > 0 ? $hist['score'] . '/' . $hist['total_score'] : 'No realizado' ?></span>
										</td>
										<td style="text-align: center; vertical-align: middle;">
											<?php
											if ($status->num_rows > 0) {
												echo '<span class="badge bg-success"><i class="fa fa-check"></i> Realizado</span>';
											} else {
												echo '<span class="badge bg-warning"><i class="fa fa-clock-o"></i> Pendiente</span>';
											}
											?>
										</td>
										<td style="text-align: center; vertical-align: middle;">
											<div class="dropdown d-inline-block">
												<button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
													<i class="fa fa-ellipsis-v"></i>
												</button>
												<ul class="dropdown-menu dropdown-menu-end">
													<li>
														<a class="dropdown-item" href="./view_answer.php?id=<?php echo $row['id'] ?>&mode=study" title="Ver respuestas">
															<i class="fa fa-eye me-2"></i> Estudiar
														</a>
													</li>
													<li>
														<a class="dropdown-item" href="./answer_sheet.php?id=<?php echo $row['id'] ?>" title="Realizar prueba">
															<i class="fa fa-pencil-alt me-2"></i> Probar Cuestionario
														</a>
													</li>													
												</ul>
											</div>
										</td>
									</tr>
									<?php
								}
							}
							?>
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</div>
</div>

<?php include('footer_adminlte.php'); ?>

<script>
	// Inicializar DataTable con configuración responsive
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
			"pageLength": 10,
			"language": {
				"url": "https://cdn.datatables.net/plug-ins/1.10.21/i18n/Spanish.json"
			},
			"drawCallback": function() {
				// Ajusta el ancho mínimo de la tabla después de redibujar
				var minWidth = $(window).width() < 768 ? '600px' : 'auto';
				$('#table').css('min-width', minWidth);
			}
		});
	}

	$(function() {
		initializeDataTable();
		
		// Re-inicializar cuando cambie el tamaño de la pantalla
		$(window).on('resize', function() {
			if ($.fn.dataTable.isDataTable('#table')) {
				var minWidth = $(window).width() < 768 ? '600px' : 'auto';
				$('#table').css('min-width', minWidth);
			}
		});
	})
</script>


