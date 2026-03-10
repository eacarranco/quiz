<?php
include 'db_connect.php';

// Obtener datos del POST
$cat_name = isset($_POST['cat_name']) ? trim($_POST['cat_name']) : '';
$cat_description = isset($_POST['cat_description']) ? trim($_POST['cat_description']) : '';

// Validar que el nombre no esté vacío
if (empty($cat_name)) {
	echo json_encode([
		'status' => 0,
		'msg' => 'El nombre de la categoría es requerido'
	]);
	exit;
}

// Validar longitud del nombre
if (strlen($cat_name) > 100) {
	echo json_encode([
		'status' => 0,
		'msg' => 'El nombre no puede exceder 100 caracteres'
	]);
	exit;
}

// Escapar datos para SQL
$cat_name = addslashes($cat_name);
$cat_description = addslashes($cat_description);

// Verificar si la categoría ya existe
$check_qry = $conn->query("SELECT id FROM question_category WHERE cat_name = '$cat_name'");
if ($check_qry->num_rows > 0) {
	echo json_encode([
		'status' => 0,
		'msg' => 'Esta categoría ya existe'
	]);
	exit;
}

// Insertar nueva categoría
$insert_qry = $conn->query("
	INSERT INTO question_category (cat_name, cat_description, state) 
	VALUES ('$cat_name', '$cat_description', 1)
");

if ($insert_qry) {
	echo json_encode([
		'status' => 1,
		'id' => $conn->insert_id,
		'msg' => 'Categoría creada exitosamente'
	]);
} else {
	echo json_encode([
		'status' => 0,
		'msg' => 'Error al crear la categoría: ' . $conn->error
	]);
}
?>

