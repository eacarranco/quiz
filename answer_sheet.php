<?php
include('auth.php');
include('db_connect.php');
$title = 'Inicio';
include 'header_adminlte.php';
$quiz = $conn->query("SELECT * FROM quiz_list where id =" . $_GET['id'] . " order by RAND()")->fetch_array();
?>

<style>
	li.answer {
		cursor: pointer;
		border: 2px solid #D5DBDB;
		transition: all 0.3s ease;
	}

	li.answer:hover {
		background: rgba(52, 73, 94, 0.05);
		border-color: #2C3E50;
	}

	li.answer.selected {
		background: rgba(44, 62, 80, 0.15);
		border-color: #2C3E50;
	}

	li.answer input:checked {
		background: rgba(52, 73, 94, 0.1);
		border-color: #2C3E50;
	}

	.question-item {
		border-left: 4px solid #2C3E50 !important;
	}

	.question-item.unanswered {
		border-left: 4px solid #DC3545 !important;
		background-color: #fff5f5 !important;
		box-shadow: 0 0 10px rgba(220, 53, 69, 0.3);
	}

	.score-modal-content {
		text-align: center;
		padding: 60px 40px;
	}

	.score-circle {
		width: 120px;
		height: 120px;
		border-radius: 50%;
		background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
		display: flex;
		align-items: center;
		justify-content: center;
		margin: 0 auto 40px;
		box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
	}

	.score-circle i {
		font-size: 60px;
		color: white;
	}

	.score-value {
		font-size: 56px;
		font-weight: 300;
		color: #2C3E50;
		margin: 20px 0 10px 0;
		letter-spacing: -1px;
	}

	.score-label {
		font-size: 14px;
		color: #7F8C8D;
		text-transform: uppercase;
		letter-spacing: 1px;
		margin-bottom: 30px;
	}

	.quiz-title-display {
		color: #34495E;
		font-size: 16px;
		margin-bottom: 30px;
		padding: 15px;
		background: #F8F9FA;
		border-radius: 6px;
	}
</style>

<!-- Modal de Calificación -->
<div class="modal fade modal-fullscreen-sm-down" id="scoreModal" tabindex="-1" role="dialog" aria-labelledby="scoreModalLabel"
	aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" role="document">
		<div class="modal-content border-0" style="box-shadow: 0 10px 40px rgba(0,0,0,0.1); border-radius: 12px;">
			<div class="score-modal-content">
				<div class="score-circle">
					<i class="fa fa-check"></i>
				</div>
				<h2 style="font-size: 28px; font-weight: 600; color: #2C3E50; margin-bottom: 10px;">
					Cuestionario Completado
				</h2>
				<p style="color: #95A5A6; font-size: 14px; margin-bottom: 40px;">Tu evaluación ha sido procesada</p>
				
				<div class="score-value" id="scoreValue"></div>
				<div class="score-label">Puntos Obtenidos</div>
				
				<div class="quiz-title-display">
					<strong>Cuestionario:</strong> <span id="quizTitle"></span>
				</div>
				
				<button type="button" class="btn" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 12px 40px; border-radius: 6px; border: none; font-weight: 500; width: 100%;"
					onclick="location.replace('view_answer.php?id=<?php echo $_GET['id'] ?>&mode=detail')">
					<i class="fa fa-eye"></i> Ver Respuestas Detalladas
				</button>
			</div>
		</div>
	</div>
</div>


<div class="row">
	<div class="col-12">

		<div class="alert alert-primary mb-4">
			<i class="fa fa-file-text"></i> <strong><?php echo htmlspecialchars($quiz['title']) ?></strong> |
			<span><?php echo $quiz['qpoints'] . ' Puntos por Pregunta' ?></span>
		</div>
		<div class="card">
			<div class="card-body">
				<form action="" id="answer-sheet">
					<input type="hidden" name="user_id" value="<?php echo $_SESSION['login_id'] ?>">
					<input type="hidden" name="quiz_id" value="<?php echo $quiz['id'] ?>">
					<input type="hidden" name="qpoints" value="<?php echo $quiz['qpoints'] ?>">
					<?php
					$question = $conn->query("SELECT * FROM questions where qid = '" . $quiz['id'] . "' order by order_by asc ");
					$i = 1;
					while ($row = $question->fetch_assoc()) {
						// Verificar si se deben aleatorizar las opciones
						$order_clause = $quiz['randomize_options'] == 1 ? "order by RAND()" : "order by id asc";
						$opt = $conn->query("SELECT * FROM question_opt where question_id = '" . $row['id'] . "' " . $order_clause);
						?>

						<div class="question-item mb-4 p-4" id="question-<?php echo $row['id']; ?>" data-question-id="<?php echo $row['id']; ?>"
							style="background: #F8F9FA; border-radius: 8px; border-left: 4px solid #2C3E50;">
							<h5 class="mb-3"><strong><?php echo ($i++) . '. '; ?>
									<?php echo htmlspecialchars($row['question']) ?></strong></h5>
							<input type="hidden" name="question_id[<?php echo $row['id'] ?>]"
								value="<?php echo $row['id'] ?>">

							<ul class='list-group'>
								<?php while ($orow = $opt->fetch_assoc()) { ?>
									<li class="answer list-group-item">
										<label style="margin-bottom: 0; cursor: pointer;">
											<input type="radio" name="option_id[<?php echo $row['id'] ?>]"
												value="<?php echo $orow['id'] ?>">
											<?php echo htmlspecialchars($orow['option_txt']) ?>
										</label>
									</li>
								<?php } ?>
							</ul>
						</div>

					<?php } ?>
					<button type="submit" class="btn btn-primary btn-lg btn-block">Calificar</button>
				</form>
			</div>
		</div>
	</div>
</div>

<?php include 'footer_adminlte.php'; ?>

<script>
	$(document).ready(function () {
		$('.answer').each(function () {
			$(this).click(function () {
				$(this).find('input[type="radio"]').prop('checked', true)
				$(this).siblings('li').removeClass('selected')
				$(this).addClass('selected')
				
				// Remover la clase unanswered cuando se responde
				$(this).closest('.question-item').removeClass('unanswered')
			})
		})
		$('#answer-sheet').submit(function (e) {
			e.preventDefault()
			
			// Limpiar estilos previos
			$('.question-item').removeClass('unanswered')
			
			// Obtener todas las preguntas
			var unanswered = false
			var firstUnanswered = null
			var questionItems = $('[id^="question-"]')
			
			// Verificar que todas las preguntas tengan respuesta
			questionItems.each(function () {
				var questionId = $(this).data('question-id')
				var isAnswered = $('input[name="option_id[' + questionId + ']"]:checked').length > 0
				
				if (!isAnswered) {
					$(this).addClass('unanswered')
					if (firstUnanswered === null) {
						firstUnanswered = $(this)
					}
					unanswered = true
				}
			})
			
			// Si hay preguntas sin responder, mostrar alerta y hacer focus
			if (unanswered) {
				alert('Por favor responde todas las preguntas antes de continuar.')
				if (firstUnanswered) {
					$('html, body').animate({
						scrollTop: firstUnanswered.offset().top - 100
					}, 500)
					firstUnanswered.focus()
				}
				return false
			}
			
			// Si todas están respondidas, proceder con el AJAX
			$('#answer-sheet [type="submit"]').attr('disabled', true)
			$('#answer-sheet [type="submit"]').html('<i class="fa fa-spinner fa-spin"></i> Procesando...')
			$.ajax({
				url: 'submit_answer.php',
				method: 'POST',
				data: $(this).serialize(),
				dataType: 'json',
				error: err => console.log(err),
				success: function (resp) {
					if (resp.status == 1) {
						// Mostrar modal con la calificación
						$('#scoreValue').text(resp.score);
						$('#quizTitle').text('<?php echo htmlspecialchars($quiz['title']); ?>');
						$('#scoreModal').modal('show');
						$('#answer-sheet [type="submit"]').removeAttr('disabled');
						$('#answer-sheet [type="submit"]').html('Calificar');
					} else {
						alert('Error: ' + resp.error);
						$('#answer-sheet [type="submit"]').removeAttr('disabled');
						$('#answer-sheet [type="submit"]').html('Calificar');
					}
				}
			})
		})
	})
</script>

</html>
