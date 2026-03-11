<?php
include('auth.php');
include('db_connect.php');

header('Content-Type: application/json');

if ($_SESSION['login_user_type'] == 3) {
    echo json_encode(array('status' => 0, 'msg' => 'No autorizado.'));
    exit;
}

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
if ($id < 1) {
    echo json_encode(array('status' => 0, 'msg' => 'ID inválido.'));
    exit;
}

$exists = $conn->query("SELECT id, created_by FROM quiz_category WHERE id = {$id} LIMIT 1");
if (!$exists || $exists->num_rows === 0) {
    echo json_encode(array('status' => 0, 'msg' => 'La categoría no existe.'));
    exit;
}

$cat_row = $exists->fetch_assoc();
$created_by = intval($cat_row['created_by']);

// Validación de permisos: solo admin o el creador puede eliminar
if ($_SESSION['login_user_type'] == 2 && intval($_SESSION['login_id']) !== $created_by) {
    echo json_encode(array('status' => 0, 'msg' => 'No tiene permisos para eliminar esta categoría.'));
    exit;
}

$in_use_qry = $conn->query("SELECT COUNT(id) AS total FROM quiz_list WHERE quiz_cat_id = {$id}");
$in_use = 0;
if ($in_use_qry && $in_use_qry->num_rows > 0) {
    $row = $in_use_qry->fetch_assoc();
    $in_use = intval($row['total']);
}

if ($in_use > 0) {
    echo json_encode(array('status' => 0, 'msg' => 'No se puede eliminar: hay cuestionarios asociados a esta categoría.'));
    exit;
}

$del = $conn->query("DELETE FROM quiz_category WHERE id = {$id}");
if ($del) {
    echo json_encode(array('status' => 1, 'msg' => 'Categoría eliminada.'));
} else {
    echo json_encode(array('status' => 0, 'msg' => 'No se pudo eliminar la categoría.'));
}
exit;
