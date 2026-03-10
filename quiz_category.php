<?php
include('auth.php');
include('db_connect.php');

if ($_SESSION['login_user_type'] == 3) {
    header('Location: home.php');
    exit;
}

$title = 'Categorías de Cuestionario';

$conn->query("CREATE TABLE IF NOT EXISTS quiz_category (
    id INT NOT NULL AUTO_INCREMENT,
    cat_name VARCHAR(150) NOT NULL,
    cat_descrip VARCHAR(200) DEFAULT NULL,
    state BIT(1) NOT NULL DEFAULT b'1',
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$qry = $conn->query("SELECT qc.id, qc.cat_name, qc.cat_descrip, qc.state, COUNT(ql.id) AS quiz_count FROM quiz_category qc LEFT JOIN quiz_list ql ON ql.quiz_cat_id = qc.id GROUP BY qc.id, qc.cat_name, qc.cat_descrip, qc.state ORDER BY qc.cat_name ASC");

include('header_adminlte.php');
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex flex-wrap align-items-center gap-2">
                <h3 class="card-title">Listado de Categorías</h3>
                <div class="card-tools ms-auto">
                    <button class="btn btn-primary btn-sm" id="btn_new_category" type="button">
                        <i class="fa fa-plus"></i> Nueva Categoría
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div id="category_page_msg" class="mb-2"></div>
                <div class="dt-mobile-scroll">
                    <table class="table table-hover table-striped align-middle" id="table_quiz_categories">
                        <thead>
                            <tr>
                                <th style="width: 8%; text-align: center;">#</th>
                                <th style="width: 28%;">Nombre</th>
                                <th style="width: 34%;">Descripción</th>
                                <th style="width: 12%; text-align: center;">Estado</th>
                                <th style="width: 8%; text-align: center;">Cuestionarios</th>
                                <th style="width: 10%; text-align: center;">Opciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $i = 1;
                            if ($qry && $qry->num_rows > 0) {
                                while ($row = $qry->fetch_assoc()) {
                                    $is_active = intval($row['state']) > 0;
                                    ?>
                                    <tr>
                                        <td style="text-align: center;"><strong><?php echo $i++; ?></strong></td>
                                        <td><?php echo htmlspecialchars($row['cat_name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['cat_descrip'] ? $row['cat_descrip'] : '-'); ?></td>
                                        <td style="text-align: center;">
                                            <?php if ($is_active): ?>
                                                <span class="badge bg-success">Activo</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Inactivo</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="text-align: center;"><span class="badge bg-info"><?php echo intval($row['quiz_count']); ?></span></td>
                                        <td style="text-align: center;">
                                            <div class="dropdown d-inline-block text-start">
                                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fa fa-cog"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <button type="button" class="dropdown-item edit_category" data-id="<?php echo intval($row['id']); ?>">
                                                            <i class="fa fa-edit me-2"></i> Editar
                                                        </button>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <button type="button" class="dropdown-item text-danger remove_category" data-id="<?php echo intval($row['id']); ?>" data-quiz-count="<?php echo intval($row['quiz_count']); ?>">
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

<div class="modal fade" id="manage_quiz_category" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nueva Categoría</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="quiz-category-frm">
                <div class="modal-body">
                    <div id="quiz_category_msg" class="mb-2"></div>
                    <input type="hidden" name="id" id="quiz_category_id" value="">

                    <div class="form-group mb-3">
                        <label for="quiz_category_name">Nombre</label>
                        <input type="text" class="form-control" id="quiz_category_name" name="cat_name" maxlength="150" required>
                    </div>

                    <div class="form-group mb-3">
                        <label for="quiz_category_desc">Descripción</label>
                        <textarea class="form-control" id="quiz_category_desc" name="cat_descrip" rows="3" maxlength="200"></textarea>
                    </div>

                    <div class="form-group mb-0">
                        <label class="d-block">Estado</label>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="state" id="quiz_category_state_active" value="1" checked>
                            <label class="form-check-label" for="quiz_category_state_active">Activo</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="state" id="quiz_category_state_inactive" value="0">
                            <label class="form-check-label" for="quiz_category_state_inactive">Inactivo</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="btn_save_category">
                        <i class="fa fa-save"></i> Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function initQuizCategoryPage() {
    if (!window.jQuery) {
        setTimeout(initQuizCategoryPage, 80);
        return;
    }

    var $ = window.jQuery;

    if ($.fn.dataTable && $.fn.dataTable.isDataTable('#table_quiz_categories')) {
        $('#table_quiz_categories').DataTable().destroy();
    }

    if ($.fn.dataTable) {
        $('#table_quiz_categories').DataTable({
            paging: true,
            lengthChange: true,
            searching: true,
            ordering: true,
            info: true,
            autoWidth: false,
            responsive: false,
            order: [[1, 'asc']],
            language: {
                url: '//cdn.datatables.net/plug-ins/1.10.21/i18n/Spanish.json'
            }
        });
    }

    function openCategoryModal() {
        var modalEl = document.getElementById('manage_quiz_category');
        if (window.bootstrap && bootstrap.Modal && modalEl) {
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        } else if ($.fn.modal) {
            $('#manage_quiz_category').modal('show');
        }
    }

    function resetCategoryForm() {
        if ($('#quiz-category-frm').length) {
            $('#quiz-category-frm').get(0).reset();
        }
        $('#quiz_category_id').val('');
        $('#quiz_category_msg').html('');
        $('#manage_quiz_category .modal-title').text('Nueva Categoría');
        $('#btn_save_category').html('<i class="fa fa-save"></i> Guardar');
        $('#quiz_category_state_active').prop('checked', true);
    }

    $(document).off('click', '#btn_new_category').on('click', '#btn_new_category', function () {
        resetCategoryForm();
        openCategoryModal();
    });

    $(document).off('click', '.edit_category').on('click', '.edit_category', function () {
        var id = $(this).attr('data-id');
        $('#quiz_category_msg').html('');

        $.ajax({
            url: './get_quiz_category.php',
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
                    alert(json.msg || 'No se pudo obtener la categoría.');
                    return;
                }

                resetCategoryForm();
                $('#manage_quiz_category .modal-title').text('Editar Categoría');
                $('#btn_save_category').html('<i class="fa fa-save"></i> Actualizar');
                $('#quiz_category_id').val(json.id || '');
                $('#quiz_category_name').val(json.cat_name || '');
                $('#quiz_category_desc').val(json.cat_descrip || '');

                if (String(json.state) === '0') {
                    $('#quiz_category_state_inactive').prop('checked', true);
                } else {
                    $('#quiz_category_state_active').prop('checked', true);
                }

                openCategoryModal();
            },
            error: function () {
                alert('Error al obtener datos de categoría.');
            }
        });
    });

    $('#quiz-category-frm').off('submit').on('submit', function (e) {
        e.preventDefault();

        var $btn = $('#btn_save_category');
        var name = $('#quiz_category_name').val().trim();

        if (!name) {
            $('#quiz_category_msg').html('<div class="alert alert-danger py-2 mb-0">El nombre es obligatorio.</div>');
            return;
        }

        $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Guardando...');
        $('#quiz_category_msg').html('');

        $.ajax({
            url: './save_quiz_category.php',
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
                    $('#quiz_category_msg').html('<div class="alert alert-danger py-2 mb-0">Respuesta inválida del servidor.</div>');
                    $btn.prop('disabled', false).html('<i class="fa fa-save"></i> Guardar');
                    return;
                }

                if (json.status == 1) {
                    $('#quiz_category_msg').html('<div class="alert alert-success py-2 mb-0">' + (json.msg || 'Categoría guardada.') + '</div>');
                    setTimeout(function () {
                        location.reload();
                    }, 450);
                } else {
                    $('#quiz_category_msg').html('<div class="alert alert-danger py-2 mb-0">' + (json.msg || 'No se pudo guardar la categoría.') + '</div>');
                    $btn.prop('disabled', false).html('<i class="fa fa-save"></i> Guardar');
                }
            },
            error: function () {
                $('#quiz_category_msg').html('<div class="alert alert-danger py-2 mb-0">Error al guardar categoría.</div>');
                $btn.prop('disabled', false).html('<i class="fa fa-save"></i> Guardar');
            }
        });
    });

    $(document).off('click', '.remove_category').on('click', '.remove_category', function () {
        var id = $(this).attr('data-id');
        var quizCount = parseInt($(this).attr('data-quiz-count') || '0', 10);

        if (quizCount > 0) {
            alert('No se puede eliminar: hay cuestionarios asociados a esta categoría.');
            return;
        }

        if (!confirm('¿Está seguro de eliminar esta categoría?')) {
            return;
        }

        $.ajax({
            url: './delete_quiz_category.php',
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
                    $('#category_page_msg').html('<div class="alert alert-success py-2">' + (json.msg || 'Categoría eliminada.') + '</div>');
                    setTimeout(function () {
                        location.reload();
                    }, 350);
                } else {
                    alert(json.msg || 'No se pudo eliminar la categoría.');
                }
            },
            error: function () {
                alert('Error al eliminar categoría.');
            }
        });
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initQuizCategoryPage);
} else {
    initQuizCategoryPage();
}
</script>

<?php include('footer_adminlte.php'); ?>
