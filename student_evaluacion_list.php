<?php
include('auth.php');
include('db_connect.php');
include('student_scope.php');

if ($_SESSION['login_user_type'] != 3) {
    header('Location: evaluacion.php');
    exit;
}

$title = 'Mis Evaluaciones';

$conn->query("CREATE TABLE IF NOT EXISTS evaluation_list (
    id INT NOT NULL AUTO_INCREMENT,
    eval_name VARCHAR(180) NOT NULL,
    eval_description VARCHAR(500) DEFAULT NULL,
    total_questions INT NOT NULL,
    created_by INT NOT NULL,
    date_updated DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$conn->query("CREATE TABLE IF NOT EXISTS evaluation_detail (
    id INT NOT NULL AUTO_INCREMENT,
    evaluation_id INT NOT NULL,
    quiz_cat_id INT NOT NULL,
    value_type VARCHAR(20) NOT NULL,
    value_num DECIMAL(10,2) NOT NULL,
    question_count INT NOT NULL,
    date_updated DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$conn->query("CREATE TABLE IF NOT EXISTS evaluation_student_list (
    id INT NOT NULL AUTO_INCREMENT,
    evaluation_id INT NOT NULL,
    user_id INT NOT NULL,
    date_updated DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$conn->query("CREATE TABLE IF NOT EXISTS evaluation_history (
    id INT NOT NULL AUTO_INCREMENT,
    evaluation_id INT NOT NULL,
    user_id INT NOT NULL,
    score INT NOT NULL,
    total_score INT NOT NULL,
    date_updated DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$student_id = intval($_SESSION['login_id']);
$scope = getStudentScope($conn, $student_id, 'q', 'e');
$eval_visibility_condition = $scope['eval_visibility_condition'];

$eval_qry = $conn->query("SELECT DISTINCT e.*, (SELECT d.value_type FROM evaluation_detail d WHERE d.evaluation_id = e.id ORDER BY d.id ASC LIMIT 1) AS eval_type FROM evaluation_list e WHERE ({$eval_visibility_condition}) ORDER BY e.id DESC");

include('header_adminlte.php');
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Evaluaciones Asignadas</h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-striped align-middle" id="table_student_evaluaciones">
                        <thead>
                            <tr>
                                <th style="width: 6%; text-align: center;">#</th>
                                <th style="width: 24%;">Nombre</th>
                                <th style="width: 30%;">Descripción</th>
                                <th style="width: 12%; text-align: center;">Preguntas</th>                                
                                <th style="width: 10%; text-align: center;">Mejor Intento</th>                                
                                <th style="width: 10%; text-align: center;">Opciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $i = 1;
                            if ($eval_qry && $eval_qry->num_rows > 0) {
                                while ($row = $eval_qry->fetch_assoc()) {
                                    $rules_count = $conn->query("SELECT COUNT(id) AS total FROM evaluation_detail WHERE evaluation_id = " . intval($row['id']))->fetch_assoc();
                                    $n_rules = isset($rules_count['total']) ? intval($rules_count['total']) : 0;
                                    $hist = $conn->query("SELECT score, total_score FROM evaluation_history WHERE evaluation_id = " . intval($row['id']) . " AND user_id = " . $student_id . " ORDER BY id DESC LIMIT 1");
                                    $has_attempt = $hist && $hist->num_rows > 0;
                                    $attempt_data = $has_attempt ? $hist->fetch_assoc() : null;
                                    $best = $conn->query("SELECT score, total_score FROM evaluation_history WHERE evaluation_id = " . intval($row['id']) . " AND user_id = " . $student_id . " ORDER BY score DESC, id DESC LIMIT 1");
                                    $best_data = ($best && $best->num_rows > 0) ? $best->fetch_assoc() : null;
                                    ?>
                                    <tr>
                                        <td style="text-align: center;"><strong><?php echo $i++; ?></strong></td>
                                        <td><?php echo htmlspecialchars($row['eval_name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['eval_description']); ?></td>
                                        <td style="text-align: center;"><span class="badge bg-primary"><?php echo intval($row['total_questions']); ?></span></td>                                                                                
                                        <td style="text-align: center;">
                                            <?php if ($best_data): ?>
                                                <span class="badge bg-success"><?php echo intval($best_data['score']); ?>/<?php echo intval($best_data['total_score']); ?></span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">-</span>
                                            <?php endif; ?>
                                        </td>                                        
                                        <td style="text-align: center;">
                                            <div class="dropdown d-inline-block text-start">
                                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fa fa-cog"></i> Opciones
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a href="take_evaluacion.php?id=<?php echo intval($row['id']); ?>" class="dropdown-item">
                                                            <i class="fa fa-play me-2"></i> Rendir
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a href="student_evaluacion_result.php?evaluation_id=<?php echo intval($row['id']); ?>" class="dropdown-item">
                                                            <i class="fa fa-chart-bar me-2"></i> Resultados
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

<script>
function initStudentEvaluacionesPage() {
    if (!window.jQuery) {
        setTimeout(initStudentEvaluacionesPage, 80);
        return;
    }

    var $ = window.jQuery;

    if ($.fn.dataTable && $.fn.dataTable.isDataTable('#table_student_evaluaciones')) {
        $('#table_student_evaluaciones').DataTable().destroy();
    }

    if ($.fn.dataTable) {
        $('#table_student_evaluaciones').DataTable({
            paging: true,
            lengthChange: true,
            searching: true,
            ordering: true,
            info: true,
            autoWidth: false,
            responsive: false,
            order: [[0, 'desc']],
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.10.21/i18n/Spanish.json'
            }
        });
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initStudentEvaluacionesPage);
} else {
    initStudentEvaluacionesPage();
}
</script>

<?php include('footer_adminlte.php'); ?>

