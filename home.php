<?php
include 'auth.php';
include 'db_connect.php';
$title = 'Inicio';
include 'header_adminlte.php';

$user_id = intval($_SESSION['login_id']);
$user_type = intval($_SESSION['login_user_type']);

$role_label = $user_type === 3 ? 'Estudiante' : ($user_type === 1 ? 'Administrador' : 'Docente');

$today = new DateTime();
$days_labels = array();
$days_keys = array();
for ($i = 6; $i >= 0; $i--) {
    $d = clone $today;
    $d->modify('-' . $i . ' days');
    $days_labels[] = $d->format('d M');
    $days_keys[] = $d->format('Y-m-d');
}

if ($user_type !== 3) {
    $owner_quiz_where = $user_type === 2 ? ' WHERE q.user_id = ' . $user_id . ' ' : '';
    $owner_eval_where = $user_type === 2 ? ' WHERE e.created_by = ' . $user_id . ' ' : '';

    $quiz_total = intval(($conn->query("SELECT COUNT(q.id) AS total FROM quiz_list q {$owner_quiz_where}")->fetch_assoc()['total']) ?? 0);
    $question_total = intval(($conn->query("SELECT COUNT(qq.id) AS total FROM questions qq INNER JOIN quiz_list q ON q.id = qq.qid " . ($user_type === 2 ? " WHERE q.user_id = {$user_id} " : ""))->fetch_assoc()['total']) ?? 0);
    $evaluation_total = intval(($conn->query("SELECT COUNT(e.id) AS total FROM evaluation_list e {$owner_eval_where}")->fetch_assoc()['total']) ?? 0);

    if ($user_type === 1) {
        $student_total = intval(($conn->query("SELECT COUNT(id) AS total FROM users WHERE user_type = 3 AND status = 1")->fetch_assoc()['total']) ?? 0);
    } else {
        $student_total = intval(($conn->query("SELECT COUNT(DISTINCT qsl.user_id) AS total FROM quiz_student_list qsl INNER JOIN quiz_list q ON q.id = qsl.quiz_id WHERE q.user_id = {$user_id}")->fetch_assoc()['total']) ?? 0);
    }

    $quiz_attempts_total = intval(($conn->query("SELECT COUNT(h.id) AS total FROM history h INNER JOIN quiz_list q ON q.id = h.quiz_id " . ($user_type === 2 ? " WHERE q.user_id = {$user_id} " : ""))->fetch_assoc()['total']) ?? 0);
    $evaluation_attempts_total = intval(($conn->query("SELECT COUNT(eh.id) AS total FROM evaluation_history eh INNER JOIN evaluation_list e ON e.id = eh.evaluation_id " . ($user_type === 2 ? " WHERE e.created_by = {$user_id} " : ""))->fetch_assoc()['total']) ?? 0);

    $avg_eval_pct_qry = $conn->query("SELECT AVG((eh.score / NULLIF(eh.total_score,0)) * 100) AS avg_pct FROM evaluation_history eh INNER JOIN evaluation_list e ON e.id = eh.evaluation_id " . ($user_type === 2 ? " WHERE e.created_by = {$user_id} " : ""));
    $avg_eval_pct = 0;
    if ($avg_eval_pct_qry && $avg_eval_pct_qry->num_rows > 0) {
        $avg_eval_pct = floatval($avg_eval_pct_qry->fetch_assoc()['avg_pct']);
    }

    $cat_qry = $conn->query(
        "SELECT qc.cat_name, COUNT(qq.id) AS question_total
         FROM quiz_category qc
         LEFT JOIN quiz_list q ON q.quiz_cat_id = qc.id" . ($user_type === 2 ? " AND q.user_id = {$user_id}" : "") . "
         LEFT JOIN questions qq ON qq.qid = q.id
         WHERE qc.state = 1
         GROUP BY qc.id, qc.cat_name
         ORDER BY question_total DESC, qc.cat_name ASC
         LIMIT 8"
    );

    $cat_labels = array();
    $cat_values = array();
    if ($cat_qry && $cat_qry->num_rows > 0) {
        while ($row = $cat_qry->fetch_assoc()) {
            $cat_labels[] = $row['cat_name'];
            $cat_values[] = intval($row['question_total']);
        }
    }

    $quiz_map = array();
    $quiz_daily_qry = $conn->query(
        "SELECT DATE(h.date_updated) AS day_key, COUNT(h.id) AS total
         FROM history h
         INNER JOIN quiz_list q ON q.id = h.quiz_id
            WHERE 1=1
            " . ($user_type === 2 ? " AND q.user_id = {$user_id} " : "") . "
            AND DATE(h.date_updated) >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
         GROUP BY DATE(h.date_updated)"
    );
    if ($quiz_daily_qry && $quiz_daily_qry->num_rows > 0) {
        while ($row = $quiz_daily_qry->fetch_assoc()) {
            $quiz_map[$row['day_key']] = intval($row['total']);
        }
    }

    $eval_map = array();
    $eval_daily_qry = $conn->query(
        "SELECT DATE(eh.date_updated) AS day_key, COUNT(eh.id) AS total
         FROM evaluation_history eh
         INNER JOIN evaluation_list e ON e.id = eh.evaluation_id
            WHERE 1=1
            " . ($user_type === 2 ? " AND e.created_by = {$user_id} " : "") . "
            AND DATE(eh.date_updated) >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
         GROUP BY DATE(eh.date_updated)"
    );
    if ($eval_daily_qry && $eval_daily_qry->num_rows > 0) {
        while ($row = $eval_daily_qry->fetch_assoc()) {
            $eval_map[$row['day_key']] = intval($row['total']);
        }
    }

    $quiz_daily_values = array();
    $eval_daily_values = array();
    foreach ($days_keys as $day_key) {
        $quiz_daily_values[] = isset($quiz_map[$day_key]) ? intval($quiz_map[$day_key]) : 0;
        $eval_daily_values[] = isset($eval_map[$day_key]) ? intval($eval_map[$day_key]) : 0;
    }

    $student_perf_labels = array();
    $student_perf_values = array();
    $student_perf_qry = $conn->query(
        "SELECT u.name, ROUND(AVG((eh.score / NULLIF(eh.total_score,0)) * 100),2) AS avg_pct
         FROM evaluation_history eh
         INNER JOIN users u ON u.id = eh.user_id
         INNER JOIN evaluation_list e ON e.id = eh.evaluation_id
         " . ($user_type === 2 ? "WHERE e.created_by = {$user_id} " : "") . "
         GROUP BY eh.user_id, u.name
         ORDER BY avg_pct DESC
         LIMIT 6"
    );
    if ($student_perf_qry && $student_perf_qry->num_rows > 0) {
        while ($row = $student_perf_qry->fetch_assoc()) {
            $student_perf_labels[] = $row['name'];
            $student_perf_values[] = floatval($row['avg_pct']);
        }
    }
} else {
    $assigned_quizzes = intval(($conn->query("SELECT COUNT(id) AS total FROM quiz_student_list WHERE user_id = {$user_id}")->fetch_assoc()['total']) ?? 0);
    $quiz_attempts_total = intval(($conn->query("SELECT COUNT(id) AS total FROM history WHERE user_id = {$user_id}")->fetch_assoc()['total']) ?? 0);
    $assigned_evaluations = intval(($conn->query("SELECT COUNT(id) AS total FROM evaluation_student_list WHERE user_id = {$user_id}")->fetch_assoc()['total']) ?? 0);
    $evaluation_attempts_total = intval(($conn->query("SELECT COUNT(id) AS total FROM evaluation_history WHERE user_id = {$user_id}")->fetch_assoc()['total']) ?? 0);

    $avg_eval_pct_qry = $conn->query("SELECT AVG((score / NULLIF(total_score,0)) * 100) AS avg_pct FROM evaluation_history WHERE user_id = {$user_id}");
    $avg_eval_pct = 0;
    if ($avg_eval_pct_qry && $avg_eval_pct_qry->num_rows > 0) {
        $avg_eval_pct = floatval($avg_eval_pct_qry->fetch_assoc()['avg_pct']);
    }

    $best_eval_pct_qry = $conn->query("SELECT MAX((score / NULLIF(total_score,0)) * 100) AS best_pct FROM evaluation_history WHERE user_id = {$user_id}");
    $best_eval_pct = 0;
    if ($best_eval_pct_qry && $best_eval_pct_qry->num_rows > 0) {
        $best_eval_pct = floatval($best_eval_pct_qry->fetch_assoc()['best_pct']);
    }

    $latest_eval_qry = $conn->query(
        "SELECT eh.evaluation_id, eh.score, eh.total_score
         FROM evaluation_history eh
         INNER JOIN (
            SELECT evaluation_id, MAX(id) AS max_id
            FROM evaluation_history
            WHERE user_id = {$user_id}
            GROUP BY evaluation_id
         ) x ON x.max_id = eh.id"
    );

    $passed_count = 0;
    $failed_count = 0;
    $attempted_unique = 0;
    if ($latest_eval_qry && $latest_eval_qry->num_rows > 0) {
        while ($row = $latest_eval_qry->fetch_assoc()) {
            $attempted_unique++;
            $pct = (intval($row['total_score']) > 0) ? (floatval($row['score']) / floatval($row['total_score']) * 100) : 0;
            if ($pct >= 70) {
                $passed_count++;
            } else {
                $failed_count++;
            }
        }
    }
    $pending_count = max(0, $assigned_evaluations - $attempted_unique);

    $quiz_title_labels = array();
    $quiz_title_values = array();
    $quiz_attempt_qry = $conn->query(
        "SELECT q.title, COUNT(h.id) AS total
         FROM history h
         INNER JOIN quiz_list q ON q.id = h.quiz_id
         WHERE h.user_id = {$user_id}
         GROUP BY q.id, q.title
         ORDER BY total DESC, q.title ASC
         LIMIT 6"
    );
    if ($quiz_attempt_qry && $quiz_attempt_qry->num_rows > 0) {
        while ($row = $quiz_attempt_qry->fetch_assoc()) {
            $quiz_title_labels[] = $row['title'];
            $quiz_title_values[] = intval($row['total']);
        }
    }

    $quiz_map = array();
    $quiz_daily_qry = $conn->query(
        "SELECT DATE(date_updated) AS day_key, COUNT(id) AS total
         FROM history
         WHERE user_id = {$user_id} AND DATE(date_updated) >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
         GROUP BY DATE(date_updated)"
    );
    if ($quiz_daily_qry && $quiz_daily_qry->num_rows > 0) {
        while ($row = $quiz_daily_qry->fetch_assoc()) {
            $quiz_map[$row['day_key']] = intval($row['total']);
        }
    }

    $eval_map = array();
    $eval_daily_qry = $conn->query(
        "SELECT DATE(date_updated) AS day_key, COUNT(id) AS total
         FROM evaluation_history
         WHERE user_id = {$user_id} AND DATE(date_updated) >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
         GROUP BY DATE(date_updated)"
    );
    if ($eval_daily_qry && $eval_daily_qry->num_rows > 0) {
        while ($row = $eval_daily_qry->fetch_assoc()) {
            $eval_map[$row['day_key']] = intval($row['total']);
        }
    }

    $quiz_daily_values = array();
    $eval_daily_values = array();
    foreach ($days_keys as $day_key) {
        $quiz_daily_values[] = isset($quiz_map[$day_key]) ? intval($quiz_map[$day_key]) : 0;
        $eval_daily_values[] = isset($eval_map[$day_key]) ? intval($eval_map[$day_key]) : 0;
    }
}
?>

<style>
    .dashboard-hero {
        border-radius: 18px;
        background: linear-gradient(130deg, #102c52 0%, #0f4c81 50%, #1f8a70 100%);
        color: #fff;
        padding: 24px;
        margin-bottom: 18px;
        box-shadow: 0 18px 40px rgba(16, 44, 82, 0.22);
    }

    .hero-role {
        background: rgba(255, 255, 255, 0.2);
        display: inline-block;
        padding: 4px 12px;
        border-radius: 999px;
        font-size: 0.85rem;
        margin-bottom: 10px;
    }

    .kpi-card {
        border-radius: 14px;
        padding: 16px;
        color: #fff;
        position: relative;
        overflow: hidden;
        min-height: 120px;
    }

    .kpi-card::after {
        content: '';
        position: absolute;
        width: 140px;
        height: 140px;
        right: -40px;
        top: -40px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.16);
    }

    .kpi-a { background: linear-gradient(135deg, #0f4c81 0%, #1d71b8 100%); }
    .kpi-b { background: linear-gradient(135deg, #1f8a70 0%, #2db79a 100%); }
    .kpi-c { background: linear-gradient(135deg, #ff7a59 0%, #ff9d57 100%); }
    .kpi-d { background: linear-gradient(135deg, #6a4c93 0%, #8a63bf 100%); }

    .kpi-title {
        font-size: 0.82rem;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        opacity: 0.9;
        margin-bottom: 8px;
    }

    .kpi-value {
        font-size: 2rem;
        line-height: 1.1;
        font-weight: 800;
        margin-bottom: 4px;
        position: relative;
        z-index: 1;
    }

    .kpi-sub {
        font-size: 0.85rem;
        position: relative;
        z-index: 1;
    }

    .chart-card {
        border-radius: 16px;
        background: #fff;
        padding: 16px;
        height: 100%;
    }

    .chart-title {
        font-weight: 700;
        color: #102c52;
        margin-bottom: 8px;
    }

    .chart-wrap {
        position: relative;
        height: 280px;
    }

    .fade-in {
        opacity: 0;
        transform: translateY(14px);
        animation: riseIn 0.6s ease forwards;
    }

    .delay-1 { animation-delay: 0.05s; }
    .delay-2 { animation-delay: 0.12s; }
    .delay-3 { animation-delay: 0.2s; }

    @keyframes riseIn {
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @media (max-width: 768px) {
        .kpi-value {
            font-size: 1.7rem;
        }

        .chart-wrap {
            height: 240px;
        }
    }
</style>

<div class="dashboard-hero fade-in">
    <div class="hero-role"><?php echo htmlspecialchars($role_label); ?></div>
    <h3 class="mb-1">Panel de Control</h3>
    <p class="mb-0">Resumen visual de actividad académica y desempeño de los últimos 7 días.</p>
</div>

<?php if ($user_type !== 3): ?>
<div class="row g-3 mb-2">
    <div class="col-md-6 col-xl-3 fade-in delay-1"><div class="kpi-card kpi-a"><div class="kpi-title">Cuestionarios</div><div class="kpi-value"><?php echo $quiz_total; ?></div><div class="kpi-sub">Preguntas registradas: <?php echo $question_total; ?></div></div></div>
    <div class="col-md-6 col-xl-3 fade-in delay-1"><div class="kpi-card kpi-b"><div class="kpi-title">Estudiantes Impactados</div><div class="kpi-value"><?php echo $student_total; ?></div><div class="kpi-sub">Usuarios activos en alcance</div></div></div>
    <div class="col-md-6 col-xl-3 fade-in delay-2"><div class="kpi-card kpi-c"><div class="kpi-title">Evaluaciones</div><div class="kpi-value"><?php echo $evaluation_total; ?></div><div class="kpi-sub">Intentos: <?php echo $evaluation_attempts_total; ?></div></div></div>
    <div class="col-md-6 col-xl-3 fade-in delay-2"><div class="kpi-card kpi-d"><div class="kpi-title">Promedio Evaluación</div><div class="kpi-value"><?php echo number_format($avg_eval_pct, 1); ?>%</div><div class="kpi-sub">Intentos quiz: <?php echo $quiz_attempts_total; ?></div></div></div>
</div>

<div class="row g-3">
    <div class="col-xl-5 fade-in delay-2">
        <div class="chart-card">
            <div class="chart-title">Preguntas por Categoría</div>
            <div class="chart-wrap"><canvas id="chartQuestionsByCategory"></canvas></div>
        </div>
    </div>
    <div class="col-xl-7 fade-in delay-3">
        <div class="chart-card">
            <div class="chart-title">Actividad Reciente</div>
            <div class="chart-wrap"><canvas id="chartRecentActivity"></canvas></div>
        </div>
    </div>
    <div class="col-12 fade-in delay-3">
        <div class="chart-card">
            <div class="chart-title">Top Rendimiento Estudiantil</div>
            <div class="chart-wrap"><canvas id="chartStudentPerformance"></canvas></div>
        </div>
    </div>
</div>
<?php else: ?>
<div class="row g-3 mb-2">
    <div class="col-md-6 col-xl-3 fade-in delay-1"><div class="kpi-card kpi-a"><div class="kpi-title">Cuestionarios Disponibles</div><div class="kpi-value"><?php echo $assigned_quizzes; ?></div><div class="kpi-sub">Intentos realizados: <?php echo $quiz_attempts_total; ?></div></div></div>
    <div class="col-md-6 col-xl-3 fade-in delay-1"><div class="kpi-card kpi-b"><div class="kpi-title">Evaluaciones Asignadas</div><div class="kpi-value"><?php echo $assigned_evaluations; ?></div><div class="kpi-sub">Intentos realizados: <?php echo $evaluation_attempts_total; ?></div></div></div>
    <div class="col-md-6 col-xl-3 fade-in delay-2"><div class="kpi-card kpi-c"><div class="kpi-title">Promedio General</div><div class="kpi-value"><?php echo number_format($avg_eval_pct, 1); ?>%</div><div class="kpi-sub">Sobre evaluaciones rendidas</div></div></div>
    <div class="col-md-6 col-xl-3 fade-in delay-2"><div class="kpi-card kpi-d"><div class="kpi-title">Mejor Nota</div><div class="kpi-value"><?php echo number_format($best_eval_pct, 1); ?>%</div><div class="kpi-sub">Máximo histórico personal</div></div></div>
</div>

<div class="row g-3">    
    <div class="col-xl-8 fade-in delay-3">
        <div class="chart-card">
            <div class="chart-title">Actividad de la Semana</div>
            <div class="chart-wrap"><canvas id="chartStudentActivity"></canvas></div>
        </div>
    </div>    
</div>
<?php endif; ?>

<?php include('footer_adminlte.php'); ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
(() => {
    const labels7 = <?php echo json_encode($days_labels); ?>;

    const palette = {
        blue: '#0f4c81',
        cyan: '#2db79a',
        orange: '#ff7a59',
        violet: '#8a63bf',
        slate: '#607d8b'
    };

    <?php if ($user_type !== 3): ?>
    const categoryLabels = <?php echo json_encode($cat_labels); ?>;
    const categoryValues = <?php echo json_encode($cat_values); ?>;
    const quizDaily = <?php echo json_encode($quiz_daily_values); ?>;
    const evalDaily = <?php echo json_encode($eval_daily_values); ?>;
    const studentPerfLabels = <?php echo json_encode($student_perf_labels); ?>;
    const studentPerfValues = <?php echo json_encode($student_perf_values); ?>;

    new Chart(document.getElementById('chartQuestionsByCategory'), {
        type: 'bar',
        data: {
            labels: categoryLabels,
            datasets: [{
                label: 'Preguntas',
                data: categoryValues,
                borderRadius: 8,
                backgroundColor: ['#0f4c81', '#1f8a70', '#ff7a59', '#6a4c93', '#1d71b8', '#ff9d57', '#2db79a', '#8a63bf']
            }]
        },
        options: {
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
        }
    });

    new Chart(document.getElementById('chartRecentActivity'), {
        type: 'line',
        data: {
            labels: labels7,
            datasets: [
                {
                    label: 'Intentos Quiz',
                    data: quizDaily,
                    borderColor: palette.blue,
                    backgroundColor: 'rgba(15,76,129,0.12)',
                    fill: true,
                    tension: 0.35
                },
                {
                    label: 'Intentos Evaluación',
                    data: evalDaily,
                    borderColor: palette.orange,
                    backgroundColor: 'rgba(255,122,89,0.12)',
                    fill: true,
                    tension: 0.35
                }
            ]
        },
        options: {
            maintainAspectRatio: false,
            scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
        }
    });

    new Chart(document.getElementById('chartStudentPerformance'), {
        type: 'bar',
        data: {
            labels: studentPerfLabels,
            datasets: [{
                label: 'Promedio %',
                data: studentPerfValues,
                borderRadius: 8,
                backgroundColor: palette.cyan
            }]
        },
        options: {
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100
                }
            }
        }
    });
    <?php else: ?>
    const quizDaily = <?php echo json_encode($quiz_daily_values); ?>;
    const evalDaily = <?php echo json_encode($eval_daily_values); ?>;
    const quizTitleLabels = <?php echo json_encode($quiz_title_labels); ?>;
    const quizTitleValues = <?php echo json_encode($quiz_title_values); ?>;

    new Chart(document.getElementById('chartEvalStatus'), {
        type: 'doughnut',
        data: {
            labels: ['Aprobadas', 'Reprobadas', 'Pendientes'],
            datasets: [{
                data: [<?php echo intval($passed_count); ?>, <?php echo intval($failed_count); ?>, <?php echo intval($pending_count); ?>],
                backgroundColor: [palette.cyan, palette.orange, palette.slate],
                borderWidth: 1
            }]
        },
        options: {
            maintainAspectRatio: false
        }
    });

    new Chart(document.getElementById('chartStudentActivity'), {
        type: 'line',
        data: {
            labels: labels7,
            datasets: [
                {
                    label: 'Intentos Quiz',
                    data: quizDaily,
                    borderColor: palette.violet,
                    backgroundColor: 'rgba(138,99,191,0.14)',
                    fill: true,
                    tension: 0.35
                },
                {
                    label: 'Intentos Evaluación',
                    data: evalDaily,
                    borderColor: palette.blue,
                    backgroundColor: 'rgba(15,76,129,0.12)',
                    fill: true,
                    tension: 0.35
                }
            ]
        },
        options: {
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { precision: 0 }
                }
            }
        }
    });

    new Chart(document.getElementById('chartAttemptsByQuiz'), {
        type: 'bar',
        data: {
            labels: quizTitleLabels,
            datasets: [{
                label: 'Intentos',
                data: quizTitleValues,
                borderRadius: 8,
                backgroundColor: '#1d71b8'
            }]
        },
        options: {
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
        }
    });
    <?php endif; ?>
})();
</script>

