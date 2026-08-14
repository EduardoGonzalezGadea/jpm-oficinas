@push('scripts')
  <script>
    function confirmDeleteCfe(id) {
      Swal.fire({
        title: '¿Está seguro?',
        text: 'Esta acción no se puede deshacer y eliminará el CFE seleccionado.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
      }).then((result) => {
        if (result.isConfirmed) {
          Livewire.dispatch('borrarCfe', { cfeId: id });
        }
      });
    }

    document.addEventListener('livewire:init', function () {
      $('#dropdownMesesWrapper').on('hide.bs.dropdown', function (e) {
        if (e.clickEvent && $(e.clickEvent.target).closest('.dropdown-menu').length) {
          e.preventDefault();
        }
      });

      window.addEventListener('abrir-modal-confirmacion-cfe', () => {
        $('#modalConfirmacionCfe').modal('show');
      });

      window.addEventListener('cerrar-modal-confirmacion-cfe', () => {
        $('#modalConfirmacionCfe').modal('hide');
      });

      $('#modalConfirmacionCfe').on('hidden.bs.modal', function () {
        Livewire.dispatch('cancelarCarga');
      });

      window.addEventListener('swal:confirmar-orden-cobro-duplicada', (event) => {
        const data = window.LiveEvent(event);
        Swal.fire({
          title: 'Orden de Cobro Duplicada',
          html: `La orden de cobro <strong>${data.ordenCobro}</strong> ya existe en el documento <strong>${data.documentoExistente}</strong>.<br><br>¿Desea grabar de todas formas o descartar la carga?`,
          icon: 'warning',
          showCancelButton: true,
          showDenyButton: true,
          confirmButtonColor: '#28a745',
          denyButtonColor: '#dc3545',
          cancelButtonColor: '#6c757d',
          confirmButtonText: 'Grabar de todas formas',
          denyButtonText: 'Descartar carga',
          cancelButtonText: 'Cancelar y revisar'
        }).then((result) => {
          if (result.isConfirmed) {
            Livewire.dispatch('confirmarCarga', { ignorarAdvertencias: true });
          } else if (result.isDenied) {
            Livewire.dispatch('cancelarCarga');
          }
        });
      });

      window.addEventListener('swal:confirmar-guardar-referencia-duplicada', (event) => {
        const data = window.LiveEvent(event);
        Swal.fire({
          title: 'Referencia Duplicada',
          html: `La referencia al documento original <strong>${data.documentoReferencia}</strong> ya existe en el documento <strong>${data.documentoExistente}</strong>.<br><br>¿Desea grabar de todas formas o descartar la carga?`,
          icon: 'warning',
          showCancelButton: true,
          showDenyButton: true,
          confirmButtonColor: '#28a745',
          denyButtonColor: '#dc3545',
          cancelButtonColor: '#6c757d',
          confirmButtonText: 'Grabar de todas formas',
          denyButtonText: 'Descartar carga',
          cancelButtonText: 'Cancelar y revisar'
        }).then((result) => {
          if (result.isConfirmed) {
            Livewire.dispatch('confirmarCarga', { ignorarAdvertencias: true });
          } else if (result.isDenied) {
            Livewire.dispatch('cancelarCarga');
          }
        });
      });

      window.addEventListener('swal:modal', (event) => {
        const data = window.LiveEvent(event);
        Swal.fire({
          icon: data.type || 'info',
          title: data.title || 'Información',
          text: data.text || '',
        });
      });

      window.addEventListener('swal:toast-error', (event) => {
        const data = window.LiveEvent(event);
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: data.text || 'Ocurrió un error inesperado.',
          toast: true,
          position: 'top-end',
          showConfirmButton: false,
          timer: 5000,
          timerProgressBar: true,
        });
      });

      window.addEventListener('swal:toast-success', (event) => {
        const data = window.LiveEvent(event);
        Swal.fire({
          icon: 'success',
          title: 'Éxito',
          text: data.text || 'Operación completada.',
          toast: true,
          position: 'top-end',
          showConfirmButton: false,
          timer: 3000,
          timerProgressBar: true,
        });
      });

      window.addEventListener('swal:confirmar-eliminar-cfe-con-asientos', (event) => {
        const data = window.LiveEvent(event);
        const texto = data.cantidad === 1
          ? 'Este CFE tiene 1 asiento asociado en el Libro Diario que también será eliminado y los saldos serán recalculados desde la fecha del CFE en adelante. ¿Desea continuar?'
          : `Este CFE tiene ${data.cantidad} asientos asociados en el Libro Diario que también serán eliminados y los saldos serán recalculados desde la fecha del CFE en adelante. ¿Desea continuar?`;
        Swal.fire({
          title: '¿Está seguro?',
          text: texto,
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#d33',
          cancelButtonColor: '#3085d6',
          confirmButtonText: 'Sí, eliminar todo',
          cancelButtonText: 'Cancelar'
        }).then((result) => {
          if (result.isConfirmed) {
            Livewire.dispatch('borrarCfe', { cfeId: data.cfeId });
          }
        });
      });

      window.addEventListener('swal:documento-duplicado-bloqueante', (event) => {
        const data = window.LiveEvent(event);
        Swal.fire({
          title: 'Documento Duplicado',
          html: `El documento <strong>${data.documentoTipo} ${data.documentoNumero}</strong> ya existe.<br><br>No se puede grabar un documento con el mismo tipo y número que uno ya existente.`,
          icon: 'error',
          confirmButtonColor: '#3085d6',
          confirmButtonText: 'Cerrar'
        });
      });

      window.addEventListener('abrir-modal-editar-cfe', () => {
        $('#modalEditarCfe').modal('show');
      });
      window.addEventListener('cerrar-modal-editar-cfe', () => {
        $('#modalEditarCfe').modal('hide');
      });

      window.addEventListener('abrir-modal-nuevo-cfe', () => {
        $('#modalNuevoCfe').modal('show');
      });

      window.addEventListener('cerrar-modal-nuevo-cfe', () => {
        $('#modalNuevoCfe').modal('hide');
      });

      $('#modalNuevoCfe').on('hidden.bs.modal', function () {
        Livewire.dispatch('cancelarNuevo');
      });

      $('#modalEditarCfe').on('hidden.bs.modal', function () {
        Livewire.dispatch('cancelarEdicion');
      });

      window.addEventListener('confirmar-descartar-cambios', () => {
        Swal.fire({
          title: '¿Descartar cambios?',
          text: 'Los cambios no guardados se perderán.',
          icon: 'question',
          showCancelButton: true,
          confirmButtonColor: '#dc3545',
          cancelButtonColor: '#3085d6',
          confirmButtonText: 'Sí, descartar',
          cancelButtonText: 'Seguir editando'
        }).then((result) => {
          if (result.isConfirmed) {
            Livewire.dispatch('cancelarEdicion');
          }
        });
      });
    });

    // Uppercase en vivo (sin round-trip al servidor): convierte a mayúsculas
    // mientras se escribe, preservando la posición del cursor. Solo afecta a
    // los inputs con la clase .texto-upper del modal de nuevo CFE.
    document.addEventListener('input', function (e) {
      var el = e.target;
      if (!el || !el.classList) return;

      if (el.classList.contains('js-cant-item') || el.classList.contains('js-precio-item')) {
        recalcImporteItem(el);
      }

      if (!el.classList.contains('js-upper')) return;
      var start = el.selectionStart;
      var end = el.selectionEnd;
      var arrastrado = document.activeElement === el;
      if (el.value !== el.value.toUpperCase()) {
        el.value = el.value.toUpperCase();
        if (arrastrado && start !== null) {
          el.setSelectionRange(start, end);
        }
      }
    });

    function recalcImporteItem(el) {
      var row = el.closest('tr[data-fila]');
      if (!row) return;
      var cant = parseFloat((row.querySelector('.js-cant-item') || {}).value) || 0;
      var precio = parseFloat((row.querySelector('.js-precio-item') || {}).value) || 0;
      var importe = row.querySelector('.js-importe-item');
      if (importe) {
        importe.value = (Math.round(cant * precio * 100) / 100).toFixed(2);
      }
      recalcularTotalNuevoCfe();
    }

    function recalcularTotalNuevoCfe() {
      var total = 0;
      document.querySelectorAll('#tablaNuevoItems tbody tr[data-fila]').forEach(function (row) {
        var importe = row.querySelector('.js-importe-item');
        if (importe) {
          total += parseFloat(importe.value) || 0;
        }
      });
      var totalCell = document.querySelector('#totalNuevoCfe');
      if (totalCell) {
        totalCell.textContent = '$ ' + total.toFixed(2).replace('.', ',');
      }
    }
  </script>
@endpush
