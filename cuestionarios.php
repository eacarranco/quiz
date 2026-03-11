<?php
include('auth.php');
include('db_connect.php');
$title = 'Gestión de Cuestionarios';
include('header_adminlte.php');
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex flex-wrap align-items-center gap-2">
                <h3 class="card-title">Listado de Cuestionarios</h3>
                <?php if ($_SESSION['login_user_type'] != 3): ?>
                <div class="card-tools">
                    <button class="btn btn-primary btn-sm" id="new_quiz" type="button">
                        <i class="fa fa-plus"></i> Agregar Cuestionario
                    </button>
                </div>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <div class="dt-mobile-scroll">
                    <table class="table table-hover table-striped align-middle" id="table_cuestionarios">
                        <thead>
                            <tr>
                                <th style="width: 5%; text-align: center;">#</th>
                                <th style="width: 22%;">Título</th>
                                <th style="width: 20%; text-align: center;">Categoría</th>
                                <th style="width: 16%; text-align: center;">Orden de respuestas</th>
                                <th style="width: 12%; text-align: center;">Preguntas</th>
                                <?php if ($_SESSION['login_user_type'] != 3): ?>
                                <th style="width: 15%; text-align: center;">Opciones</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $where = '';
                            if ($_SESSION['login_user_type'] == 2) {
                                $where = ' WHERE q.user_id = ' . intval($_SESSION['login_id']) . ' ';
                            } elseif ($_SESSION['login_user_type'] == 3) {
                                $where = ' WHERE q.id IN (SELECT quiz_id FROM quiz_student_list WHERE user_id = ' . intval($_SESSION['login_id']) . ') ';
                            }

                            $qry = $conn->query('SELECT q.*, u.name AS teacher_name, qc.cat_name AS category_name FROM quiz_list q LEFT JOIN users u ON q.user_id = u.id LEFT JOIN quiz_category qc ON q.quiz_cat_id = qc.id ' . $where . ' ORDER BY q.title ASC');
                            $i = 1;

                            if ($qry && $qry->num_rows > 0) {
                                while ($row = $qry->fetch_assoc()) {
                                    $items = $conn->query("SELECT COUNT(id) AS item_count FROM questions WHERE qid = '" . intval($row['id']) . "'")->fetch_assoc();
                                    $count = isset($items['item_count']) ? intval($items['item_count']) : 0;
                                    ?>
                                    <tr>
                                        <td style="text-align: center;"><strong><?php echo $i++; ?></strong></td>
                                        <td><?php echo htmlspecialchars($row['title']); ?></td>
                                        <td><?php echo htmlspecialchars($row['category_name'] ? $row['category_name'] : 'Sin categoría'); ?></td>
                                        <td style="text-align: center;">
                                            <?php if (intval($row['randomize_options']) === 1): ?>
                                                <span class="badge bg-success">Aleatorias</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Orden ingresado</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="text-align: center;"><span class="badge bg-info"><?php echo $count; ?></span></td>
                                        <?php if ($_SESSION['login_user_type'] != 3): ?>
                                        <td style="text-align: center;">
                                            <div class="dropdown d-inline-block text-start">
                                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fa fa-cog"></i> Opciones
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a class="dropdown-item" href="quiz_view.php?id=<?php echo intval($row['id']); ?>">
                                                            <i class="fa fa-list me-2"></i> Preguntas
                                                        </a>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <button type="button" class="dropdown-item edit_quiz" data-id="<?php echo intval($row['id']); ?>">
                                                            <i class="fa fa-edit me-2"></i> Editar
                                                        </button>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <button type="button" class="dropdown-item text-danger remove_quiz" data-id="<?php echo intval($row['id']); ?>">
                                                            <i class="fa fa-trash me-2"></i> Eliminar
                                                        </button>
                                                    </li>
                                                </ul>
                                            </div>
                                        </td>
                                        <?php endif; ?>
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

<?php if ($_SESSION['login_user_type'] != 3): ?>
<div class="modal fade" id="manage_quiz" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Agregar Cuestionario</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="quiz-frm">
                <div class="modal-body">
                    <div id="quiz_msg" class="mb-2"></div>
                    <input type="hidden" name="id" id="quiz_id" value="">
                    <?php
                    $quiz_categories = array();
                    $cat_where = '1=1';
                    if ($_SESSION['login_user_type'] == 2) {
                        // Profesor: mostrar sus categorías + las creadas por admin
                        $cat_where = '(qc.created_by = ' . intval($_SESSION['login_id']) . ' OR qc.created_by IN (SELECT id FROM users WHERE user_type = 1))';
                    }
                    $cat_qry = $conn->query("SELECT qc.id, qc.cat_name FROM quiz_category qc WHERE qc.state = 1 AND {$cat_where} ORDER BY qc.cat_name ASC");
                    if ($cat_qry && $cat_qry->num_rows > 0) {
                        while ($cat_row = $cat_qry->fetch_assoc()) {
                            $quiz_categories[] = $cat_row;
                        }
                    }
                    ?>
                    <div class="form-group mb-3">
                        <label for="quiz_title">Título</label>
                        <input type="text" name="title" id="quiz_title" class="form-control" placeholder="Ingrese título del cuestionario">
                    </div>
                    <?php
                    $quiz_users = array();
                    $usr_qry = $conn->query("SELECT id, name FROM users WHERE user_type = 2 ORDER BY name ASC");
                    if ($usr_qry && $usr_qry->num_rows > 0) {
                        while ($usr_row = $usr_qry->fetch_assoc()) {
                            $quiz_users[] = $usr_row;
                        }
                    }
                    $default_quiz_user_id = intval($_SESSION['login_id']);
                    ?>
                    <div class="form-group mb-3">
                        <label for="quiz_user_id">Profesor</label>
                        <select name="user_id" id="quiz_user_id" class="form-select" data-default="<?php echo $default_quiz_user_id; ?>">
                            <option value="">Seleccione profesor</option>
                            <?php foreach ($quiz_users as $usr): ?>
                                <option value="<?php echo intval($usr['id']); ?>" <?php echo intval($usr['id']) === $default_quiz_user_id ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($usr['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group mb-3">
                        <label for="quiz_cat_id">Categoría</label>
                        <div class="d-flex gap-2">
                            <select name="quiz_cat_id" id="quiz_cat_id" class="form-select">
                                <option value="">Seleccione categoría</option>
                                <?php foreach ($quiz_categories as $cat): ?>
                                    <option value="<?php echo intval($cat['id']); ?>"><?php echo htmlspecialchars($cat['cat_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="button" class="btn btn-outline-primary" id="btn_show_new_category">
                                <i class="fa fa-plus"></i>
                            </button>
                        </div>
                        <?php if (count($quiz_categories) === 0): ?>
                            <small class="text-danger d-block mt-2">No hay categorías para cuestionarios. Agrega una nueva.</small>
                        <?php endif; ?>
                    </div>
                    <div class="card border" id="new_category_box" style="display:none;">
                        <div class="card-body p-3">
                            <h6 class="mb-3">Nueva categoría</h6>
                            <div id="new_category_msg" class="mb-2"></div>
                            <div class="form-group mb-2">
                                <label for="new_quiz_category_name">Nombre</label>
                                <input type="text" id="new_quiz_category_name" class="form-control" placeholder="Nombre de categoría">
                            </div>
                            <div class="form-group mb-3">
                                <label for="new_quiz_category_desc">Descripción</label>
                                <input type="text" id="new_quiz_category_desc" class="form-control" placeholder="Descripción de categoría">
                            </div>
                            <div class="d-flex justify-content-end gap-2">
                                <button type="button" class="btn btn-light" id="btn_cancel_new_category">Cancelar</button>
                                <button type="button" class="btn btn-primary" id="btn_save_new_category">
                                    <i class="fa fa-save"></i> Guardar categoría
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="form-group mb-3">
                        <label for="quiz_qpoints">Puntos por Pregunta</label>
                        <input type="number" name="qpoints" id="quiz_qpoints" class="form-control" min="1" value="1">
                    </div>
                    <div class="form-group mb-0">
                        <label class="d-block">Respuestas Aleatorias</label>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="randomize_options" id="quiz_randomize_no" value="0" checked>
                            <label class="form-check-label" for="quiz_randomize_no">No</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="randomize_options" id="quiz_randomize_yes" value="1">
                            <label class="form-check-label" for="quiz_randomize_yes">Sí</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="btn_save_quiz" name="save_quiz">
                        <i class="fa fa-save"></i> Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
function initCuestionariosPage() {
    if (!window.jQuery) {
        setTimeout(initCuestionariosPage, 80);
        return;
    }

    var $ = window.jQuery;

    if ($.fn.dataTable && $.fn.dataTable.isDataTable('#table_cuestionarios')) {
        $('#table_cuestionarios').DataTable().destroy();
    }

    if ($.fn.dataTable) {
        var dtCuestionarios = $('#table_cuestionarios').DataTable({
            paging: true,
            lengthChange: true,
            searching: true,
            ordering: true,
            info: true,
            autoWidth: false,
            responsive: false,
            order: [[1, 'asc']],
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.10.21/i18n/Spanish.json'
            }
        });

        // Recalcula la columna # en cada draw para mantener orden correlativo.
        dtCuestionarios.on('draw.dt', function () {
            var pageInfo = dtCuestionarios.page.info();
            dtCuestionarios.column(0, { page: 'current' }).nodes().each(function (cell, i) {
                cell.innerHTML = '<strong>' + (pageInfo.start + i + 1) + '</strong>';
            });
        });

        dtCuestionarios.draw(false);
    }

    $(document).off('click', '#new_quiz').on('click', '#new_quiz', function () {
        $('#manage_quiz .modal-title').html('Agregar Cuestionario');
        $('#btn_save_quiz').html('<i class="fa fa-save"></i> Guardar');
        if ($('#quiz-frm').length) {
            $('#quiz-frm').get(0).reset();
        }
        $('#quiz_id').val('');
        $('#quiz_msg').html('');
        if ($('#quiz_user_id').length) {
            $('#quiz_user_id').val(String($('#quiz_user_id').data('default') || ''));
        }
        $('#new_category_box').hide();
        $('#new_category_msg').html('');
        $('#new_quiz_category_name').val('');
        $('#new_quiz_category_desc').val('');

        var modalEl = document.getElementById('manage_quiz');
        if (window.bootstrap && bootstrap.Modal && modalEl) {
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        } else if ($.fn.modal) {
            $('#manage_quiz').modal('show');
        }
    });

    $(document).off('click', '.edit_quiz').on('click', '.edit_quiz', function () {
        var id = $(this).attr('data-id');
        $('#quiz_msg').html('');

        $.ajax({
            url: './get_quiz.php',
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
                    alert(json.msg || 'No se pudo cargar el cuestionario.');
                    return;
                }

                $('#manage_quiz .modal-title').html('Editar Cuestionario');
                $('#btn_save_quiz').html('<i class="fa fa-save"></i> Actualizar');
                $('#quiz_id').val(json.id || '');
                $('#quiz_title').val(json.title || '');
                $('#quiz_qpoints').val(json.qpoints || 1);
                $('#quiz_user_id').val(String(json.user_id || ''));
                $('#quiz_cat_id').val(String(json.quiz_cat_id || ''));

                if (String(json.randomize_options) === '1') {
                    $('#quiz_randomize_yes').prop('checked', true);
                } else {
                    $('#quiz_randomize_no').prop('checked', true);
                }

                $('#new_category_box').hide();
                $('#new_category_msg').html('');

                var modalEl = document.getElementById('manage_quiz');
                if (window.bootstrap && bootstrap.Modal && modalEl) {
                    bootstrap.Modal.getOrCreateInstance(modalEl).show();
                } else if ($.fn.modal) {
                    $('#manage_quiz').modal('show');
                }
            },
            error: function () {
                alert('Error al obtener datos del cuestionario.');
            }
        });
    });

    $(document).off('click', '#btn_show_new_category').on('click', '#btn_show_new_category', function () {
        $('#new_category_msg').html('');
        $('#new_category_box').slideDown(120);
        $('#new_quiz_category_name').focus();
    });

    $(document).off('click', '#btn_cancel_new_category').on('click', '#btn_cancel_new_category', function () {
        $('#new_category_box').slideUp(120);
        $('#new_category_msg').html('');
        $('#new_quiz_category_name').val('');
        $('#new_quiz_category_desc').val('');
    });

    $(document).off('click', '#btn_save_new_category').on('click', '#btn_save_new_category', function () {
        var $btn = $(this);
        var catName = $('#new_quiz_category_name').val();
        var catDesc = $('#new_quiz_category_desc').val();

        $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Guardando...');
        $('#new_category_msg').html('');

        $.ajax({
            url: './save_quiz_category.php',
            method: 'POST',
            data: {
                cat_name: catName,
                cat_descrip: catDesc
            },
            success: function (resp) {
                var json = null;
                try {
                    json = typeof resp === 'object' ? resp : JSON.parse(resp);
                } catch (e) {
                    json = null;
                }

                if (!json) {
                    $('#new_category_msg').html('<div class="alert alert-danger py-2 mb-0">Respuesta inválida del servidor.</div>');
                    $btn.prop('disabled', false).html('<i class="fa fa-save"></i> Guardar categoría');
                    return;
                }

                if (json.status == 1) {
                    $('#quiz_cat_id').append('<option value="' + json.id + '">' + json.cat_name + '</option>');
                    $('#quiz_cat_id').val(String(json.id));
                    $('#new_category_msg').html('<div class="alert alert-success py-2 mb-0">Categoría creada.</div>');
                    $('#new_quiz_category_name').val('');
                    $('#new_quiz_category_desc').val('');
                    setTimeout(function () {
                        $('#new_category_box').slideUp(120);
                        $('#new_category_msg').html('');
                    }, 700);
                } else {
                    $('#new_category_msg').html('<div class="alert alert-danger py-2 mb-0">' + (json.msg || 'No se pudo crear la categoría.') + '</div>');
                }

                $btn.prop('disabled', false).html('<i class="fa fa-save"></i> Guardar categoría');
            },
            error: function () {
                $('#new_category_msg').html('<div class="alert alert-danger py-2 mb-0">Error al guardar categoría.</div>');
                $btn.prop('disabled', false).html('<i class="fa fa-save"></i> Guardar categoría');
            }
        });
    });

    $('#quiz-frm').off('submit').on('submit', function (e) {
        e.preventDefault();

        var $btn = $('#btn_save_quiz');
        $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Guardando...');
        $('#quiz_msg').html('');

        $.ajax({
            url: './save_quiz.php',
            method: 'POST',
            data: $(this).serialize(),
            success: function (resp) {
                var json = null;
                try {
                    json = typeof resp === 'object' ? resp : JSON.parse(resp);
                } catch (e) {
                    json = null;
                }

                if (!json) {
                    $('#quiz_msg').html('<div class="alert alert-danger py-2 mb-0">Respuesta inválida del servidor.</div>');
                    $btn.prop('disabled', false).html('<i class="fa fa-save"></i> Guardar');
                    return;
                }

                if (json.status == 1) {
                    $('#quiz_msg').html('<div class="alert alert-success py-2 mb-0">' + (json.msg || 'Cuestionario guardado.') + '</div>');
                    setTimeout(function () {
                        location.reload();
                    }, 500);
                } else {
                    $('#quiz_msg').html('<div class="alert alert-danger py-2 mb-0">' + (json.msg || 'No se pudo guardar el cuestionario.') + '</div>');
                    $btn.prop('disabled', false).html('<i class="fa fa-save"></i> Guardar');
                }
            },
            error: function () {
                $('#quiz_msg').html('<div class="alert alert-danger py-2 mb-0">Error al guardar el cuestionario.</div>');
                $btn.prop('disabled', false).html('<i class="fa fa-save"></i> Guardar');
            }
        });
    });

    $(document).off('click', '.remove_quiz').on('click', '.remove_quiz', function () {
        var id = $(this).attr('data-id');
        if (!confirm('¿Está seguro de eliminar este cuestionario y todos sus registros relacionados?')) {
            return;
        }

        $.ajax({
            url: './delete_quiz.php',
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
                    alert(json.msg || 'Cuestionario eliminado.');
                    location.reload();
                } else {
                    alert(json.msg || 'No se pudo eliminar el cuestionario.');
                }
            },
            error: function () {
                alert('Error al eliminar el cuestionario.');
            }
        });
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initCuestionariosPage);
} else {
    initCuestionariosPage();
}
</script>

<?php include('footer_adminlte.php'); ?>

