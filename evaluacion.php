<?php
include('auth.php');
include('db_connect.php');

if ($_SESSION['login_user_type'] == 3) {
    header('Location: student_quiz_list.php');
    exit;
}

$title = 'Evaluación';

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

// Add created_by column to quiz_category if it doesn't exist
$has_created_by = $conn->query("SHOW COLUMNS FROM quiz_category LIKE 'created_by'");
if ($has_created_by && $has_created_by->num_rows === 0) {
    $conn->query("ALTER TABLE quiz_category ADD COLUMN created_by INT NOT NULL DEFAULT 1 AFTER cat_descrip");
}

$categories = array();
$cat_where = '1=1';
if (intval($_SESSION['login_user_type']) === 2) {
    // Profesor: mostrar sus categorías + las creadas por admin
    $cat_where = '(qc.created_by = ' . intval($_SESSION['login_id']) . ' OR qc.created_by IN (SELECT id FROM users WHERE user_type = 1))';
}
$cat_qry = $conn->query("SELECT qc.id, qc.cat_name FROM quiz_category qc WHERE qc.state = 1 AND {$cat_where} ORDER BY qc.cat_name ASC");
if ($cat_qry && $cat_qry->num_rows > 0) {
    while ($cat = $cat_qry->fetch_assoc()) {
        $categories[] = $cat;
    }
}

$where_owner = '';
if (intval($_SESSION['login_user_type']) === 2) {
    $where_owner = ' WHERE e.created_by = ' . intval($_SESSION['login_id']) . ' ';
}

$eval_qry = $conn->query("SELECT e.*, u.name AS creator_name, (SELECT d.value_type FROM evaluation_detail d WHERE d.evaluation_id = e.id ORDER BY d.id ASC LIMIT 1) AS eval_type FROM evaluation_list e LEFT JOIN users u ON e.created_by = u.id " . $where_owner . " ORDER BY e.id DESC");

include('header_adminlte.php');
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex flex-wrap align-items-center gap-2">
                <h3 class="card-title">Evaluaciones Registradas</h3>
                <div class="card-tools">
                    <button class="btn btn-primary btn-sm" id="new_evaluation" type="button">
                        <i class="fa fa-plus"></i> Nueva Evaluación
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="dt-mobile-scroll">
                    <table class="table table-hover table-striped align-middle" id="table_evaluaciones">
                        <thead>
                            <tr>
                                <th style="width: 6%; text-align: center;">#</th>
                                <th style="width: 24%;">Nombre</th>
                                <th style="width: 24%;">Descripción</th>
                                <th style="width: 12%; text-align: center;">Total Preguntas</th>
                                <th style="width: 10%; text-align: center;">Opciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $i = 1;
                            if ($eval_qry && $eval_qry->num_rows > 0) {
                                while ($row = $eval_qry->fetch_assoc()) {
                                    $rules_count = $conn->query("SELECT COUNT(id) AS total FROM evaluation_detail WHERE evaluation_id = " . intval($row['id']))->fetch_assoc();
                                    $n_rules = isset($rules_count['total']) ? intval($rules_count['total']) : 0;
                                    ?>
                                    <tr>
                                        <td style="text-align: center;"><strong><?php echo $i++; ?></strong></td>
                                        <td><?php echo htmlspecialchars($row['eval_name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['eval_description']); ?></td>
                                        <td style="text-align: center;"><span
                                                class="badge bg-primary"><?php echo intval($row['total_questions']); ?></span>
                                        </td>
                                        <td style="text-align: center;">
                                            <div class="dropdown d-inline-block text-start">
                                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button"
                                                    data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fa fa-cog"></i> Opciones
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <button type="button" class="dropdown-item edit_evaluation"
                                                            data-id="<?php echo intval($row['id']); ?>">
                                                            <i class="fa fa-edit me-2"></i> Editar
                                                        </button>
                                                    </li>
                                                    <li>
                                                        <hr class="dropdown-divider">
                                                    </li>
                                                    <li>
                                                        <button type="button"
                                                            class="dropdown-item text-danger remove_evaluation"
                                                            data-id="<?php echo intval($row['id']); ?>">
                                                            <i class="fa fa-trash me-2"></i> Eliminar
                                                        </button>
                                                    </li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php
                                }
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="manage_evaluation" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nueva Evaluación</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="evaluation-frm">
                <div class="modal-body">
                    <div id="evaluation_msg" class="mb-2"></div>
                    <input type="hidden" name="id" id="evaluation_id" value="">

                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label for="eval_name">Nombre de la evaluación</label>
                            <input type="text" name="eval_name" id="eval_name" class="form-control"
                                placeholder="Ej: Test de habilidades" required>
                        </div>
                        <?php if ($_SESSION['login_user_type'] == 1): ?>
                            <div class="col-md-3 mb-3">
                                <label for="eval_professor_id">Profesor</label>
                                <select name="created_by" id="eval_professor_id" class="form-select" required>
                                    <option value="">-- Selecciona un profesor --</option>
                                    <?php
                                    $prof_qry = $conn->query('SELECT id, name FROM users WHERE user_type = 2 ORDER BY name ASC');
                                    if ($prof_qry && $prof_qry->num_rows > 0) {
                                        while ($prof = $prof_qry->fetch_assoc()) {
                                            echo '<option value="' . intval($prof['id']) . '">' . htmlspecialchars($prof['name']) . '</option>';
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                        <?php else: ?>
                            <input type="hidden" name="created_by" id="eval_professor_id"
                                value="<?php echo intval($_SESSION['login_id']); ?>">
                        <?php endif; ?>
                        <div class="col-md-3 mb-3">
                            <label for="total_questions">Cantidad total de preguntas</label>
                            <input type="number" name="total_questions" id="total_questions" min="1"
                                class="form-control" value="1" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="evaluation_value_type">Tipo de distribución</label>
                            <select name="value_type" id="evaluation_value_type" class="form-select">
                                <option value="cantidad">Cantidad</option>
                                <option value="porcentaje">Porcentaje</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="eval_description">Descripción de la evaluación</label>
                        <textarea name="eval_description" id="eval_description" rows="2" class="form-control"
                            placeholder="Detalle de propósito y alcance"></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="evaluation_randomize_options">Orden de respuestas</label>
                        <select name="randomize_options" id="evaluation_randomize_options" class="form-select">
                            <option value="0">Orden ingresado</option>
                            <option value="1">Aleatorias</option>
                        </select>
                    </div>

                    <div class="card border">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <strong>Configuración de Preguntas por Categoría</strong>
                            <button type="button" class="btn btn-outline-primary btn-sm" id="add_rule_row">
                                <i class="fa fa-plus"></i> Agregar Registro
                            </button>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered mb-0" id="evaluation_rules_table">
                                    <thead>
                                        <tr>
                                            <th style="width: 5%; text-align:center;">#</th>
                                            <th style="width: 48%;">Categoría Cuestionario</th>
                                            <th style="width: 22%;">Valor</th>
                                            <th style="width: 15%; text-align:center;">Preguntas</th>
                                            <th style="width: 10%; text-align:center;">Acción</th>
                                        </tr>
                                    </thead>
                                    <tbody id="rules_body"></tbody>
                                </table>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="d-block">Resumen</label>
                                <div class="d-flex gap-2">
                                    <span class="badge bg-secondary" id="assigned_questions_badge">Asignadas: 0</span>
                                    <span class="badge bg-primary" id="remaining_questions_badge">Faltantes: 0</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="save_evaluation_btn">
                        <i class="fa fa-save"></i> Guardar Evaluación
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function initEvaluacionPage() {
        if (!window.jQuery) {
            setTimeout(initEvaluacionPage, 80);
            return;
        }

        var $ = window.jQuery;
        var categoryOptionsHtml = '<option value="">Seleccione categoría</option>' +
            '<?php foreach ($categories as $cat): ?><option value="<?php echo intval($cat['id']); ?>"><?php echo htmlspecialchars($cat['cat_name']); ?></option><?php endforeach; ?>';

        if ($.fn.dataTable && $.fn.dataTable.isDataTable('#table_evaluaciones')) {
            $('#table_evaluaciones').DataTable().destroy();
        }

        if ($.fn.dataTable) {
            $('#table_evaluaciones').DataTable({
                paging: true,
                lengthChange: true,
                searching: true,
                ordering: true,
                info: true,
                autoWidth: false,
                responsive: false,
                order: [[0, 'asc']],
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.10.21/i18n/Spanish.json'
                }
            });
        }

        function recalcRows() {
            var total = parseInt($('#total_questions').val(), 10);
            var evalType = $('#evaluation_value_type').val();
            if (isNaN(total) || total < 0) {
                total = 0;
            }

            var assigned = 0;

            $('#rules_body tr').each(function () {
                var $tr = $(this);
                var value = parseFloat($tr.find('.rule-value').val());
                if (isNaN(value) || value < 0) {
                    value = 0;
                }

                if (evalType === 'porcentaje' && value > 100) {
                    value = 100;
                    $tr.find('.rule-value').val(100);
                }

                var questions = 0;
                if (evalType === 'porcentaje') {
                    questions = Math.round((total * value) / 100);
                } else {
                    questions = Math.round(value);
                }

                if (questions < 0) {
                    questions = 0;
                }

                $tr.find('.rule-questions').text(questions);
                assigned += questions;
            });

            var remaining = total - assigned;
            $('#assigned_questions_badge').text('Asignadas: ' + assigned);
            $('#remaining_questions_badge').text('Faltantes: ' + remaining);

            if (remaining === 0) {
                $('#remaining_questions_badge').removeClass('bg-primary bg-danger').addClass('bg-success');
            } else {
                $('#remaining_questions_badge').removeClass('bg-primary bg-success').addClass('bg-danger');
            }
        }

        function renumberRows() {
            $('#rules_body tr').each(function (index) {
                $(this).find('.rule-index').text(index + 1);
            });
        }

        function addRuleRow(ruleData) {
            var evalType = $('#evaluation_value_type').val();
            var inputMax = evalType === 'porcentaje' ? '100' : '';
            var selectedCategory = ruleData && ruleData.quiz_cat_id ? String(ruleData.quiz_cat_id) : '';
            var selectedValue = ruleData && ruleData.value_num ? String(ruleData.value_num) : '1';
            var rowHtml = '' +
                '<tr>' +
                '  <td class="rule-index" style="text-align:center;"></td>' +
                '  <td><select class="form-select form-select-sm rule-category">' + categoryOptionsHtml + '</select></td>' +
                '  <td><input type="number" min="1" max="' + inputMax + '" step="0.01" class="form-control form-control-sm rule-value" value="' + selectedValue + '"></td>' +
                '  <td style="text-align:center;"><span class="badge bg-info rule-questions">0</span></td>' +
                '  <td style="text-align:center;">' +
                '    <button type="button" class="btn btn-sm btn-outline-danger remove-rule"><i class="fa fa-trash"></i></button>' +
                '  </td>' +
                '</tr>';

            $('#rules_body').append(rowHtml);
            $('#rules_body tr:last .rule-category').val(selectedCategory);
            renumberRows();
            recalcRows();
        }

        function resetForm() {
            $('#evaluation-frm').get(0).reset();
            $('#evaluation_id').val('');
            $('#evaluation_value_type').val('cantidad');
            $('#evaluation_randomize_options').val('1');
            $('#rules_body').html('');
            $('#evaluation_msg').html('');
            $('#manage_evaluation .modal-title').text('Nueva Evaluación');
            $('#save_evaluation_btn').html('<i class="fa fa-save"></i> Guardar Evaluación');
            $('#remaining_questions_badge').removeClass('bg-danger bg-success').addClass('bg-primary').text('Faltantes: 0');
            addRuleRow();
        }

        function syncRuleInputsWithType() {
            var evalType = $('#evaluation_value_type').val();
            $('#rules_body .rule-value').each(function () {
                if (evalType === 'porcentaje') {
                    $(this).attr('max', '100');
                } else {
                    $(this).removeAttr('max');
                }
            });
        }

        $(document).off('click', '#new_evaluation').on('click', '#new_evaluation', function () {
            resetForm();
            var modalEl = document.getElementById('manage_evaluation');
            if (window.bootstrap && bootstrap.Modal && modalEl) {
                bootstrap.Modal.getOrCreateInstance(modalEl).show();
            } else if ($.fn.modal) {
                $('#manage_evaluation').modal('show');
            }
        });

        $(document).off('click', '.edit_evaluation').on('click', '.edit_evaluation', function () {
            var id = $(this).attr('data-id');
            $('#evaluation_msg').html('');

            $.ajax({
                url: './get_evaluacion.php',
                method: 'GET',
                data: { id: id },
                success: function (resp) {
                    var json = null;
                    try {
                        json = typeof resp === 'object' ? resp : JSON.parse(resp);
                    } catch (e) {
                        json = null;
                    }

                    if (!json) {
                        alert('Respuesta inválida del servidor.');
                        return;
                    }

                    if (json.status != 1) {
                        alert(json.msg || 'No se pudo cargar la evaluación.');
                        return;
                    }

                    // Load all fields from database with proper defaults
                    var evalId = parseInt(json.id, 10);
                    var evalName = String(json.eval_name || '');
                    var evalDescription = String(json.eval_description || '');
                    var totalQuestions = parseInt(json.total_questions, 10) || 1;
                    var valueType = String(json.value_type || 'cantidad');
                    // Handle randomizeOptions carefully - 0 is valid, not falsy
                    var randomizeOptions = json.randomize_options !== undefined ? String(json.randomize_options) : '1';
                    var createdBy = parseInt(json.created_by, 10) || 0;

                    // Validate loaded values
                    if (isNaN(evalId) || evalId < 1) {
                        alert('ID de evaluación inválido.');
                        return;
                    }
                    if (isNaN(totalQuestions) || totalQuestions < 1) {
                        totalQuestions = 1;
                    }
                    if (valueType !== 'porcentaje' && valueType !== 'cantidad') {
                        valueType = 'cantidad';
                    }

                    // Assign all values to form fields
                    $('#evaluation_id').val(evalId);
                    $('#eval_name').val(evalName);
                    $('#eval_description').val(evalDescription);
                    $('#total_questions').val(totalQuestions);
                    $('#evaluation_value_type').val(valueType);
                    $('#evaluation_randomize_options').val(randomizeOptions);
                    
                    // Set professor field if it exists
                    if ($('#eval_professor_id').length > 0) {
                        $('#eval_professor_id').val(createdBy || '');
                    }

                    // Load rules
                    $('#rules_body').html('');
                    if (json.rules && Array.isArray(json.rules) && json.rules.length > 0) {
                        json.rules.forEach(function (item) {
                            addRuleRow(item);
                        });
                    } else {
                        addRuleRow();
                    }

                    // Update modal title and button text
                    $('#manage_evaluation .modal-title').text('Editar Evaluación');
                    $('#save_evaluation_btn').html('<i class="fa fa-save"></i> Actualizar Evaluación');
                    
                    // Recalculate and sync
                    syncRuleInputsWithType();
                    recalcRows();

                    // Show modal
                    var modalEl = document.getElementById('manage_evaluation');
                    if (window.bootstrap && bootstrap.Modal && modalEl) {
                        bootstrap.Modal.getOrCreateInstance(modalEl).show();
                    } else if ($.fn.modal) {
                        $('#manage_evaluation').modal('show');
                    }
                },
                error: function (xhr, status, error) {
                    console.error('Error al cargar evaluación:', error);
                    alert('Error al cargar la evaluación.');
                }
            });
        });

        $(document).off('click', '#add_rule_row').on('click', '#add_rule_row', function () {
            addRuleRow();
        });

        $(document).off('click', '.remove-rule').on('click', '.remove-rule', function () {
            $(this).closest('tr').remove();
            renumberRows();
            recalcRows();
        });

        $(document).off('change keyup', '.rule-value, #total_questions, #evaluation_value_type').on('change keyup', '.rule-value, #total_questions, #evaluation_value_type', function () {
            syncRuleInputsWithType();
            recalcRows();
        });

        $('#evaluation-frm').off('submit').on('submit', function (e) {
            e.preventDefault();

            var total = parseInt($('#total_questions').val(), 10);
            if (isNaN(total) || total < 1) {
                $('#evaluation_msg').html('<div class="alert alert-danger py-2 mb-0">Ingrese una cantidad total de preguntas válida.</div>');
                return;
            }

            var rules = [];
            var hasError = false;
            var evalType = $('#evaluation_value_type').val();

            if (evalType !== 'porcentaje' && evalType !== 'cantidad') {
                $('#evaluation_msg').html('<div class="alert alert-danger py-2 mb-0">Seleccione un tipo de distribución válido.</div>');
                return;
            }

            $('#rules_body tr').each(function () {
                var catId = parseInt($(this).find('.rule-category').val(), 10);
                var value = parseFloat($(this).find('.rule-value').val());

                if (isNaN(catId) || catId < 1 || isNaN(value) || value <= 0) {
                    hasError = true;
                    return false;
                }

                if (evalType === 'porcentaje' && value > 100) {
                    hasError = true;
                    return false;
                }

                rules.push({
                    quiz_cat_id: catId,
                    value_num: value
                });
            });

            if (hasError || rules.length === 0) {
                $('#evaluation_msg').html('<div class="alert alert-danger py-2 mb-0">Complete correctamente todas las filas de configuración.</div>');
                return;
            }

            recalcRows();
            var assigned = 0;
            $('#rules_body .rule-questions').each(function () {
                assigned += parseInt($(this).text(), 10) || 0;
            });

            if (assigned !== total) {
                $('#evaluation_msg').html('<div class="alert alert-danger py-2 mb-0">La suma de preguntas configuradas (' + assigned + ') debe ser igual al total (' + total + ').</div>');
                return;
            }

            var $btn = $('#save_evaluation_btn');
            $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Guardando...');
            $('#evaluation_msg').html('');

            $.ajax({
                url: './save_evaluacion.php',
                method: 'POST',
                data: {
                    id: $('#evaluation_id').val(),
                    eval_name: $('#eval_name').val(),
                    eval_description: $('#eval_description').val(),
                    total_questions: total,
                    value_type: evalType,
                    randomize_options: parseInt($('#evaluation_randomize_options').val(), 10),
                    created_by: $('#eval_professor_id').val(),
                    rules_json: JSON.stringify(rules)
                },
                success: function (resp) {
                    var json = null;
                    try {
                        json = typeof resp === 'object' ? resp : JSON.parse(resp);
                    } catch (e) {
                        json = null;
                    }

                    if (!json) {
                        $('#evaluation_msg').html('<div class="alert alert-danger py-2 mb-0">Respuesta inválida del servidor.</div>');
                        $btn.prop('disabled', false).html('<i class="fa fa-save"></i> Guardar Evaluación');
                        return;
                    }

                    if (json.status == 1) {
                        $('#evaluation_msg').html('<div class="alert alert-success py-2 mb-0">' + (json.msg || 'Evaluación guardada.') + '</div>');
                        setTimeout(function () {
                            location.reload();
                        }, 600);
                    } else {
                        $('#evaluation_msg').html('<div class="alert alert-danger py-2 mb-0">' + (json.msg || 'No se pudo guardar la evaluación.') + '</div>');
                        $btn.prop('disabled', false).html('<i class="fa fa-save"></i> Guardar Evaluación');
                    }
                },
                error: function () {
                    $('#evaluation_msg').html('<div class="alert alert-danger py-2 mb-0">Error al guardar la evaluación.</div>');
                    $btn.prop('disabled', false).html('<i class="fa fa-save"></i> Guardar Evaluación');
                }
            });
        });

        $(document).off('click', '.remove_evaluation').on('click', '.remove_evaluation', function () {
            var id = $(this).attr('data-id');
            if (!confirm('¿Está seguro de eliminar esta evaluación y su configuración?')) {
                return;
            }

            $.ajax({
                url: './delete_evaluacion.php',
                method: 'POST',
                data: { id: id },
                success: function (resp) {
                    var json = null;
                    try {
                        json = typeof resp === 'object' ? resp : JSON.parse(resp);
                    } catch (e) {
                        json = null;
                    }

                    if (!json) {
                        alert('Respuesta inválida del servidor.');
                        return;
                    }

                    if (json.status == 1) {
                        alert(json.msg || 'Evaluación eliminada.');
                        location.reload();
                    } else {
                        alert(json.msg || 'No se pudo eliminar la evaluación.');
                    }
                },
                error: function () {
                    alert('Error al eliminar la evaluación.');
                }
            });
        });

        if ($('#rules_body tr').length === 0) {
            addRuleRow();
        }
        syncRuleInputsWithType();
        recalcRows();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initEvaluacionPage);
    } else {
        initEvaluacionPage();
    }
</script>

<?php include('footer_adminlte.php'); ?>