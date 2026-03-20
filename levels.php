<?php
include('auth.php');
include('db_connect.php');

if (intval($_SESSION['login_user_type']) !== 1) {
    header('Location: home.php');
    exit;
}

$title = 'Niveles';

$conn->query("CREATE TABLE IF NOT EXISTS levels (
    id INT NOT NULL AUTO_INCREMENT,
    level_name VARCHAR(100) NOT NULL,
    state TINYINT(1) NOT NULL DEFAULT 1,
    date_updated DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_level_name (level_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$conn->query("CREATE TABLE IF NOT EXISTS faculty_levels (
    id INT NOT NULL AUTO_INCREMENT,
    faculty_id INT NOT NULL,
    level_id INT NOT NULL,
    date_updated DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_faculty_level (faculty_id, level_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$has_level_id = $conn->query("SHOW COLUMNS FROM students LIKE 'level_id'");
if ($has_level_id && $has_level_id->num_rows === 0) {
    $conn->query("ALTER TABLE students ADD COLUMN level_id INT NULL AFTER user_id");
}

$default_chk = $conn->query("SELECT id FROM levels WHERE level_name = 'Default' LIMIT 1");
if (!$default_chk || $default_chk->num_rows === 0) {
    $conn->query("UPDATE levels SET level_name = 'Default' WHERE level_name = '1A' LIMIT 1");
}

$conn->query("INSERT IGNORE INTO levels (level_name, state) VALUES ('Default', 1)");

$default_row = $conn->query("SELECT id FROM levels WHERE level_name = 'Default' LIMIT 1");
$default_id = ($default_row && $default_row->num_rows > 0) ? intval($default_row->fetch_assoc()['id']) : 0;

if ($default_id > 0) {
    $conn->query("UPDATE students SET level_id = {$default_id}, level_section = 'Default' WHERE TRIM(level_section) = '1A'");
    $conn->query("UPDATE students SET level_id = {$default_id}, level_section = 'Default' WHERE (level_id IS NULL OR level_id = 0)");
}

$qry = $conn->query("SELECT l.id, l.level_name, l.state,
    COUNT(DISTINCT s.id) AS students_count,
    COUNT(DISTINCT fl.faculty_id) AS faculty_count
    FROM levels l
    LEFT JOIN students s ON s.level_id = l.id
    LEFT JOIN faculty_levels fl ON fl.level_id = l.id
    GROUP BY l.id, l.level_name, l.state
    ORDER BY l.level_name ASC");

include('header_adminlte.php');
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex flex-wrap align-items-center gap-2">
                <h3 class="card-title">Listado de Niveles</h3>
                <div class="card-tools ms-auto">
                    <button class="btn btn-primary btn-sm" id="btn_new_level" type="button">
                        <i class="fa fa-plus"></i> Nuevo Nivel
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div id="level_page_msg" class="mb-2"></div>
                <div class="dt-mobile-scroll">
                    <table class="table table-hover table-striped align-middle" id="table_levels">
                        <thead>
                            <tr>
                                <th style="width: 8%; text-align: center;">#</th>
                                <th style="width: 34%;">Nivel</th>
                                <th style="width: 14%; text-align: center;">Estudiantes</th>
                                <th style="width: 14%; text-align: center;">Profesores</th>
                                <th style="width: 16%; text-align: center;">Estado</th>
                                <th style="width: 14%; text-align: center;">Opciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $i = 1;
                            if ($qry && $qry->num_rows > 0) {
                                while ($row = $qry->fetch_assoc()) {
                                    $is_active = intval($row['state']) === 1;
                                    ?>
                                    <tr>
                                        <td style="text-align: center;"><strong><?php echo $i++; ?></strong></td>
                                        <td><?php echo htmlspecialchars($row['level_name']); ?></td>
                                        <td style="text-align: center;"><span class="badge bg-info"><?php echo intval($row['students_count']); ?></span></td>
                                        <td style="text-align: center;"><span class="badge bg-primary"><?php echo intval($row['faculty_count']); ?></span></td>
                                        <td style="text-align: center;">
                                            <?php if ($is_active): ?>
                                                <span class="badge bg-success">Activo</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Inactivo</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="text-align: center;">
                                            <div class="dropdown d-inline-block text-start">
                                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fa fa-cog"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <button type="button" class="dropdown-item edit_level" data-id="<?php echo intval($row['id']); ?>">
                                                            <i class="fa fa-edit me-2"></i> Editar
                                                        </button>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <button type="button" class="dropdown-item text-danger remove_level" data-id="<?php echo intval($row['id']); ?>">
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

<div class="modal fade modal-fullscreen-sm-down" id="manage_level" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nuevo Nivel</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="level-frm">
                <div class="modal-body modal-body-scroll">
                    <div id="level_msg" class="mb-2"></div>
                    <input type="hidden" name="id" id="level_id" value="">

                    <div class="form-group mb-3">
                        <label for="level_name">Nombre del Nivel</label>
                        <input type="text" class="form-control" id="level_name" name="level_name" maxlength="100" required>
                    </div>

                    <div class="form-group mb-0">
                        <label class="d-block">Estado</label>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="state" id="level_state_active" value="1" checked>
                            <label class="form-check-label" for="level_state_active">Activo</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="state" id="level_state_inactive" value="0">
                            <label class="form-check-label" for="level_state_inactive">Inactivo</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="btn_save_level">
                        <i class="fa fa-save"></i> Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function initLevelsPage() {
    if (!window.jQuery) {
        setTimeout(initLevelsPage, 80);
        return;
    }

    var $ = window.jQuery;

    if ($.fn.dataTable && $.fn.dataTable.isDataTable('#table_levels')) {
        $('#table_levels').DataTable().destroy();
    }

    if ($.fn.dataTable) {
        $('#table_levels').DataTable({
            paging: true,
            lengthChange: true,
            searching: true,
            ordering: true,
            info: true,
            autoWidth: false,
            responsive: false,
            order: [[1, 'asc']],
            language: { url: 'https://cdn.datatables.net/plug-ins/1.10.21/i18n/Spanish.json' }
        });
    }

    function openLevelModal() {
        var modalEl = document.getElementById('manage_level');
        if (window.bootstrap && bootstrap.Modal && modalEl) {
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        } else if ($.fn.modal) {
            $('#manage_level').modal('show');
        }
    }

    function resetLevelForm() {
        if ($('#level-frm').length) {
            $('#level-frm').get(0).reset();
        }
        $('#level_id').val('');
        $('#level_msg').html('');
        $('#manage_level .modal-title').text('Nuevo Nivel');
        $('#btn_save_level').html('<i class="fa fa-save"></i> Guardar');
        $('#level_state_active').prop('checked', true);
    }

    $(document).off('click', '#btn_new_level').on('click', '#btn_new_level', function () {
        resetLevelForm();
        openLevelModal();
    });

    $(document).off('click', '.edit_level').on('click', '.edit_level', function () {
        var id = $(this).attr('data-id');

        $.ajax({
            url: './get_level.php',
            method: 'GET',
            data: { id: id },
            success: function (resp) {
                var json = null;
                try {
                    json = typeof resp === 'object' ? resp : JSON.parse(resp);
                } catch (e) {
                    json = null;
                }

                if (!json || json.status != 1) {
                    alert((json && json.msg) ? json.msg : 'No se pudo obtener el nivel.');
                    return;
                }

                resetLevelForm();
                $('#manage_level .modal-title').text('Editar Nivel');
                $('#btn_save_level').html('<i class="fa fa-save"></i> Actualizar');
                $('#level_id').val(json.id || '');
                $('#level_name').val(json.level_name || '');
                if (String(json.state) === '0') {
                    $('#level_state_inactive').prop('checked', true);
                } else {
                    $('#level_state_active').prop('checked', true);
                }
                openLevelModal();
            },
            error: function () {
                alert('Error al obtener el nivel.');
            }
        });
    });

    $('#level-frm').off('submit').on('submit', function (e) {
        e.preventDefault();

        var $btn = $('#btn_save_level');
        $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Guardando...');
        $('#level_msg').html('');

        $.ajax({
            url: './save_level.php',
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
                    $('#level_msg').html('<div class="alert alert-danger py-2 mb-0">Respuesta inválida del servidor.</div>');
                    $btn.prop('disabled', false).html('<i class="fa fa-save"></i> Guardar');
                    return;
                }

                if (json.status == 1) {
                    $('#level_msg').html('<div class="alert alert-success py-2 mb-0">' + (json.msg || 'Nivel guardado.') + '</div>');
                    setTimeout(function () { location.reload(); }, 500);
                } else {
                    $('#level_msg').html('<div class="alert alert-danger py-2 mb-0">' + (json.msg || 'No se pudo guardar el nivel.') + '</div>');
                    $btn.prop('disabled', false).html('<i class="fa fa-save"></i> Guardar');
                }
            },
            error: function () {
                $('#level_msg').html('<div class="alert alert-danger py-2 mb-0">Error al guardar el nivel.</div>');
                $btn.prop('disabled', false).html('<i class="fa fa-save"></i> Guardar');
            }
        });
    });

    $(document).off('click', '.remove_level').on('click', '.remove_level', function () {
        var id = $(this).attr('data-id');
        if (!confirm('¿Está seguro de eliminar este nivel?')) {
            return;
        }

        $.ajax({
            url: './delete_level.php',
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
                    alert(json.msg || 'Nivel eliminado.');
                    location.reload();
                } else {
                    alert(json.msg || 'No se pudo eliminar el nivel.');
                }
            },
            error: function () {
                alert('Error al eliminar el nivel.');
            }
        });
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initLevelsPage);
} else {
    initLevelsPage();
}
</script>

<?php include('footer_adminlte.php'); ?>

