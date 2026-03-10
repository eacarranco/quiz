<?php
include('auth.php');
include('db_connect.php');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(array('status' => 0, 'msg' => 'Método no permitido.'));
    exit;
}

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

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$level_name = isset($_POST['level_name']) ? trim($_POST['level_name']) : '';
$state = isset($_POST['state']) ? intval($_POST['state']) : 1;
$state = $state === 0 ? 0 : 1;

if ($level_name === '') {
    echo json_encode(array('status' => 0, 'msg' => 'Ingrese el nombre del nivel.'));
    exit;
}

$level_name_sql = $conn->real_escape_string($level_name);

if ($id > 0) {
    $exists = $conn->query("SELECT id FROM levels WHERE id = {$id} LIMIT 1");
    if (!$exists || $exists->num_rows === 0) {
        echo json_encode(array('status' => 0, 'msg' => 'Nivel no encontrado.'));
        exit;
    }

    $dup = $conn->query("SELECT id FROM levels WHERE level_name = '{$level_name_sql}' AND id != {$id} LIMIT 1");
    if ($dup && $dup->num_rows > 0) {
        echo json_encode(array('status' => 0, 'msg' => 'Ya existe un nivel con ese nombre.'));
        exit;
    }

    if (!$conn->query("UPDATE levels SET level_name = '{$level_name_sql}', state = {$state} WHERE id = {$id}")) {
        echo json_encode(array('status' => 0, 'msg' => 'No se pudo actualizar el nivel.'));
        exit;
    }

    echo json_encode(array('status' => 1, 'msg' => 'Nivel actualizado correctamente.'));
    exit;
}

$dup = $conn->query("SELECT id FROM levels WHERE level_name = '{$level_name_sql}' LIMIT 1");
if ($dup && $dup->num_rows > 0) {
    echo json_encode(array('status' => 0, 'msg' => 'Ya existe un nivel con ese nombre.'));
    exit;
}

if (!$conn->query("INSERT INTO levels (level_name, state) VALUES ('{$level_name_sql}', {$state})")) {
    echo json_encode(array('status' => 0, 'msg' => 'No se pudo guardar el nivel.'));
    exit;
}

echo json_encode(array('status' => 1, 'msg' => 'Nivel guardado correctamente.'));
exit;
