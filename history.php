<?php
    include ('auth.php');
    include ('db_connect.php');
    $title = 'Historial de Cuestionarios';
    include ('header_adminlte.php');
?>

<div class="row">
	<div class="col-12">
		<div class="card">
			<div class="card-header">
				<h3 class="card-title">Historial de Cuestionarios</h3>
				<div class="card-tools">
					<div class="form-group">
						<select class="form-control select2" onchange="location.replace('history.php?quiz_id='+this.value)">
				<option value="all" <?php echo isset($_GET['quiz_id']) && $_GET['quiz_id'] == 'all' ? 'selected' : '' ?>>Todos</option>
				<?php 
				$quiz_where =''; 
				if($_SESSION['login_user_type'] == 2){
					$quiz_where = ' where user_id = '.$_SESSION['login_id'].' '; 
				}
				$quiz = $conn->query("SELECT * FROM quiz_list ".$quiz_where." order by title asc");
				while($row = $quiz->fetch_assoc()){
				?>
				<option value="<?php echo $row['id'] ?>" <?php echo isset($_GET['quiz_id']) && $_GET['quiz_id'] == $row['id']  ? 'selected' : '' ?>><?php echo $row['title'] ?></option>
			<?php } ?>
			</select>
					</div>
				</div>
			</div>
			<!-- /.card-header -->
			<div class="card-body">
				<table class="table table-bordered table-striped" id='table'>
					<colgroup>
						<col width="10%">
						<col width="30%">
						<col width="20%">
						<col width="20%">						
					</colgroup>
					<thead>
						<tr>
							<th>#</th>
							<th>Estudiante</th>
							<th>Cuestionario</th>
							<th>Calificación</th>
						</tr>
					</thead>
					<tbody>
					<?php
					$where = '';
					if($_SESSION['login_user_type'] == 2){
						$where = ' where q.user_id = '.$_SESSION['login_id'].' ';
					}
					if(isset($_GET['quiz_id']) && $_GET['quiz_id'] != 'all'){
						if(empty($where)){
						$where = ' where q.id = '.$_GET['quiz_id'].' ';

						}else{
						$where = ' and q.id = '.$_GET['quiz_id'].' ';

						}
					}
					$qry = $conn->query("SELECT h.*,u.name as student,q.title from history h inner join users u on h.user_id = u.id inner join quiz_list q on h.quiz_id = q.id ".$where." order by u.name asc ");
					$i = 1;
					if($qry->num_rows > 0){
						while($row= $qry->fetch_assoc()){
							
						?>
					<tr>
					<td style="text-align: center; vertical-align: middle;"><?php echo $i++ ?></td>
					<td style="vertical-align: middle;"><?php echo ucwords($row['student']) ?></td>
					<td style="vertical-align: middle;"><?php echo $row['title'] ?></td>
					<td style="text-align: center; vertical-align: middle;"><?php echo $row['score'].'/'.$row['total_score']  ?></td>
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
		initializeDataTable();
		$('.select2').select2({})
	})
</script>

