<?php
include('auth.php');
include('db_connect.php');
include 'header_adminlte.php';
$quiz = $conn->query("SELECT * FROM quiz_list where id =" . $_GET['id'])->fetch_array();

// Obtener el último intento del usuario para este quiz
$last_attempt = $conn->query("SELECT * FROM history where quiz_id =" . $_GET['id'] . " and user_id = " . $_SESSION['login_id'] . " ORDER BY date_updated DESC LIMIT 1")->fetch_array();

$mode = isset($_GET['mode']) ? $_GET['mode'] : 'detail'; // 'study' o 'detail'
?>

<style>
	/* Estilos para vista de respuestas - solo lectura */
	li.answer {
		cursor: not-allowed !important;
		pointer-events: none !important;
	}

	li.answer label {
		cursor: not-allowed !important;
		margin-bottom: 0;
	}

	li.answer input {
		cursor: not-allowed !important;
		pointer-events: none !important;
	}

	li.answer input:checked {
		background: #00c4ff3d;
	}

	/* Estilos para respuestas correctas e incorrectas en modo detalle */
	li.answer.correct {
		background-color: #d4edda !important;
		border-left: 4px solid #28a745 !important;
	}

	li.answer.incorrect {
		background-color: rgba(220, 53, 69, 0.15) !important;
		border-left: 4px solid #dc3545 !important;
	}
</style>

<div class="row">
	<div class="col-12">
		<div class="col-md-12 alert alert-primary"><?php echo $quiz['title'] ?> |
			<?php echo $quiz['qpoints'] . ' Punto(s) por pregunta' ?>
		</div>
		<br>
		<div class="card">
			<div class="card-body">
				<input type="hidden" name="user_id" value="<?php echo $_SESSION['login_id'] ?>">
				<input type="hidden" name="quiz_id" value="<?php echo $quiz['id'] ?>">
				<input type="hidden" name="qpoints" value="<?php echo $quiz['qpoints'] ?>">
				<?php
				$question = $conn->query("SELECT * FROM questions where qid = '" . $quiz['id'] . "' order by id asc ");
				$i = 1;
				while ($row = $question->fetch_assoc()) {
					$opt = $conn->query("SELECT * FROM question_opt where question_id = '" . $row['id'] . "' order by id ");
					
					// Obtener solo la respuesta más reciente del últimoIntento
					// La estrategia es: si hay múltiples intentos, obtenemos la más reciente basada en date_updated
					// filtrando por las respuestas después del penúltimo history
					$answer_query = "SELECT * FROM answers 
						WHERE quiz_id = '" . $quiz['id'] . "' 
						AND user_id = '" . $_SESSION['login_id'] . "' 
						AND question_id = '" . $row['id'] . "' 
						ORDER BY date_updated DESC 
						LIMIT 1";
					$answer = $conn->query($answer_query)->fetch_array();
					?>

					<ul class="q-items list-group mt-4 mb-4 ?>">
						<li class="q-field list-group-item">
							<strong><?php echo ($i++) . '. '; ?> 	<?php echo $row['question'] ?></strong>
							<input type="hidden" name="question_id[<?php echo $row['id'] ?>]"
								value="<?php echo $row['id'] ?>">
							<br>
							<ul class='list-group mt-4 mb-4'>
								<?php while ($orow = $opt->fetch_assoc()) { 
									// Determinar clase CSS y si mostrar checked
									$liClass = '';
									$showChecked = false;
									$userSelectedThisOption = isset($answer['option_id']) && $answer['option_id'] == $orow['id'];
									$isCorrectAnswer = $orow['is_right'] == 1;
									
									if ($mode == 'study') {
										// Modo estudio: solo mostrar respuesta correcta en verde
										if ($isCorrectAnswer) {
											$liClass = 'correct';
											$showChecked = true;
										}
									} else { // mode == 'detail'
										// Modo detalle: solo marcar checked la respuesta del estudiante
										// Verde si es correcta, rojo si es incorrecta
										if ($isCorrectAnswer) {
											// Esta es la respuesta correcta
											$liClass = 'correct';
											// Solo marcar checked si el usuario la seleccionó
											$showChecked = $userSelectedThisOption ? true : false;
										} elseif ($userSelectedThisOption) {
											// Usuario seleccionó esta opción y es INCORRECTA
											$liClass = 'incorrect';
											$showChecked = true;
										}
									}
								?>

									<li class="answer list-group-item <?php echo $liClass ?>">
										<label><input type="radio" name="option_id[<?php echo $row['id'] ?>]"
												value="<?php echo $orow['id'] ?>" 
												disabled
												<?php echo $showChecked ? "checked" : "" ?>>
											<?php echo $orow['option_txt'] ?></label>
									</li>
								<?php } ?>

							</ul>

						</li>
					</ul>

				<?php } ?>
			</div>
		</div>
	</div>
</div>

<?php include 'footer_adminlte.php'; ?>

<script>
	$(document).ready(function () {
		// Deshabilitar completamente todos los inputs
		$('input[type="radio"]').attr('disabled', true);
		
		// Prevenir click en labels
		$('.answer label').on('click', function(e) {
			e.preventDefault();
			return false;
		});
		
		// Prevenir click en los items de respuesta
		$('.answer').on('click', function(e) {
			e.preventDefault();
			return false;
		});
	})
</script>

</html>
