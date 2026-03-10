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

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
if ($id < 1) {
	echo json_encode(array('status' => 0, 'msg' => 'ID de cuestionario inválido.'));
	exit;
}

$where_owner = '';
if (intval($_SESSION['login_user_type']) === 2) {
	$where_owner = ' AND user_id = ' . intval($_SESSION['login_id']) . ' ';
}

$quiz = $conn->query("SELECT id FROM quiz_list WHERE id = {$id} {$where_owner} LIMIT 1");
if (!$quiz || $quiz->num_rows === 0) {
	echo json_encode(array('status' => 0, 'msg' => 'Cuestionario no encontrado o sin permisos.'));
	exit;
}

$conn->begin_transaction();

try {
	$question_ids = array();
	$qres = $conn->query("SELECT id FROM questions WHERE qid = {$id}");
	if ($qres && $qres->num_rows > 0) {
		while ($qrow = $qres->fetch_assoc()) {
			$question_ids[] = intval($qrow['id']);
		}
	}

	if (count($question_ids) > 0) {
		$ids_csv = implode(',', $question_ids);
		if (!$conn->query("DELETE FROM question_opt WHERE question_id IN ({$ids_csv})")) {
			throw new Exception('No se pudieron eliminar opciones de preguntas.');
		}
		if (!$conn->query("DELETE FROM answers WHERE question_id IN ({$ids_csv})")) {
			throw new Exception('No se pudieron eliminar respuestas por pregunta.');
		}
	}

	if (!$conn->query("DELETE FROM answers WHERE quiz_id = {$id}")) {
		throw new Exception('No se pudieron eliminar respuestas del cuestionario.');
	}

	if (!$conn->query("DELETE FROM history WHERE quiz_id = {$id}")) {
		throw new Exception('No se pudo eliminar historial del cuestionario.');
	}

	if (!$conn->query("DELETE FROM quiz_student_list WHERE quiz_id = {$id}")) {
		throw new Exception('No se pudo eliminar asignaciones de estudiantes.');
	}

	if (!$conn->query("DELETE FROM questions WHERE qid = {$id}")) {
		throw new Exception('No se pudieron eliminar preguntas del cuestionario.');
	}

	if (!$conn->query("DELETE FROM quiz_list WHERE id = {$id}")) {
		throw new Exception('No se pudo eliminar el cuestionario.');
	}

	$conn->commit();
	echo json_encode(array('status' => 1, 'msg' => 'Cuestionario eliminado correctamente.'));
	exit;
} catch (Exception $e) {
	$conn->rollback();
	echo json_encode(array('status' => 0, 'msg' => $e->getMessage()));
	exit;
}