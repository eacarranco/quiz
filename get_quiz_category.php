<?php
include('auth.php');
include('db_connect.php');

header('Content-Type: application/json');

if ($_SESSION['login_user_type'] == 3) {
    echo json_encode(array('status' => 0, 'msg' => 'No autorizado.'));
    exit;
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id < 1) {
    echo json_encode(array('status' => 0, 'msg' => 'ID inválido.'));
    exit;
}

$qry = $conn->query("SELECT id, cat_name, cat_descrip, state FROM quiz_category WHERE id = {$id} LIMIT 1");
if (!$qry || $qry->num_rows === 0) {
    echo json_encode(array('status' => 0, 'msg' => 'Categoría no encontrada.'));
    exit;
}

$row = $qry->fetch_assoc();
$state = intval($row['state']) > 0 ? 1 : 0;

echo json_encode(array(
    'status' => 1,
    'id' => intval($row['id']),
    'cat_name' => $row['cat_name'],
    'cat_descrip' => $row['cat_descrip'],
    'state' => $state
));
exit;
