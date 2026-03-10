<?php
include('auth.php');
include('db_connect.php');

header('Content-Type: application/json');

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id < 1) {
    echo json_encode(array('status' => 0, 'msg' => 'ID inválido.'));
    exit;
}

if (!isset($_SESSION['login_user_type']) || intval($_SESSION['login_user_type']) === 3) {
    echo json_encode(array('status' => 0, 'msg' => 'No autorizado.'));
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

$eval = $conn->query("SELECT id FROM evaluation_list WHERE id = {$id} {$where_owner} LIMIT 1");
if (!$eval || $eval->num_rows === 0) {
    echo json_encode(array('status' => 0, 'msg' => 'Evaluación no encontrada o sin permisos.'));
    exit;
}

$students = array();
$qry = $conn->query("SELECT user_id FROM evaluation_student_list WHERE evaluation_id = {$id}");
if ($qry && $qry->num_rows > 0) {
    while ($row = $qry->fetch_assoc()) {
        $students[] = intval($row['user_id']);
    }
}

echo json_encode(array('status' => 1, 'students' => $students));
exit;
