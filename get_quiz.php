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

$where_owner = '';
if (intval($_SESSION['login_user_type']) === 2) {
	$where_owner = ' AND user_id = ' . intval($_SESSION['login_id']) . ' ';
}

$qry = $conn->query("SELECT id, title, qpoints, randomize_options, user_id, quiz_cat_id, level_id FROM quiz_list WHERE id = {$id} {$where_owner} LIMIT 1");
if (!$qry || $qry->num_rows === 0) {
	echo json_encode(array('status' => 0, 'msg' => 'Cuestionario no encontrado o sin permisos.'));
	exit;
}

$row = $qry->fetch_assoc();
$row['status'] = 1;
echo json_encode($row);
exit;
?>