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

$conn->query("CREATE TABLE IF NOT EXISTS faculty_levels (
    id INT NOT NULL AUTO_INCREMENT,
    faculty_id INT NOT NULL,
    level_id INT NOT NULL,
    date_updated DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_faculty_level (faculty_id, level_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$has_level_id = $conn->query("SHOW COLUMNS FROM students LIKE 'level_id'");
if ($has_level_id && $has_level_id->num_rows === 0) {
    $conn->query("ALTER TABLE students ADD COLUMN level_id INT NULL AFTER user_id");
}

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
if ($id < 1) {
    echo json_encode(array('status' => 0, 'msg' => 'ID inválido.'));
    exit;
}

$exists = $conn->query("SELECT id FROM levels WHERE id = {$id} LIMIT 1");
if (!$exists || $exists->num_rows === 0) {
    echo json_encode(array('status' => 0, 'msg' => 'Nivel no encontrado.'));
    exit;
}

$in_students = $conn->query("SELECT COUNT(id) AS total FROM students WHERE level_id = {$id}");
$students_count = ($in_students && $in_students->num_rows > 0) ? intval($in_students->fetch_assoc()['total']) : 0;
if ($students_count > 0) {
    echo json_encode(array('status' => 0, 'msg' => 'No se puede eliminar: hay estudiantes asociados a este nivel.'));
    exit;
}

$in_faculty = $conn->query("SELECT COUNT(id) AS total FROM faculty_levels WHERE level_id = {$id}");
$faculty_count = ($in_faculty && $in_faculty->num_rows > 0) ? intval($in_faculty->fetch_assoc()['total']) : 0;
if ($faculty_count > 0) {
    echo json_encode(array('status' => 0, 'msg' => 'No se puede eliminar: hay profesores con este nivel asignado.'));
    exit;
}

if (!$conn->query("DELETE FROM levels WHERE id = {$id}")) {
    echo json_encode(array('status' => 0, 'msg' => 'No se pudo eliminar el nivel.'));
    exit;
}

echo json_encode(array('status' => 1, 'msg' => 'Nivel eliminado correctamente.'));
exit;
