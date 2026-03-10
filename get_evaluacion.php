<?php
include('auth.php');
include('db_connect.php');

header('Content-Type: application/json');

$has_randomize_column = $conn->query("SHOW COLUMNS FROM evaluation_list LIKE 'randomize_options'");
if ($has_randomize_column && $has_randomize_column->num_rows === 0) {
    $conn->query("ALTER TABLE evaluation_list ADD COLUMN randomize_options TINYINT(1) NOT NULL DEFAULT 1 AFTER total_questions");
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id < 1) {
    echo json_encode(array('status' => 0, 'msg' => 'ID inválido.'));
    exit;
}

if (!isset($_SESSION['login_user_type']) || intval($_SESSION['login_user_type']) === 3) {
    echo json_encode(array('status' => 0, 'msg' => 'No autorizado.'));
    exit;
}

$where_owner = '';
if (intval($_SESSION['login_user_type']) === 2) {
    $where_owner = ' AND created_by = ' . intval($_SESSION['login_id']) . ' ';
}

$eval_qry = $conn->query("SELECT id, eval_name, eval_description, total_questions, randomize_options FROM evaluation_list WHERE id = {$id} {$where_owner} LIMIT 1");
if (!$eval_qry || $eval_qry->num_rows === 0) {
    echo json_encode(array('status' => 0, 'msg' => 'Evaluación no encontrada o sin permisos.'));
    exit;
}

$evaluation = $eval_qry->fetch_assoc();
$rules = array();
$value_type = 'cantidad';

$det_qry = $conn->query("SELECT quiz_cat_id, value_type, value_num, question_count FROM evaluation_detail WHERE evaluation_id = {$id} ORDER BY id ASC");
if ($det_qry && $det_qry->num_rows > 0) {
    while ($row = $det_qry->fetch_assoc()) {
        if (isset($row['value_type']) && ($row['value_type'] === 'cantidad' || $row['value_type'] === 'porcentaje')) {
            $value_type = $row['value_type'];
        }

        $rules[] = array(
            'quiz_cat_id' => intval($row['quiz_cat_id']),
            'value_num' => floatval($row['value_num']),
            'question_count' => intval($row['question_count'])
        );
    }
}

echo json_encode(array(
    'status' => 1,
    'id' => intval($evaluation['id']),
    'eval_name' => $evaluation['eval_name'],
    'eval_description' => $evaluation['eval_description'],
    'total_questions' => intval($evaluation['total_questions']),
    'randomize_options' => intval($evaluation['randomize_options']),
    'value_type' => $value_type,
    'rules' => $rules
));
exit;
