<?php
include('auth.php');
include('db_connect.php');

if ($_SESSION['login_user_type'] != 3) {
    header('Location: evaluacion.php');
    exit;
}

$evaluation_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($evaluation_id < 1) {
    header('Location: student_evaluacion_list.php');
    exit;
}

$conn->query("CREATE TABLE IF NOT EXISTS evaluation_student_list (
    id INT NOT NULL AUTO_INCREMENT,
    evaluation_id INT NOT NULL,
    user_id INT NOT NULL,
    date_updated DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$student_id = intval($_SESSION['login_id']);
$permit = $conn->query("SELECT id FROM evaluation_student_list WHERE evaluation_id = {$evaluation_id} AND user_id = {$student_id} LIMIT 1");
if (!$permit || $permit->num_rows === 0) {
    header('Location: student_evaluacion_list.php');
    exit;
}

$has_randomize_column = $conn->query("SHOW COLUMNS FROM evaluation_list LIKE 'randomize_options'");
if ($has_randomize_column && $has_randomize_column->num_rows === 0) {
    $conn->query("ALTER TABLE evaluation_list ADD COLUMN randomize_options TINYINT(1) NOT NULL DEFAULT 1 AFTER total_questions");
}

$evaluation = $conn->query("SELECT id, eval_name, eval_description, total_questions, randomize_options FROM evaluation_list WHERE id = {$evaluation_id} LIMIT 1")->fetch_assoc();
if (!$evaluation) {
    header('Location: student_evaluacion_list.php');
    exit;
}

$rules_qry = $conn->query("SELECT d.*, qc.cat_name FROM evaluation_detail d LEFT JOIN quiz_category qc ON qc.id = d.quiz_cat_id WHERE d.evaluation_id = {$evaluation_id} ORDER BY d.id ASC");
$rules = array();
if ($rules_qry && $rules_qry->num_rows > 0) {
    while ($r = $rules_qry->fetch_assoc()) {
        $rules[] = $r;
    }
}

$selected_questions = array();
$selected_ids = array();
$generation_error = '';

foreach ($rules as $rule) {
    $cat_id = intval($rule['quiz_cat_id']);
    $needed = intval($rule['question_count']);
    if ($needed <= 0) {
        continue;
    }

    $exclude = '';
    if (count($selected_ids) > 0) {
        $exclude = ' AND q.id NOT IN (' . implode(',', $selected_ids) . ') ';
    }

    $questions_qry = $conn->query("SELECT q.id, q.question FROM questions q INNER JOIN quiz_list ql ON ql.id = q.qid WHERE ql.quiz_cat_id = {$cat_id} {$exclude} ORDER BY RAND() LIMIT {$needed}");

    $found = array();
    if ($questions_qry && $questions_qry->num_rows > 0) {
        while ($qq = $questions_qry->fetch_assoc()) {
            $found[] = $qq;
        }
    }

    if (count($found) < $needed) {
        $generation_error = 'No hay suficientes preguntas en la categoría ' . ($rule['cat_name'] ? $rule['cat_name'] : ('ID ' . $cat_id)) . '. Requeridas: ' . $needed . ', disponibles: ' . count($found) . '.';
        break;
    }

    foreach ($found as $f) {
        $qid = intval($f['id']);
        $selected_ids[] = $qid;
        $selected_questions[] = array(
            'id' => $qid,
            'question' => $f['question'],
            'cat_name' => $rule['cat_name']
        );
    }
}

$title = 'Rendir Evaluación';
include('header_adminlte.php');
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title"><?php echo htmlspecialchars($evaluation['eval_name']); ?></h3>
                <div class="card-tools ms-auto">
                    <a href="student_evaluacion_list.php" class="btn btn-sm btn-outline-secondary">Volver</a>
                </div>
            </div>
            <div class="card-body">
                <p class="mb-2"><strong>Descripción:</strong> <?php echo htmlspecialchars($evaluation['eval_description']); ?></p>
                <p class="mb-3"><strong>Total de preguntas:</strong> <?php echo intval($evaluation['total_questions']); ?></p>
                <p class="mb-3"><strong>Orden de respuestas:</strong> <?php echo intval($evaluation['randomize_options']) === 1 ? 'Aleatorias' : 'Orden ingresado'; ?></p>

                <?php if ($generation_error !== ''): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($generation_error); ?></div>
                <?php else: ?>
                    <div id="eval_submit_msg" class="mb-2"></div>
                    <form id="evaluation-answer-frm">
                        <input type="hidden" name="evaluation_id" value="<?php echo intval($evaluation_id); ?>">
                        <input type="hidden" name="user_id" value="<?php echo intval($student_id); ?>">

                        <?php $idx = 1; foreach ($selected_questions as $q): ?>
                            <div class="card mb-3 border">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h6 class="mb-0"><?php echo $idx++; ?>. <?php echo nl2br(htmlspecialchars($q['question'])); ?></h6>
                                        <span class="badge bg-info"><?php echo htmlspecialchars($q['cat_name'] ? $q['cat_name'] : 'Categoría'); ?></span>
                                    </div>

                                    <input type="hidden" name="question_id[]" value="<?php echo intval($q['id']); ?>">

                                    <?php
                                    $opt_order = intval($evaluation['randomize_options']) === 1 ? 'RAND()' : 'id ASC';
                                    $opt_qry = $conn->query("SELECT id, option_txt FROM question_opt WHERE question_id = " . intval($q['id']) . " ORDER BY " . $opt_order);
                                    if ($opt_qry && $opt_qry->num_rows > 0):
                                        while ($opt = $opt_qry->fetch_assoc()):
                                    ?>
                                        <div class="form-check mb-1">
                                            <input class="form-check-input" type="radio" name="option_id[<?php echo intval($q['id']); ?>]" id="opt_<?php echo intval($opt['id']); ?>" value="<?php echo intval($opt['id']); ?>">
                                            <label class="form-check-label" for="opt_<?php echo intval($opt['id']); ?>">
                                                <?php echo htmlspecialchars($opt['option_txt']); ?>
                                            </label>
                                        </div>
                                    <?php
                                        endwhile;
                                    else:
                                    ?>
                                        <div class="text-danger small">Esta pregunta no tiene opciones disponibles.</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary" id="submit_eval_btn">
                                <i class="fa fa-paper-plane"></i> Enviar Evaluación
                            </button>
                        </div>
                    </form>

                    <div class="modal fade" id="evaluationEndModal" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Evaluación finalizada</h5>
                                </div>
                                <div class="modal-body">
                                    <p class="mb-2"><strong>Evaluación:</strong> <?php echo htmlspecialchars($evaluation['eval_name']); ?></p>
                                    <p class="mb-2"><strong>Descripción:</strong> <?php echo htmlspecialchars($evaluation['eval_description']); ?></p>
                                    <p class="mb-0"><strong>Nota obtenida:</strong> <span id="end_modal_score" class="badge bg-success">-</span></p>
                                    <p class="mb-0 mt-2"><strong>Intento:</strong> <span id="end_modal_attempt" class="badge bg-info">-</span></p>
                                </div>
                                <div class="modal-footer">
                                    <a href="student_evaluacion_list.php" class="btn btn-outline-secondary">Cerrar</a>
                                    <a href="#" id="btn_view_eval_details" class="btn btn-primary">Ver detalles</a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function initTakeEvaluacionPage() {
    if (!window.jQuery) {
        setTimeout(initTakeEvaluacionPage, 80);
        return;
    }

    var $ = window.jQuery;
    var endModalEl = document.getElementById('evaluationEndModal');
    var endModal = null;
    if (endModalEl && window.bootstrap && window.bootstrap.Modal) {
        endModal = new window.bootstrap.Modal(endModalEl, {
            backdrop: 'static',
            keyboard: false
        });
    }

    $('#evaluation-answer-frm').off('submit').on('submit', function (e) {
        e.preventDefault();

        var $btn = $('#submit_eval_btn');
        $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Enviando...');
        $('#eval_submit_msg').html('');

        $.ajax({
            url: './submit_evaluacion.php',
            method: 'POST',
            data: $(this).serialize(),
            success: function (resp) {
                var json = null;
                try {
                    json = typeof resp === 'object' ? resp : JSON.parse(resp);
                } catch (e) {
                    json = null;
                }

                if (!json) {
                    $('#eval_submit_msg').html('<div class="alert alert-danger py-2 mb-0">Respuesta inválida del servidor.</div>');
                    $btn.prop('disabled', false).html('<i class="fa fa-paper-plane"></i> Enviar Evaluación');
                    return;
                }

                if (json.status == 1) {
                    $('#eval_submit_msg').html('<div class="alert alert-success py-2 mb-0">Evaluación enviada correctamente.</div>');

                    var target = 'student_evaluacion_result.php?evaluation_id=<?php echo intval($evaluation_id); ?>';
                    if (json.history_id) {
                        target += '&history_id=' + json.history_id;
                    }

                    $('#end_modal_score').text(json.score || '-');
                    $('#end_modal_attempt').text(json.attempt_no ? ('#' + json.attempt_no) : '-');
                    $('#btn_view_eval_details').attr('href', target);

                    if (endModal) {
                        endModal.show();
                    } else {
                        window.location.href = target;
                    }
                } else {
                    $('#eval_submit_msg').html('<div class="alert alert-danger py-2 mb-0">' + (json.msg || 'No se pudo enviar la evaluación.') + '</div>');
                    $btn.prop('disabled', false).html('<i class="fa fa-paper-plane"></i> Enviar Evaluación');
                }
            },
            error: function () {
                $('#eval_submit_msg').html('<div class="alert alert-danger py-2 mb-0">Error al enviar la evaluación.</div>');
                $btn.prop('disabled', false).html('<i class="fa fa-paper-plane"></i> Enviar Evaluación');
            }
        });
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initTakeEvaluacionPage);
} else {
    initTakeEvaluacionPage();
}
</script>

<?php include('footer_adminlte.php'); ?>
