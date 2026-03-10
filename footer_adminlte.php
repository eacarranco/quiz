      </div>
    </div>
  </main>

  <footer class="app-footer main-footer">
    <div class="float-end d-none d-sm-inline">Versión 1.0.0</div>
    <strong>Copyright &copy; <?php echo date('Y') ?> <a href="#">Sistema de Cuestionarios</a>.</strong>
    Todos los derechos reservados.
  </footer>
</div>

<script src="./assets/js/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@4.0.0-rc4/dist/js/adminlte.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>

<script>
$(function() {
  // Bridge de migracion Bootstrap 4 -> Bootstrap 5 para atributos legacy
  $('[data-toggle]').each(function () {
    var $el = $(this);
    if (!$el.attr('data-bs-toggle')) {
      $el.attr('data-bs-toggle', $el.attr('data-toggle'));
    }
  });
  $('[data-target]').each(function () {
    var $el = $(this);
    if (!$el.attr('data-bs-target')) {
      $el.attr('data-bs-target', $el.attr('data-target'));
    }
  });
  $('[data-dismiss="modal"]').attr('data-bs-dismiss', 'modal');

  // Shim jQuery para proyectos con llamadas legacy $('#id').modal('show')
  if ($.fn && !$.fn.modal && window.bootstrap && bootstrap.Modal) {
    $.fn.modal = function (action) {
      return this.each(function () {
        var instance = bootstrap.Modal.getOrCreateInstance(this);
        if (action === 'show') instance.show();
        else if (action === 'hide') instance.hide();
        else if (action === 'toggle') instance.toggle();
      });
    };
  }

  if ($.fn && !$.fn.dropdown && window.bootstrap && bootstrap.Dropdown) {
    $.fn.dropdown = function (action) {
      return this.each(function () {
        var instance = bootstrap.Dropdown.getOrCreateInstance(this);
        if (action === 'show') instance.show();
        else if (action === 'hide') instance.hide();
        else if (action === 'toggle' || action === undefined) instance.toggle();
      });
    };
  }

  // Marca activo el item de sidebar según URL actual
  var path = window.location.pathname.split('/').pop();
  $('.sidebar-menu .nav-link').each(function () {
    var href = ($(this).attr('href') || '').split('/').pop();
    if (href && href === path) {
      $(this).addClass('active');
      $(this).closest('.nav-treeview').closest('.nav-item').addClass('menu-open');
      $(this).closest('.nav-treeview').prev('.nav-link').addClass('active');
    }
  });

  // Focus en primer input al abrir modal
  $(document).on('shown.bs.modal', function(e) {
    $(e.target).find('input:first, textarea:first, select:first').focus();
  });
});
</script>

</body>
</html>

