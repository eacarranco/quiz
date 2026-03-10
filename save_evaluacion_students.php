<?php
include('auth.php');
include('db_connect.php');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(array('status' => 0, 'msg' => 'Método no permitido.'));
    exit;
}

if (!isset($_SESSION['login_user_type']) || intval($_SESSION['login_user_type']) === 3) {
    echo json_encode(array('status' => 0, 'msg' => 'No autorizado.'));
    exit;
}

$evaluation_id = isset($_POST['evaluation_id']) ? intval($_POST['evaluation_id']) : 0;
if ($evaluation_id < 1) {
    echo json_encode(array('status' => 0, 'msg' => 'Evaluación inválida.'));
    exit;
}

$conn->query("CREATE TABLE IF NOT EXISTS evaluation_student_list (
    id INT NOT NULL AUTO_INCREMENT,
    evaluation_id INT NOT NULL,
    user_id INT NOT NULL,
    date_updated DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$where_owner = '';
if (intval($_SESSION['login_user_type']) === 2) {
    $where_owner = ' AND created_by = ' . intval($_SESSION['login_id']) . ' ';
}

$eval = $conn->query("SELECT id FROM evaluation_list WHERE id = {$evaluation_id} {$where_owner} LIMIT 1");
if (!$eval || $eval->num_rows === 0) {
    echo json_encode(array('status' => 0, 'msg' => 'Evaluación no encontrada o sin permisos.'));
    exit;
}

$students = isset($_POST['students']) ? $_POST['students'] : array();
if (!is_array($students)) {
    $students = array();
}

$valid_students = array();
foreach ($students as $sid) {
    $uid = intval($sid);
    if ($uid > 0) {
        $valid_students[$uid] = $uid;
    }
}

$conn->begin_transaction();

try {
    if (!$conn->query("DELETE FROM evaluation_student_list WHERE evaluation_id = {$evaluation_id}")) {
        throw new Exception('No se pudo limpiar la asignación previa.');
    }

    foreach ($valid_students as $uid) {
        $chk = $conn->query("SELECT id FROM users WHERE id = {$uid} AND user_type = 3 AND status = 1 LIMIT 1");
        if (!$chk || $chk->num_rows === 0) {
            continue;
        }

        $ins = $conn->query("INSERT INTO evaluation_student_list (evaluation_id, user_id) VALUES ({$evaluation_id}, {$uid})");
        if (!$ins) {
            throw new Exception('No se pudo guardar la asignación de estudiantes.');
        }
    }

    $conn->commit();
    echo json_encode(array('status' => 1, 'msg' => 'Estudiantes asignados correctamente.'));
    exit;
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(array('status' => 0, 'msg' => $e->getMessage()));
    exit;
}
