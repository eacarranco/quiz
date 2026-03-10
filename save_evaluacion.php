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

$conn->query("CREATE TABLE IF NOT EXISTS evaluation_list (
    id INT NOT NULL AUTO_INCREMENT,
    eval_name VARCHAR(180) NOT NULL,
    eval_description VARCHAR(500) DEFAULT NULL,
    total_questions INT NOT NULL,
    randomize_options TINYINT(1) NOT NULL DEFAULT 1,
    created_by INT NOT NULL,
    date_updated DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$has_randomize_column = $conn->query("SHOW COLUMNS FROM evaluation_list LIKE 'randomize_options'");
if ($has_randomize_column && $has_randomize_column->num_rows === 0) {
    $conn->query("ALTER TABLE evaluation_list ADD COLUMN randomize_options TINYINT(1) NOT NULL DEFAULT 1 AFTER total_questions");
}

$conn->query("CREATE TABLE IF NOT EXISTS evaluation_detail (
    id INT NOT NULL AUTO_INCREMENT,
    evaluation_id INT NOT NULL,
    quiz_cat_id INT NOT NULL,
    value_type VARCHAR(20) NOT NULL,
    value_num DECIMAL(10,2) NOT NULL,
    question_count INT NOT NULL,
    date_updated DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$eval_name = isset($_POST['eval_name']) ? trim($_POST['eval_name']) : '';
$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$eval_description = isset($_POST['eval_description']) ? trim($_POST['eval_description']) : '';
$total_questions = isset($_POST['total_questions']) ? intval($_POST['total_questions']) : 0;
$value_type = isset($_POST['value_type']) ? trim($_POST['value_type']) : '';
$randomize_options = isset($_POST['randomize_options']) ? intval($_POST['randomize_options']) : 1;
$rules_json = isset($_POST['rules_json']) ? $_POST['rules_json'] : '[]';
$created_by = intval($_SESSION['login_id']);

if ($randomize_options !== 0) {
    $randomize_options = 1;
}

if ($eval_name === '') {
    echo json_encode(array('status' => 0, 'msg' => 'Ingrese el nombre de la evaluación.'));
    exit;
}

if ($total_questions < 1) {
    echo json_encode(array('status' => 0, 'msg' => 'La cantidad total de preguntas debe ser mayor a 0.'));
    exit;
}

if ($value_type !== 'porcentaje' && $value_type !== 'cantidad') {
    echo json_encode(array('status' => 0, 'msg' => 'Seleccione un tipo de distribución válido para la evaluación.'));
    exit;
}

$rules = json_decode($rules_json, true);
if (!is_array($rules) || count($rules) === 0) {
    echo json_encode(array('status' => 0, 'msg' => 'Debe agregar al menos una regla de preguntas.'));
    exit;
}

$parsed_rules = array();
$assigned_total = 0;
$required_by_category = array();

foreach ($rules as $rule) {
    $quiz_cat_id = isset($rule['quiz_cat_id']) ? intval($rule['quiz_cat_id']) : 0;
    $value_num = isset($rule['value_num']) ? floatval($rule['value_num']) : 0;

    if ($quiz_cat_id < 1) {
        echo json_encode(array('status' => 0, 'msg' => 'Seleccione una categoría en todas las filas.'));
        exit;
    }

    $cat_chk = $conn->query("SELECT id FROM quiz_category WHERE id = {$quiz_cat_id} AND state = 1 LIMIT 1");
    if (!$cat_chk || $cat_chk->num_rows === 0) {
        echo json_encode(array('status' => 0, 'msg' => 'Existe una categoría inválida en las reglas.'));
        exit;
    }

    if ($value_num <= 0) {
        echo json_encode(array('status' => 0, 'msg' => 'El valor debe ser mayor a 0 en todas las filas.'));
        exit;
    }

    if ($value_type === 'porcentaje' && $value_num > 100) {
        echo json_encode(array('status' => 0, 'msg' => 'El porcentaje no puede ser mayor a 100.'));
        exit;
    }

    $question_count = $value_type === 'porcentaje'
        ? intval(round(($total_questions * $value_num) / 100, 0))
        : intval(round($value_num, 0));

    if ($question_count < 0) {
        $question_count = 0;
    }

    $assigned_total += $question_count;
    $parsed_rules[] = array(
        'quiz_cat_id' => $quiz_cat_id,
        'value_num' => $value_num,
        'question_count' => $question_count
    );

    if (!isset($required_by_category[$quiz_cat_id])) {
        $required_by_category[$quiz_cat_id] = 0;
    }
    $required_by_category[$quiz_cat_id] += $question_count;
}

if ($assigned_total !== $total_questions) {
    echo json_encode(array(
        'status' => 0,
        'msg' => 'La distribución de preguntas (' . $assigned_total . ') debe coincidir con el total (' . $total_questions . ').'
    ));
    exit;
}

foreach ($required_by_category as $quiz_cat_id => $required_questions) {
    if ($required_questions < 1) {
        continue;
    }

    $cat_name = 'ID ' . intval($quiz_cat_id);
    $cat_name_qry = $conn->query("SELECT cat_name FROM quiz_category WHERE id = " . intval($quiz_cat_id) . " LIMIT 1");
    if ($cat_name_qry && $cat_name_qry->num_rows > 0) {
        $cat_name_row = $cat_name_qry->fetch_assoc();
        $cat_name = $cat_name_row['cat_name'];
    }

    $available_qry = $conn->query("SELECT COUNT(DISTINCT q.id) AS total FROM questions q INNER JOIN quiz_list ql ON ql.id = q.qid WHERE ql.quiz_cat_id = " . intval($quiz_cat_id));
    $available_questions = 0;
    if ($available_qry && $available_qry->num_rows > 0) {
        $available_row = $available_qry->fetch_assoc();
        $available_questions = intval($available_row['total']);
    }

    if ($required_questions > $available_questions) {
        echo json_encode(array(
            'status' => 0,
            'msg' => 'No hay preguntas suficientes en la categoría "' . $cat_name . '". Requeridas: ' . $required_questions . ', disponibles: ' . $available_questions . '.'
        ));
        exit;
    }
}

$eval_name_sql = $conn->real_escape_string($eval_name);
$eval_desc_sql = $conn->real_escape_string($eval_description);

$conn->begin_transaction();

try {
    $evaluation_id = 0;
    if ($id > 0) {
        $where_owner = '';
        if (intval($_SESSION['login_user_type']) === 2) {
            $where_owner = ' AND created_by = ' . intval($_SESSION['login_id']) . ' ';
        }

        $exists = $conn->query("SELECT id FROM evaluation_list WHERE id = {$id} {$where_owner} LIMIT 1");
        if (!$exists || $exists->num_rows === 0) {
            throw new Exception('Evaluación no encontrada o sin permisos para editar.');
        }

        $upd_eval = $conn->query("UPDATE evaluation_list SET eval_name = '{$eval_name_sql}', eval_description = '{$eval_desc_sql}', total_questions = {$total_questions}, randomize_options = {$randomize_options} WHERE id = {$id}");
        if (!$upd_eval) {
            throw new Exception('No se pudo actualizar la evaluación.');
        }

        if (!$conn->query("DELETE FROM evaluation_detail WHERE evaluation_id = {$id}")) {
            throw new Exception('No se pudieron actualizar los detalles de la evaluación.');
        }

        $evaluation_id = $id;
    } else {
        $ins_eval = $conn->query("INSERT INTO evaluation_list (eval_name, eval_description, total_questions, randomize_options, created_by) VALUES ('{$eval_name_sql}', '{$eval_desc_sql}', {$total_questions}, {$randomize_options}, {$created_by})");
        if (!$ins_eval) {
            throw new Exception('No se pudo guardar la evaluación.');
        }
        $evaluation_id = intval($conn->insert_id);
    }

    foreach ($parsed_rules as $item) {
        $quiz_cat_id = intval($item['quiz_cat_id']);
        $value_type_sql = $conn->real_escape_string($value_type);
        $value_num = floatval($item['value_num']);
        $question_count = intval($item['question_count']);

        $ins_det = $conn->query("INSERT INTO evaluation_detail (evaluation_id, quiz_cat_id, value_type, value_num, question_count) VALUES ({$evaluation_id}, {$quiz_cat_id}, '{$value_type_sql}', {$value_num}, {$question_count})");
        if (!$ins_det) {
            throw new Exception('No se pudieron guardar los detalles de la evaluación.');
        }
    }

    $conn->commit();
    echo json_encode(array('status' => 1, 'msg' => ($id > 0 ? 'Evaluación actualizada correctamente.' : 'Evaluación guardada correctamente.'), 'id' => $evaluation_id));
    exit;
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(array('status' => 0, 'msg' => $e->getMessage()));
    exit;
}
