<?php
include('auth.php');
include('db_connect.php');

header('Content-Type: application/json');

if ($_SESSION['login_user_type'] == 3) {
    echo json_encode(array('status' => 0, 'msg' => 'No autorizado.'));
    exit;
}

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$cat_name = isset($_POST['cat_name']) ? trim($_POST['cat_name']) : '';
$cat_descrip = isset($_POST['cat_descrip']) ? trim($_POST['cat_descrip']) : '';
$state = isset($_POST['state']) ? intval($_POST['state']) : 1;

if ($state !== 0 && $state !== 1) {
    $state = 1;
}

if ($cat_name === '') {
    echo json_encode(array('status' => 0, 'msg' => 'Ingrese el nombre de la categoría.'));
    exit;
}

if (mb_strlen($cat_name) > 150) {
    echo json_encode(array('status' => 0, 'msg' => 'El nombre no debe superar 150 caracteres.'));
    exit;
}

if (mb_strlen($cat_descrip) > 200) {
    echo json_encode(array('status' => 0, 'msg' => 'La descripción no debe superar 200 caracteres.'));
    exit;
}

$cat_name = $conn->real_escape_string($cat_name);
$cat_descrip = $conn->real_escape_string($cat_descrip);

$exists_sql = "SELECT id FROM quiz_category WHERE cat_name = '{$cat_name}'";
if ($id > 0) {
    $exists_sql .= " AND id != {$id}";
}
$exists_sql .= " LIMIT 1";

$exists = $conn->query($exists_sql);
if ($exists && $exists->num_rows > 0) {
    echo json_encode(array('status' => 0, 'msg' => 'La categoría ya existe.'));
    exit;
}

if ($id > 0) {
    $check = $conn->query("SELECT id FROM quiz_category WHERE id = {$id} LIMIT 1");
    if (!$check || $check->num_rows === 0) {
        echo json_encode(array('status' => 0, 'msg' => 'La categoría a editar no existe.'));
        exit;
    }

    $save = $conn->query("UPDATE quiz_category SET cat_name = '{$cat_name}', cat_descrip = '{$cat_descrip}', state = {$state} WHERE id = {$id}");
    $saved_id = $id;
    $ok_msg = 'Categoría actualizada';
} else {
    $save = $conn->query("INSERT INTO quiz_category (cat_name, cat_descrip, state) VALUES ('{$cat_name}', '{$cat_descrip}', {$state})");
    $saved_id = intval($conn->insert_id);
    $ok_msg = 'Categoría creada';
}

if ($save) {
    echo json_encode(array(
        'status' => 1,
        'id' => $saved_id,
        'cat_name' => stripslashes($cat_name),
        'state' => $state,
        'msg' => $ok_msg
    ));
} else {
    echo json_encode(array('status' => 0, 'msg' => 'No se pudo guardar la categoría.'));
}
exit;
