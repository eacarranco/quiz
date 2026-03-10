<meta charset = "utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<!-- 1. CORE CSS (Bootstrap 5) -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" />

<!-- 2. ICONS -->
<link rel = "stylesheet" type = "text/css" href = "https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" />

<!-- 3. DATA TABLES -->
<link href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet" crossorigin="anonymous" />
<link href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css" rel="stylesheet" crossorigin="anonymous" />

<!-- 4. SELECT2 -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />

<!-- 5. CUSTOM STYLES -->
<link rel = "stylesheet" type = "text/css" href = "assets/css/style.css" />

<style>
  /* Select2 - sincronizar con Bootstrap */
  .select2-container {
    width: 100% !important;
  }

  .select2-container--default .select2-selection--single {
    border: 1px solid #ced4da;
    border-radius: 0.25rem;
    height: auto;
    min-height: calc(1.5em + 0.75rem + 2px);
  }

  .select2-container--default .select2-selection--single .select2-selection__rendered {
    padding: 0.375rem 0.75rem;
    line-height: 1.5;
  }

  .select2-container--default .select2-selection--multiple {
    border: 1px solid #ced4da;
    border-radius: 0.25rem;
    min-height: calc(1.5em + 0.75rem + 2px);
  }

  .select2-dropdown {
    border: 1px solid #ced4da;
    z-index: 1070 !important;
  }
</style>

<!-- SCRIPTS: ORDEN CRÍTICO -->
<!-- 1. jQuery PRIMERO -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- 2. Bootstrap 5 Bundle (incluye Popper) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- 4. DataTables -->
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js" crossorigin="anonymous"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js" crossorigin="anonymous"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js" crossorigin="anonymous"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js" crossorigin="anonymous"></script>

<!-- 5. Select2 -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>

<script>
  // Bridge global para vistas con header.php (legacy Bootstrap 4 -> Bootstrap 5)
  (function () {
    function applyBs5AttrBridge() {
      if (!window.jQuery) return;
      var $ = window.jQuery;

      // Configuracion global para evitar CORS por URL relativa en DataTables.
      window.DATATABLES_LANG_ES_URL = 'https://cdn.datatables.net/plug-ins/1.10.21/i18n/Spanish.json';
      if ($.fn && $.fn.dataTable && $.fn.dataTable.defaults) {
        $.extend(true, $.fn.dataTable.defaults, {
          language: { url: window.DATATABLES_LANG_ES_URL }
        });
      }

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
    }

    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', applyBs5AttrBridge);
    } else {
      applyBs5AttrBridge();
    }
  })();
</script>


