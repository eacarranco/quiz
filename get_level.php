<?php
include('auth.php');
include('db_connect.php');

header('Content-Type: application/json');

if (!isset($_SESSION['login_user_type']) || intval($_SESSION['login_user_type']) !== 1) {
    echo json_encode(array('status' => 0, 'msg' => 'No autorizado.'));
    exit;
}

$conn->query("CREATE TABLE IF NOT EXISTS levels (
    id INT NOT NULL AUTO_INCREMENT,
    level_name VARCHAR(100) NOT NULL,
    state TINYINT(1) NOT NULL DEFAULT 1,
    date_updated DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_level_name (level_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id < 1) {
    echo json_encode(array('status' => 0, 'msg' => 'ID inválido.'));
    exit;
}

$qry = $conn->query("SELECT id, level_name, state FROM levels WHERE id = {$id} LIMIT 1");
if (!$qry || $qry->num_rows === 0) {
    echo json_encode(array('status' => 0, 'msg' => 'Nivel no encontrado.'));
    exit;
}

$row = $qry->fetch_assoc();
echo json_encode(array(
    'status' => 1,
    'id' => intval($row['id']),
    'level_name' => $row['level_name'],
    'state' => intval($row['state'])
));
exit;
