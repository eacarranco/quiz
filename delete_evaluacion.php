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
    echo json_encode(array('status' => 0, 'msg' => 'ID inválido.'));
    exit;
}

$where_owner = '';
if (intval($_SESSION['login_user_type']) === 2) {
    $where_owner = ' AND created_by = ' . intval($_SESSION['login_id']) . ' ';
}

$exists = $conn->query("SELECT id FROM evaluation_list WHERE id = {$id} {$where_owner} LIMIT 1");
if (!$exists || $exists->num_rows === 0) {
    echo json_encode(array('status' => 0, 'msg' => 'Evaluación no encontrada o sin permisos.'));
    exit;
}

$conn->begin_transaction();

try {
    $history_ids = array();
    $hqry = $conn->query("SELECT id FROM evaluation_history WHERE evaluation_id = {$id}");
    if ($hqry && $hqry->num_rows > 0) {
        while ($hrow = $hqry->fetch_assoc()) {
            $history_ids[] = intval($hrow['id']);
        }
    }

    if (!$conn->query("DELETE FROM evaluation_answers WHERE evaluation_id = {$id}")) {
        throw new Exception('No se pudieron eliminar respuestas de evaluación.');
    }

    if (count($history_ids) > 0) {
        $history_csv = implode(',', $history_ids);
        if (!$conn->query("DELETE FROM evaluation_answers WHERE history_id IN ({$history_csv})")) {
            throw new Exception('No se pudieron eliminar respuestas por historial.');
        }
    }

    if (!$conn->query("DELETE FROM evaluation_history WHERE evaluation_id = {$id}")) {
        throw new Exception('No se pudo eliminar el historial de evaluación.');
    }

    if (!$conn->query("DELETE FROM evaluation_student_list WHERE evaluation_id = {$id}")) {
        throw new Exception('No se pudo eliminar la asignación de estudiantes.');
    }

    if (!$conn->query("DELETE FROM evaluation_detail WHERE evaluation_id = {$id}")) {
        throw new Exception('No se pudo eliminar el detalle de la evaluación.');
    }

    if (!$conn->query("DELETE FROM evaluation_list WHERE id = {$id}")) {
        throw new Exception('No se pudo eliminar la evaluación.');
    }

    $conn->commit();
    echo json_encode(array('status' => 1, 'msg' => 'Evaluación eliminada correctamente.'));
    exit;
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(array('status' => 0, 'msg' => $e->getMessage()));
    exit;
}
