<?php
include('auth.php');
include('db_connect.php');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(array('status' => 0, 'msg' => 'Método no permitido.'));
    exit;
}

if (!isset($_SESSION['login_user_type']) || intval($_SESSION['login_user_type']) !== 3) {
    echo json_encode(array('status' => 0, 'msg' => 'No autorizado.'));
    exit;
}

$evaluation_id = isset($_POST['evaluation_id']) ? intval($_POST['evaluation_id']) : 0;
$user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
$question_ids = isset($_POST['question_id']) && is_array($_POST['question_id']) ? $_POST['question_id'] : array();
$option_ids = isset($_POST['option_id']) && is_array($_POST['option_id']) ? $_POST['option_id'] : array();

if ($evaluation_id < 1 || $user_id < 1 || count($question_ids) === 0) {
    echo json_encode(array('status' => 0, 'msg' => 'Datos incompletos para enviar evaluación.'));
    exit;
}

if ($user_id !== intval($_SESSION['login_id'])) {
    echo json_encode(array('status' => 0, 'msg' => 'Usuario inválido.'));
    exit;
}

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

$assigned = $conn->query("SELECT id FROM evaluation_student_list WHERE evaluation_id = {$evaluation_id} AND user_id = {$user_id} LIMIT 1");
if (!$assigned || $assigned->num_rows === 0) {
    echo json_encode(array('status' => 0, 'msg' => 'No tiene permiso para rendir esta evaluación.'));
    exit;
}

$conn->begin_transaction();

try {
    $points = 0;
    $total_questions = 0;
    $answers_to_insert = array();

    foreach ($question_ids as $qid_raw) {
        $qid = intval($qid_raw);
        if ($qid < 1) {
            continue;
        }

        $total_questions++;
        $selected_option = isset($option_ids[$qid]) ? intval($option_ids[$qid]) : 0;
        $is_right = 0;

        if ($selected_option > 0) {
            $opt_qry = $conn->query("SELECT is_right FROM question_opt WHERE id = {$selected_option} AND question_id = {$qid} LIMIT 1");
            if ($opt_qry && $opt_qry->num_rows > 0) {
                $is_right = intval($opt_qry->fetch_assoc()['is_right']) > 0 ? 1 : 0;
            }
        }

        if ($is_right === 1) {
            $points++;
        }

        $answers_to_insert[] = array(
            'question_id' => $qid,
            'option_id' => $selected_option,
            'is_right' => $is_right
        );
    }

    if ($total_questions < 1) {
        throw new Exception('No se encontraron preguntas para evaluar.');
    }

    $save_hist = $conn->query("INSERT INTO evaluation_history (evaluation_id, user_id, score, total_score) VALUES ({$evaluation_id}, {$user_id}, {$points}, {$total_questions})");
    if (!$save_hist) {
        throw new Exception('No se pudo guardar el resultado de la evaluación.');
    }

    $history_id = intval($conn->insert_id);

    foreach ($answers_to_insert as $ans) {
        $qid = intval($ans['question_id']);
        $oid = intval($ans['option_id']);
        $is_right = intval($ans['is_right']);

        $ins_ans = $conn->query("INSERT INTO evaluation_answers (history_id, user_id, evaluation_id, question_id, option_id, is_right) VALUES ({$history_id}, {$user_id}, {$evaluation_id}, {$qid}, {$oid}, {$is_right})");
        if (!$ins_ans) {
            throw new Exception('No se pudo guardar el detalle de respuestas.');
        }
    }

    $attempt_no = 0;
    $attempt_qry = $conn->query("SELECT COUNT(id) AS total FROM evaluation_history WHERE evaluation_id = {$evaluation_id} AND user_id = {$user_id} AND id <= {$history_id}");
    if ($attempt_qry && $attempt_qry->num_rows > 0) {
        $attempt_row = $attempt_qry->fetch_assoc();
        $attempt_no = intval($attempt_row['total']);
    }

    $conn->commit();
    echo json_encode(array('status' => 1, 'score' => $points . '/' . $total_questions, 'history_id' => $history_id, 'attempt_no' => $attempt_no));
    exit;
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(array('status' => 0, 'msg' => $e->getMessage()));
    exit;
}
