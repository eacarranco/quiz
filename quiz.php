<?php
include('auth.php');
include('db_connect.php');
header('Location: cuestionarios.php');
exit;
$title = 'Gestion de Cuestionarios';
include('header_adminlte.php');
?>

<style>
    /* Permite que el dropdown de acciones no sea recortado por el contenedor responsivo */
    .quiz-table-wrap {
        overflow: visible !important;
    }

    .quiz-table-wrap .dropdown-menu {
        z-index: 2000;
    }

    .icon-dropdown-toggle::after {
        display: none;
    }
</style>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex flex-wrap align-items-center gap-2">
                <h3 class="card-title">Listado de Cuestionarios</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-secondary btn-sm mr-2" id="btn_empty_modal">
                        <i class="fa fa-window-maximize"></i> Modal Vacio
                    </button>
                    <button type="button" class="btn btn-primary btn-sm" id="btn_add_quiz">
                        <i class="fa fa-plus"></i> Agregar Cuestionario
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive quiz-table-wrap">
                    <table class="table table-hover table-striped align-middle" id="table">
                        <thead>
                            <tr>
                                <th style="width: 5%; text-align: center;">#</th>
                                <th style="width: 32%;">Titulo</th>
                                <th style="width: 12%; text-align: center;">Preguntas</th>
                                <th style="width: 12%; text-align: center;">Puntos/Preg</th>
                                <?php if ($_SESSION['login_user_type'] == 1): ?>
                                    <th style="width: 20%;">Profesor</th>
                                <?php endif; ?>
                                <th style="width: 10%; text-align: center;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $where = '';
                            if ($_SESSION['login_user_type'] == 2) {
                                $where = ' WHERE q.user_id = ' . intval($_SESSION['login_id']) . ' ';
                            }

                            $qry = $conn->query('SELECT q.*, u.name AS teacher_name FROM quiz_list q LEFT JOIN users u ON q.user_id = u.id ' . $where . ' ORDER BY q.title ASC');
                            $i = 1;

                            if ($qry && $qry->num_rows > 0) {
                                while ($row = $qry->fetch_assoc()) {
                                    $items = $conn->query("SELECT COUNT(id) AS item_count FROM questions WHERE qid = '" . intval($row['id']) . "'")->fetch_assoc();
                                    $count = isset($items['item_count']) ? intval($items['item_count']) : 0;
                                    ?>
                                    <tr>
                                        <td style="text-align: center;"><strong><?php echo $i++; ?></strong></td>
                                        <td><?php echo htmlspecialchars($row['title']); ?></td>
                                        <td style="text-align: center;"><span class="badge bg-info"><?php echo $count; ?></span></td>
                                        <td style="text-align: center;"><span class="badge bg-success"><?php echo intval($row['qpoints']); ?></span></td>
                                        <?php if ($_SESSION['login_user_type'] == 1): ?>
                                            <td><?php echo htmlspecialchars($row['teacher_name']); ?></td>
                                        <?php endif; ?>
                                        <td style="text-align: center; vertical-align: middle;">
                                            <div class="dropdown d-inline-block">
                                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle icon-dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Opciones">
                                                    <i class="fa fa-ellipsis-v"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a class="dropdown-item" href="./quiz_view.php?id=<?php echo intval($row['id']); ?>">
                                                            <i class="fa fa-cog me-2"></i> Administrar
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <button class="dropdown-item edit_quiz" type="button" data-id="<?php echo intval($row['id']); ?>">
                                                            <i class="fa fa-edit me-2"></i> Editar
                                                        </button>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <button class="dropdown-item text-danger remove_quiz" type="button" data-id="<?php echo intval($row['id']); ?>">
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

<div class="modal fade" id="emptyModal" tabindex="-1" aria-labelledby="emptyModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="emptyModalTitle">Modal Vacio</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="quizModal" tabindex="-1" aria-labelledby="quizModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="quizModalTitle">Agregar Cuestionario</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="quizForm" method="POST">
                <div class="modal-body">
                    <div id="msg_quiz"></div>
                    <div id="error-msg-quiz" class="alert alert-danger" style="display:none;"></div>
                    <input type="hidden" name="id" id="quiz_id" value="">

                    <div class="mb-3">
                        <label for="quiz_title">Titulo del Cuestionario</label>
                        <input type="text" class="form-control" id="quiz_title" name="title" required>
                    </div>

                    <div class="mb-3">
                        <label for="quiz_cat_id">Categoria</label>
                        <select class="form-select" id="quiz_cat_id" name="quiz_cat_id" required>
                            <option value="">-- Selecciona una categoria --</option>
                            <?php
                            $cat_qry = $conn->query('SELECT id, cat_name FROM question_category WHERE state = 1 ORDER BY cat_name ASC');
                            if ($cat_qry && $cat_qry->num_rows > 0) {
                                while ($cat = $cat_qry->fetch_assoc()) {
                                    echo '<option value="' . intval($cat['id']) . '">' . htmlspecialchars($cat['cat_name']) . '</option>';
                                }
                            }
                            ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="quiz_qpoints">Puntos por Pregunta</label>
                        <input type="number" class="form-control" id="quiz_qpoints" name="qpoints" min="1" step="1" value="1" required>
                    </div>

                    <?php if ($_SESSION['login_user_type'] == 1): ?>
                        <div class="mb-3">
                            <label for="quiz_user_id">Profesor</label>
                            <select class="form-select" id="quiz_user_id" name="user_id" required>
                                <option value="">-- Selecciona un profesor --</option>
                                <?php
                                $user_qry = $conn->query('SELECT id, name FROM users WHERE user_type = 2 ORDER BY name ASC');
                                if ($user_qry && $user_qry->num_rows > 0) {
                                    while ($usr = $user_qry->fetch_assoc()) {
                                        echo '<option value="' . intval($usr['id']) . '">' . htmlspecialchars($usr['name']) . '</option>';
                                    }
                                }
                                ?>
                            </select>
                        </div>
                    <?php else: ?>
                        <input type="hidden" name="user_id" id="quiz_user_id" value="<?php echo intval($_SESSION['login_id']); ?>">
                    <?php endif; ?>

                    <div class="mb-0">
                        <label class="d-block">Mezclar Opciones</label>
                        <div class="form-check form-check-inline">
                            <input type="radio" id="randomize_no" name="randomize_options" class="form-check-input" value="0" checked>
                            <label class="form-check-label" for="randomize_no">No</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input type="radio" id="randomize_yes" name="randomize_options" class="form-check-input" value="1">
                            <label class="form-check-label" for="randomize_yes">Si</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="quiz_submit_btn"><i class="fa fa-save"></i> Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function showModalSafe(selector) {
        if (window.bootstrap && bootstrap.Modal) {
            bootstrap.Modal.getOrCreateInstance(document.querySelector(selector)).show();
            return;
        }
        if (window.jQuery && $.fn && $.fn.modal) {
            $(selector).modal('show');
            return;
        }
        var modal = document.querySelector(selector);
        if (!modal) return;
        modal.classList.add('show');
        modal.style.display = 'block';
        document.body.classList.add('modal-open');
        if (!document.querySelector('.js-fallback-backdrop')) {
            var backdrop = document.createElement('div');
            backdrop.className = 'modal-backdrop fade show js-fallback-backdrop';
            document.body.appendChild(backdrop);
        }
    }

    function initializeDataTable() {
        if ($.fn.dataTable.isDataTable('#table')) {
            $('#table').DataTable().destroy();
        }
        $('#table').DataTable({
            "paging": true,
            "lengthChange": true,
            "searching": true,
            "ordering": true,
            "info": true,
            "autoWidth": false,
            "responsive": false,
            "order": [[1, "asc"]],
            "language": {
                "url": "https://cdn.datatables.net/plug-ins/1.10.21/i18n/Spanish.json"
            }
        });
    }

    function resetQuizForm() {
        $('#msg_quiz').html('');
        $('#error-msg-quiz').hide().html('');
        $('#quizForm').get(0).reset();
        $('#quiz_id').val('');
        $('#quizModalTitle').text('Agregar nuevo cuestionario');
        $('#quiz_submit_btn').html('<i class="fa fa-save"></i> Guardar');
    }

    function showQuizError(message) {
        $('#error-msg-quiz').html('<i class="fa fa-exclamation-circle"></i> ' + message).show();
    }

    if (window.jQuery) {
    $(function(){
        // Mismo patrón de accesibilidad usado en faculty.php
        var modalElements = document.querySelectorAll('#quizModal, #emptyModal');
        modalElements.forEach(function(modal) {
            var observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'attributes' && mutation.attributeName === 'aria-hidden') {
                        modal.removeAttribute('aria-hidden');
                    }
                });
            });
            observer.observe(modal, { attributes: true, attributeFilter: ['aria-hidden'] });
        });

        $('#quizModal, #emptyModal').on('show.bs.modal', function () {
            $(this).removeAttr('aria-hidden');
        });

        initializeDataTable();

        $(document).on('click', '#btn_add_quiz', function(e){
            e.preventDefault();
            resetQuizForm();
            showModalSafe('#quizModal');
        });

        $(document).on('click', '#btn_empty_modal', function(e){
            e.preventDefault();
            showModalSafe('#emptyModal');
        });

        // Fallback robusto para menu de opciones en caso de falla del plugin dropdown
        $(document).on('click', '.icon-dropdown-toggle', function (e) {
            var dropdownPluginAvailable = !!(window.bootstrap && bootstrap.Dropdown);
            if (dropdownPluginAvailable) {
                return;
            }
            e.preventDefault();
            e.stopPropagation();
            var $menu = $(this).siblings('.dropdown-menu');
            $('.dropdown-menu.show').not($menu).removeClass('show');
            $menu.toggleClass('show');
        });

        $(document).on('click', function () {
            $('.dropdown-menu.show').removeClass('show');
        });

        $(document).on('click', '.dropdown-menu', function (e) {
            e.stopPropagation();
        });

        $(document).on('click', '.edit_quiz', function(){
            var id = $(this).attr('data-id');
            $.ajax({
                url:'./get_quiz.php?id='+id,
                error:err=>console.log(err),
                success:function(resp){
                    if(typeof resp != undefined){
                        resp = JSON.parse(resp);
                        resetQuizForm();
                        $('#quiz_id').val(resp.id || '');
                        $('#quiz_title').val(resp.title || '');
                        $('#quiz_cat_id').val(resp.quiz_cat_id || '');
                        $('#quiz_qpoints').val(resp.qpoints || 1);
                        $('#quiz_user_id').val(resp.user_id || '');
                        if (String(resp.randomize_options) === '1') {
                            $('#randomize_yes').prop('checked', true);
                        } else {
                            $('#randomize_no').prop('checked', true);
                        }
                        $('#quizModalTitle').text('Editar Cuestionario');
                        $('#quiz_submit_btn').html('<i class="fa fa-save"></i> Actualizar');
                        showModalSafe('#quizModal');
                    }
                }
            });
        });

        $(document).on('click', '.remove_quiz', function(){
            var id = $(this).attr('data-id');
            if(confirm('¿Está seguro que desea eliminar este cuestionario?')){
                $.ajax({
                    url:'./delete_quiz.php?id='+id,
                    error:err=>console.log(err),
                    success:function(resp){
                        if(resp == true || resp === '1')
                            location.reload();
                    }
                });
            }
        });

        $('#quizForm').submit(function(e){
            e.preventDefault();
            var $form = $(this);
            var title = $('#quiz_title').val().trim();
            var categoryId = $('#quiz_cat_id').val();
            var qpoints = parseInt($('#quiz_qpoints').val(), 10);

            $('#error-msg-quiz').hide().html('');

            if (!title) {
                showQuizError('El titulo del cuestionario es requerido.');
                return false;
            }

            if (!categoryId) {
                showQuizError('Debe seleccionar una categoria.');
                return false;
            }

            if (!qpoints || qpoints <= 0) {
                showQuizError('Los puntos por pregunta deben ser mayores a 0.');
                return false;
            }

            <?php if ($_SESSION['login_user_type'] == 1): ?>
            if (!$('#quiz_user_id').val()) {
                showQuizError('Debe seleccionar un profesor.');
                return false;
            }
            <?php endif; ?>

            $('#quiz_submit_btn').attr('disabled',true)
            $('#quiz_submit_btn').html('<i class="fa fa-spinner fa-spin"></i> Guardando...')
            $('#msg_quiz').html('')

            $.ajax({
                url:'./save_quiz.php',
                method:'POST',
                data:$form.serialize(),
                error:err=>{
                    console.log(err)
                    showQuizError('Ocurrió un error al guardar el cuestionario.');
                    $('#quiz_submit_btn').removeAttr('disabled')
                    $('#quiz_submit_btn').html('<i class="fa fa-save"></i> Guardar')
                },
                success:function(resp){
                    try {
                        resp = JSON.parse(resp)
                        if(resp.status == 1){
                            alert(resp.msg || 'Cuestionario guardado correctamente');
                            location.reload()
                        }else{
                            showQuizError(resp.msg || 'No se pudo guardar el cuestionario.');
                            $('#quiz_submit_btn').removeAttr('disabled')
                            $('#quiz_submit_btn').html('<i class="fa fa-save"></i> Guardar')
                        }
                    } catch (e) {
                        showQuizError('Respuesta invalida del servidor.');
                        $('#quiz_submit_btn').removeAttr('disabled')
                        $('#quiz_submit_btn').html('<i class="fa fa-save"></i> Guardar')
                    }
                }
            })
        })
    })
    } else {
        document.addEventListener('DOMContentLoaded', function () {
            var modal = document.getElementById('quizModal');
            var form = document.getElementById('quizForm');
            var msg = document.getElementById('msg_quiz');
            var errBox = document.getElementById('error-msg-quiz');
            var submitBtn = document.getElementById('quiz_submit_btn');

            function showErr(text) {
                if (!errBox) return;
                errBox.style.display = 'block';
                errBox.innerHTML = '<i class="fa fa-exclamation-circle"></i> ' + text;
            }

            function clearErr() {
                if (!errBox) return;
                errBox.style.display = 'none';
                errBox.innerHTML = '';
            }

            function openModal() {
                if (!modal) return;
                clearErr();
                if (msg) msg.innerHTML = '';
                form.reset();
                document.getElementById('quiz_id').value = '';
                document.getElementById('quizModalTitle').textContent = 'Agregar nuevo cuestionario';
                submitBtn.innerHTML = '<i class="fa fa-save"></i> Guardar';
                modal.classList.add('show');
                modal.style.display = 'block';
                document.body.classList.add('modal-open');
                if (!document.querySelector('.js-fallback-backdrop')) {
                    var backdrop = document.createElement('div');
                    backdrop.className = 'modal-backdrop fade show js-fallback-backdrop';
                    document.body.appendChild(backdrop);
                }
            }

            function closeModal() {
                if (!modal) return;
                modal.classList.remove('show');
                modal.style.display = 'none';
                document.body.classList.remove('modal-open');
                var bd = document.querySelector('.js-fallback-backdrop');
                if (bd) bd.remove();
            }

            document.getElementById('btn_add_quiz').addEventListener('click', function (e) {
                e.preventDefault();
                openModal();
            });

            document.addEventListener('click', function (e) {
                if (e.target.closest('[data-bs-dismiss="modal"], [data-dismiss="modal"]')) {
                    e.preventDefault();
                    closeModal();
                }
            });

            document.addEventListener('click', function (e) {
                var toggle = e.target.closest('.icon-dropdown-toggle');
                if (toggle) {
                    e.preventDefault();
                    e.stopPropagation();
                    var menu = toggle.parentElement.querySelector('.dropdown-menu');
                    document.querySelectorAll('.dropdown-menu.show').forEach(function (m) {
                        if (m !== menu) m.classList.remove('show');
                    });
                    if (menu) menu.classList.toggle('show');
                    return;
                }

                if (!e.target.closest('.dropdown-menu')) {
                    document.querySelectorAll('.dropdown-menu.show').forEach(function (m) {
                        m.classList.remove('show');
                    });
                }
            });

            document.addEventListener('click', function (e) {
                var editBtn = e.target.closest('.edit_quiz');
                if (editBtn) {
                    e.preventDefault();
                    fetch('./get_quiz.php?id=' + editBtn.getAttribute('data-id'))
                        .then(function (r) { return r.json(); })
                        .then(function (resp) {
                            openModal();
                            document.getElementById('quiz_id').value = resp.id || '';
                            document.getElementById('quiz_title').value = resp.title || '';
                            document.getElementById('quiz_cat_id').value = resp.quiz_cat_id || '';
                            document.getElementById('quiz_qpoints').value = resp.qpoints || 1;
                            var userEl = document.getElementById('quiz_user_id');
                            if (userEl) userEl.value = resp.user_id || '';
                            document.getElementById('randomize_yes').checked = String(resp.randomize_options) === '1';
                            document.getElementById('randomize_no').checked = String(resp.randomize_options) !== '1';
                            document.getElementById('quizModalTitle').textContent = 'Editar Cuestionario';
                            submitBtn.innerHTML = '<i class="fa fa-save"></i> Actualizar';
                        })
                        .catch(function () {
                            alert('No se pudo cargar el cuestionario.');
                        });
                    return;
                }

                var removeBtn = e.target.closest('.remove_quiz');
                if (removeBtn) {
                    e.preventDefault();
                    if (!confirm('¿Está seguro que desea eliminar este cuestionario?')) return;
                    fetch('./delete_quiz.php?id=' + removeBtn.getAttribute('data-id'))
                        .then(function () { location.reload(); })
                        .catch(function () { alert('Error al eliminar el cuestionario.'); });
                }
            });

            form.addEventListener('submit', function (e) {
                e.preventDefault();
                clearErr();

                var title = document.getElementById('quiz_title').value.trim();
                var cat = document.getElementById('quiz_cat_id').value;
                var pts = parseInt(document.getElementById('quiz_qpoints').value || '0', 10);

                if (!title) return showErr('El titulo del cuestionario es requerido.');
                if (!cat) return showErr('Debe seleccionar una categoria.');
                if (!pts || pts <= 0) return showErr('Los puntos por pregunta deben ser mayores a 0.');

                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Guardando...';

                fetch('./save_quiz.php', {
                    method: 'POST',
                    body: new FormData(form)
                })
                .then(function (r) { return r.json(); })
                .then(function (resp) {
                    if (resp.status == 1) {
                        alert(resp.msg || 'Cuestionario guardado correctamente');
                        location.reload();
                        return;
                    }
                    showErr(resp.msg || 'No se pudo guardar el cuestionario.');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fa fa-save"></i> Guardar';
                })
                .catch(function () {
                    showErr('Ocurrió un error al guardar el cuestionario.');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fa fa-save"></i> Guardar';
                });
            });
        });
    }
</script>

<?php include('footer_adminlte.php'); ?>


