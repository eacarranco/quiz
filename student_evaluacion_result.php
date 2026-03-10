<?php
include('auth.php');
include('db_connect.php');

if ($_SESSION['login_user_type'] != 3) {
    header('Location: evaluacion.php');
    exit;
}

$evaluation_id = isset($_GET['evaluation_id']) ? intval($_GET['evaluation_id']) : 0;
$history_id = isset($_GET['history_id']) ? intval($_GET['history_id']) : 0;
$user_id = intval($_SESSION['login_id']);

if ($evaluation_id < 1) {
    header('Location: student_evaluacion_list.php');
    exit;
}

$conn->query("CREATE TABLE IF NOT EXISTS evaluation_history (
    id INT NOT NULL AUTO_INCREMENT,
    evaluation_id INT NOT NULL,
    user_id INT NOT NULL,
    score INT NOT NULL,
    total_score INT NOT NULL,
    date_updated DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$conn->query("CREATE TABLE IF NOT EXISTS evaluation_answers (
    id INT NOT NULL AUTO_INCREMENT,
    history_id INT NOT NULL,
    user_id INT NOT NULL,
    evaluation_id INT NOT NULL,
    question_id INT NOT NULL,
    option_id INT NOT NULL,
    is_right TINYINT(1) NOT NULL DEFAULT 0,
    date_updated DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$has_history_column = $conn->query("SHOW COLUMNS FROM evaluation_answers LIKE 'history_id'");
if ($has_history_column && $has_history_column->num_rows === 0) {
    $conn->query("ALTER TABLE evaluation_answers ADD COLUMN history_id INT NOT NULL DEFAULT 0 AFTER id");
}

$eval_qry = $conn->query("SELECT id, eval_name, eval_description, total_questions FROM evaluation_list WHERE id = {$evaluation_id} LIMIT 1");
$evaluation = $eval_qry ? $eval_qry->fetch_assoc() : null;
if (!$evaluation) {
    header('Location: student_evaluacion_list.php');
    exit;
}

$attempts = array();
$att_qry = $conn->query("SELECT id, score, total_score, date_updated FROM evaluation_history WHERE evaluation_id = {$evaluation_id} AND user_id = {$user_id} ORDER BY id DESC");
if ($att_qry && $att_qry->num_rows > 0) {
    while ($row = $att_qry->fetch_assoc()) {
        $attempts[] = $row;
    }
}

$total_attempts = count($attempts);
for ($i = 0; $i < $total_attempts; $i++) {
    $attempts[$i]['attempt_no'] = $total_attempts - $i;
}

if ($history_id < 1 && count($attempts) > 0) {
    $history_id = intval($attempts[0]['id']);
}

$selected_attempt = null;
foreach ($attempts as $at) {
    if (intval($at['id']) === $history_id) {
        $selected_attempt = $at;
        break;
    }
}

$answers = array();
if ($history_id > 0) {
    $ans_qry = $conn->query("SELECT ea.question_id, ea.option_id, ea.is_right, q.question FROM evaluation_answers ea INNER JOIN questions q ON q.id = ea.question_id WHERE ea.history_id = {$history_id} AND ea.user_id = {$user_id} ORDER BY ea.id ASC");
    if ($ans_qry && $ans_qry->num_rows > 0) {
        while ($row = $ans_qry->fetch_assoc()) {
            $answers[] = $row;
        }
    }
}

$title = 'Resultado de Evaluación';
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
                <p class="mb-1"><strong>Descripción:</strong> <?php echo htmlspecialchars($evaluation['eval_description']); ?></p>
                <p class="mb-3"><strong>Total de preguntas:</strong> <?php echo intval($evaluation['total_questions']); ?></p>

                <div class="card border mb-3">
                    <div class="card-header"><strong>Intentos realizados</strong></div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm table-striped mb-0" id="table_eval_attempts">
                                <thead>
                                    <tr>
                                        <th style="width: 10%; text-align:center;">Intento</th>
                                        <th style="width: 25%; text-align:center;">Puntaje</th>
                                        <th style="width: 35%;">Fecha</th>
                                        <th style="width: 30%; text-align:center;">Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($attempts) > 0): ?>
                                        <?php foreach ($attempts as $at): ?>
                                            <tr>
                                                <td style="text-align:center;"><strong>#<?php echo intval($at['attempt_no']); ?></strong></td>
                                                <td style="text-align:center;"><span class="badge bg-primary"><?php echo intval($at['score']); ?>/<?php echo intval($at['total_score']); ?></span></td>
                                                <td><?php echo htmlspecialchars($at['date_updated']); ?></td>
                                                <td style="text-align:center;">
                                                    <a class="btn btn-sm <?php echo intval($at['id']) === intval($history_id) ? 'btn-secondary' : 'btn-outline-primary'; ?>" href="student_evaluacion_result.php?evaluation_id=<?php echo intval($evaluation_id); ?>&history_id=<?php echo intval($at['id']); ?>">
                                                        Ver Detalle
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td class="text-center text-muted">-</td>
                                            <td class="text-center text-muted">-</td>
                                            <td class="text-muted">Aún no tiene intentos registrados.</td>
                                            <td class="text-center text-muted">-</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <?php if ($selected_attempt): ?>
                    <div class="mb-3">
                        <span class="badge bg-success">Mejor calificación: <?php
                            $best = $conn->query("SELECT score, total_score FROM evaluation_history WHERE evaluation_id = {$evaluation_id} AND user_id = {$user_id} ORDER BY score DESC, id DESC LIMIT 1")->fetch_assoc();
                            echo intval($best['score']) . '/' . intval($best['total_score']);
                        ?></span>
                        <?php
                        $selected_attempt_no = 0;
                        foreach ($attempts as $at) {
                            if (intval($at['id']) === intval($selected_attempt['id'])) {
                                $selected_attempt_no = intval($at['attempt_no']);
                                break;
                            }
                        }
                        ?>
                        <span class="badge bg-info">Detalle intento #<?php echo $selected_attempt_no; ?>: <?php echo intval($selected_attempt['score']); ?>/<?php echo intval($selected_attempt['total_score']); ?></span>
                    </div>
                <?php endif; ?>

                <?php if (count($answers) > 0): ?>
                    <?php $qnum = 1; foreach ($answers as $ans): ?>
                        <div class="card border mb-3">
                            <div class="card-body">
                                <h6 class="mb-3"><?php echo $qnum++; ?>. <?php echo nl2br(htmlspecialchars($ans['question'])); ?></h6>
                                <?php
                                $opts = $conn->query("SELECT id, option_txt, is_right FROM question_opt WHERE question_id = " . intval($ans['question_id']) . " ORDER BY id ASC");
                                if ($opts && $opts->num_rows > 0):
                                    while ($opt = $opts->fetch_assoc()):
                                        $is_selected = intval($opt['id']) === intval($ans['option_id']);
                                        $is_correct = intval($opt['is_right']) === 1;

                                        $cls = 'border';
                                        if ($is_selected && $is_correct) {
                                            $cls = 'border-success bg-success-subtle';
                                        } elseif ($is_selected && !$is_correct) {
                                            $cls = 'border-danger bg-danger-subtle';
                                        } elseif ($is_correct) {
                                            $cls = 'border-success';
                                        }
                                ?>
                                    <div class="p-2 mb-2 rounded <?php echo $cls; ?>">
                                        <?php if ($is_selected): ?>
                                            <i class="fa fa-check-circle me-1"></i>
                                        <?php else: ?>
                                            <i class="fa fa-circle me-1"></i>
                                        <?php endif; ?>
                                        <?php echo htmlspecialchars($opt['option_txt']); ?>
                                        <?php if ($is_correct): ?>
                                            <span class="badge bg-success ms-2">Correcta</span>
                                        <?php endif; ?>
                                        <?php if ($is_selected && !$is_correct): ?>
                                            <span class="badge bg-danger ms-2">Tu respuesta</span>
                                        <?php endif; ?>
                                    </div>
                                <?php
                                    endwhile;
                                endif;
                                ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php elseif (count($attempts) > 0): ?>
                    <div class="alert alert-warning">No se encontró detalle de respuestas para este intento.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function initEvalResultPage() {
    if (!window.jQuery) {
        setTimeout(initEvalResultPage, 80);
        return;
    }

    var $ = window.jQuery;
    if ($.fn.dataTable && $.fn.dataTable.isDataTable('#table_eval_attempts')) {
        $('#table_eval_attempts').DataTable().destroy();
    }

    if ($.fn.dataTable) {
        $('#table_eval_attempts').DataTable({
            paging: true,
            lengthChange: false,
            searching: false,
            ordering: true,
            info: true,
            autoWidth: false,
            responsive: false,
            order: [[0, 'desc']],
            language: {
                url: '//cdn.datatables.net/plug-ins/1.10.21/i18n/Spanish.json'
            }
        });
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initEvalResultPage);
} else {
    initEvalResultPage();
}
</script>

<?php include('footer_adminlte.php'); ?>
