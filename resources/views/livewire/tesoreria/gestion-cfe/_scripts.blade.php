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

    function getEventData(event) {
      if (!event) return {};
      if (typeof window.LiveEvent === 'function') return window.LiveEvent(event);
      var d = event.detail !== undefined ? event.detail : event;
      return (Array.isArray(d) && d.length > 0) ? d[0] : (d || {});
    }

    $(document).on('hide.bs.dropdown', '#dropdownMesesWrapper', function (e) {
      if (e.clickEvent && $(e.clickEvent.target).closest('.dropdown-menu').length) {
        e.preventDefault();
      }
    });

    // Cierre declarativo del modal de confirmación
    window.addEventListener('cerrar-modal-confirmacion-cfe', () => {
      // No-op: el modal se cierra declarativamente con la propiedad de Livewire
    });

    // ─── ADVERTENCIA: Concepto de Caja - Valor nunca visto antes (carga PDF) ──
    window.addEventListener('swal:confirmar-concepto-nuevo', (event) => {
      const data = getEventData(event);
      Swal.fire({
        title: 'Monto no habitual',
        html: `El monto <strong>${data.totalAPagar || ''}</strong> no figura en los últimos registros del concepto <strong>${data.concepto || ''}</strong>.<br><br>¿Desea guardar de todas formas?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, guardar igual',
        cancelButtonText: 'Revisar'
      }).then((result) => {
        if (result.isConfirmed) {
          Livewire.dispatch('confirmar-carga-forzado');
        }
      });
    });

    // ─── ADVERTENCIA: Concepto diferente (carga PDF) ────────────────────────
    window.addEventListener('swal:confirmar-concepto-diferente', (event) => {
      const data = getEventData(event);
      Swal.fire({
        title: 'Posible concepto incorrecto',
        html: `El monto <strong>${data.totalAPagar || ''}</strong> aparece en ${data.cantidad || ''} registro(s) bajo el concepto <strong>${data.conceptoFrecuente || ''}</strong>, pero usted seleccionó <strong>${data.concepto || ''}</strong>.<br><br>¿Desea guardar con el concepto seleccionado?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, usar este concepto',
        cancelButtonText: 'Revisar'
      }).then((result) => {
        if (result.isConfirmed) {
          Livewire.dispatch('confirmar-carga-forzado');
        }
      });
    });

    // ─── ADVERTENCIA: Concepto de Caja - Valor no habitual (modal Nuevo CFE) ──
    window.addEventListener('swal:confirmar-concepto-nuevo-nuevo', (event) => {
      const data = getEventData(event);
      Swal.fire({
        title: 'Monto no habitual',
        html: `El monto <strong>${data.totalAPagar || ''}</strong> no figura en los últimos registros del concepto <strong>${data.concepto || ''}</strong>.<br><br>¿Desea guardar de todas formas?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, guardar igual',
        cancelButtonText: 'Revisar'
      }).then((result) => {
        if (result.isConfirmed) {
          Livewire.dispatch('guardar-nuevo-forzado');
        }
      });
    });

    // ─── ADVERTENCIA: Concepto diferente (modal Nuevo CFE) ───────────────────
    window.addEventListener('swal:confirmar-concepto-diferente-nuevo', (event) => {
      const data = getEventData(event);
      Swal.fire({
        title: 'Posible concepto incorrecto',
        html: `El monto <strong>${data.totalAPagar || ''}</strong> aparece en ${data.cantidad || ''} registro(s) bajo el concepto <strong>${data.conceptoFrecuente || ''}</strong>, pero usted seleccionó <strong>${data.concepto || ''}</strong>.<br><br>¿Desea guardar con el concepto seleccionado?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, usar este concepto',
        cancelButtonText: 'Revisar'
      }).then((result) => {
        if (result.isConfirmed) {
          Livewire.dispatch('guardar-nuevo-forzado');
        }
      });
    });

    // ─── ADVERTENCIA: Orden de Cobro Duplicada (carga PDF) ───────────────────
    window.addEventListener('swal:confirmar-orden-cobro-duplicada', (event) => {
      const data = getEventData(event);
      Swal.fire({
        title: 'Orden de Cobro Duplicada',
        html: `La orden de cobro <strong>${data.ordenCobro || ''}</strong> ya existe en el documento <strong>${data.documentoExistente || ''}</strong>.<br><br>¿Desea grabar de todas formas o descartar la carga?`,
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
          Livewire.dispatch('confirmar-carga-ignorar-duplicados');
        } else if (result.isDenied) {
          Livewire.dispatch('cancelarCarga');
        }
      });
    });

    // ─── ADVERTENCIA: Referencia Duplicada (carga PDF) ──────────────────────
    window.addEventListener('swal:confirmar-guardar-referencia-duplicada', (event) => {
      const data = getEventData(event);
      Swal.fire({
        title: 'Referencia Duplicada',
        html: `La referencia al documento original <strong>${data.documentoReferencia || ''}</strong> ya existe en el documento <strong>${data.documentoExistente || ''}</strong>.<br><br>¿Desea grabar de todas formas o descartar la carga?`,
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
          Livewire.dispatch('confirmar-carga-ignorar-duplicados');
        } else if (result.isDenied) {
          Livewire.dispatch('cancelarCarga');
        }
      });
    });

    // ─── ADVERTENCIA: Orden de Cobro Duplicada (Nuevo CFE manual) ───────────
    window.addEventListener('swal:confirmar-orden-cobro-duplicada-nuevo', (event) => {
      const data = getEventData(event);
      Swal.fire({
        title: 'Orden de Cobro Duplicada',
        html: `La orden de cobro <strong>${data.ordenCobro || ''}</strong> ya existe en el documento <strong>${data.documentoExistente || ''}</strong>.<br><br>¿Desea grabar de todas formas o descartar la carga?`,
        icon: 'warning',
        showCancelButton: true,
        showDenyButton: true,
        confirmButtonColor: '#28a745',
        denyButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Grabar de todas formas',
        denyButtonText: 'Descartar',
        cancelButtonText: 'Cancelar y revisar'
      }).then((result) => {
        if (result.isConfirmed) {
          Livewire.dispatch('guardar-nuevo-ignorar-duplicados');
        } else if (result.isDenied) {
          Livewire.dispatch('cancelarNuevo');
        }
      });
    });

    // ─── ADVERTENCIA: Referencia Duplicada (Nuevo CFE manual) ───────────────
    window.addEventListener('swal:confirmar-guardar-referencia-duplicada-nuevo', (event) => {
      const data = getEventData(event);
      Swal.fire({
        title: 'Referencia Duplicada',
        html: `La referencia al documento original <strong>${data.documentoReferencia || ''}</strong> ya existe en el documento <strong>${data.documentoExistente || ''}</strong>.<br><br>¿Desea grabar de todas formas o descartar la carga?`,
        icon: 'warning',
        showCancelButton: true,
        showDenyButton: true,
        confirmButtonColor: '#28a745',
        denyButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Grabar de todas formas',
        denyButtonText: 'Descartar',
        cancelButtonText: 'Cancelar y revisar'
      }).then((result) => {
        if (result.isConfirmed) {
          Livewire.dispatch('guardar-nuevo-ignorar-duplicados');
        } else if (result.isDenied) {
          Livewire.dispatch('cancelarNuevo');
        }
      });
    });

    window.addEventListener('swal:confirmar-eliminar-cfe-con-asientos', (event) => {
      const data = getEventData(event);
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
      const data = getEventData(event);
      Swal.fire({
        title: 'Documento Duplicado',
        html: `El documento <strong>${data.documentoTipo || ''} ${data.documentoNumero || ''}</strong> ya existe.<br><br>No se puede grabar un documento con el mismo tipo y número que uno ya existente.`,
        icon: 'error',
        confirmButtonColor: '#3085d6',
        confirmButtonText: 'Cerrar'
      });
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
