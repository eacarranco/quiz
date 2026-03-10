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

$title = isset($_POST['title']) ? trim($_POST['title']) : '';
$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
$quiz_cat_id = isset($_POST['quiz_cat_id']) ? intval($_POST['quiz_cat_id']) : 0;
$qpoints = isset($_POST['qpoints']) ? intval($_POST['qpoints']) : 0;
$randomize_options = isset($_POST['randomize_options']) ? intval($_POST['randomize_options']) : 0;

if ($title === '') {
    echo json_encode(array('status' => 0, 'msg' => 'Ingrese el título del cuestionario.'));
    exit;
}

if ($qpoints < 1) {
    echo json_encode(array('status' => 0, 'msg' => 'Los puntos por pregunta deben ser mayores o iguales a 1.'));
    exit;
}

if ($quiz_cat_id < 1) {
    echo json_encode(array('status' => 0, 'msg' => 'Seleccione una categoría.'));
    exit;
}

if ($randomize_options !== 1) {
    $randomize_options = 0;
}

if (intval($_SESSION['login_user_type']) === 2) {
    $user_id = intval($_SESSION['login_id']);
}

if ($user_id < 1) {
    echo json_encode(array('status' => 0, 'msg' => 'Seleccione un usuario válido.'));
    exit;
}

$user_check = $conn->query("SELECT id FROM users WHERE id = {$user_id} AND user_type = 2 LIMIT 1");
if (!$user_check || $user_check->num_rows === 0) {
    echo json_encode(array('status' => 0, 'msg' => 'El usuario seleccionado no es válido.'));
    exit;
}

$cat_check = $conn->query("SELECT id FROM quiz_category WHERE id = {$quiz_cat_id} AND state = 1 LIMIT 1");
if (!$cat_check || $cat_check->num_rows === 0) {
    echo json_encode(array('status' => 0, 'msg' => 'La categoría seleccionada no es válida.'));
    exit;
}

$title = $conn->real_escape_string($title);

if ($id > 0) {
    $where_owner = '';
    if (intval($_SESSION['login_user_type']) === 2) {
        $where_owner = ' AND user_id = ' . intval($_SESSION['login_id']) . ' ';
    }

    $exists = $conn->query("SELECT id FROM quiz_list WHERE id = {$id} {$where_owner} LIMIT 1");
    if (!$exists || $exists->num_rows === 0) {
        echo json_encode(array('status' => 0, 'msg' => 'Cuestionario no encontrado o sin permisos para editar.'));
        exit;
    }

    $save_sql = "UPDATE quiz_list SET title = '{$title}', qpoints = {$qpoints}, randomize_options = {$randomize_options}, user_id = {$user_id}, quiz_cat_id = {$quiz_cat_id} WHERE id = {$id}";
    $save = $conn->query($save_sql);

    if ($save) {
        echo json_encode(array(
            'status' => 1,
            'id' => $id,
            'msg' => 'Cuestionario actualizado correctamente.'
        ));
        exit;
    }

    echo json_encode(array('status' => 0, 'msg' => 'No fue posible actualizar el cuestionario.'));
    exit;
}

$insert_sql = "INSERT INTO quiz_list (title, qpoints, randomize_options, user_id, quiz_cat_id) VALUES ('{$title}', {$qpoints}, {$randomize_options}, {$user_id}, {$quiz_cat_id})";
$insert = $conn->query($insert_sql);

if ($insert) {
    echo json_encode(array(
        'status' => 1,
        'id' => intval($conn->insert_id),
        'msg' => 'Cuestionario guardado correctamente.'
    ));
    exit;
}

echo json_encode(array('status' => 0, 'msg' => 'No fue posible guardar el cuestionario.'));
exit;
