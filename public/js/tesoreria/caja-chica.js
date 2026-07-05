document.addEventListener('livewire:load', function() {

    Livewire.on('show-recuperar-modal', () => $('#modalRecuperar').modal('show'));
    Livewire.on('hide-recuperar-modal', () => $('#modalRecuperar').modal('hide'));
    Livewire.on('show-recuperar-rendido-modal', () => $('#modalRecuperarRendido').modal('show'));
    Livewire.on('hide-recuperar-rendido-modal', () => $('#modalRecuperarRendido').modal('hide'));
    Livewire.on('show-recuperar-pago-modal', () => $('#modalRecuperarPago').modal('show'));
    Livewire.on('hide-recuperar-pago-modal', () => $('#modalRecuperarPago').modal('hide'));
    Livewire.on('mostrar-modal-nuevo-fondo', () => {
        if ($('#modalNuevoFondo').length) {
            $('#modalNuevoFondo').modal('show');
        }
    });
    Livewire.on('cerrar-modal-nuevo-fondo', () => {
        if ($('#modalNuevoFondo').length) {
            $('#modalNuevoFondo').modal('hide');
        }
    });

    Livewire.on('contentChanged', function() {
        if ($.fn.datepicker) {
            $('.datepicker').not('.hasDatepicker').datepicker({
                dateFormat: 'dd/mm/yy',
                changeMonth: true,
                changeYear: true,
            });
        }
    });

    Livewire.on('fondo-actualizado', function(data) {
        console.log('Fondo actualizado:', data);
    });

    // === Dependencias Listeners ===
    Livewire.on('dependenciaCreada', function() {
        console.log('Dependencia creada exitosamente');
    });
    Livewire.on('dependenciaActualizada', function() {
        console.log('Dependencia actualizada exitosamente');
    });
    Livewire.on('dependenciaEliminada', function() {
        console.log('Dependencia eliminada exitosamente');
    });

    // === Data Reload Handler ===
    Livewire.on('datosRecargados', function() {
        console.log('Datos recargados correctamente');
    });
    Livewire.on('recargaCompletada', function() {
        console.log('Recarga completa finalizada');
    });

    // === Modal Close Handlers ===
    Livewire.on('cerrarModalAcreedores', function() {
        $('#modalAcreedores').modal('hide');
    });
    Livewire.on('cerrarModalDependencias', function() {
        $('#modalDependencias').modal('hide');
    });

    // === Modal Nuevo Pendiente - Bootstrap close handler ===
    $(document).on('hidden.bs.modal', '#modalNuevoPendiente', function() {
        Livewire.emit('cerrarModalNuevoPendiente');
    });

    // === Modal Nuevo Pago - Bootstrap close handler ===
    $(document).on('hidden.bs.modal', '#modalNuevoPago', function() {
        Livewire.emit('cerrarModalNuevoPago');
    });
    $(document).on('shown.bs.modal', '#modalNuevoPago', function() {
        $('#pagoEgreso').focus();
        const form = this.querySelector('form');
        if (form) {
            const focusable = Array.from(form.querySelectorAll('input, select, button[type="submit"]'));
            form.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' && e.target.tagName !== 'BUTTON') {
                    e.preventDefault();
                    const currentIndex = focusable.indexOf(e.target);
                    const nextElement = focusable[currentIndex + 1];
                    if (nextElement) nextElement.focus();
                }
            });
        }
    });

    // === Dependencias Modals ===
    window.addEventListener('formModal-show', function() {
        $('#formModal').modal({ backdrop: 'static', keyboard: false });
    });
    window.addEventListener('formModal-hide', function() {
        $('#formModal').modal('hide');
    });
    window.addEventListener('deleteModal-show', function() {
        $('#deleteModal').modal({ backdrop: 'static', keyboard: false });
    });
    window.addEventListener('deleteModal-hide', function() {
        $('#deleteModal').modal('hide');
    });

    // === Acreedores Modals ===
    window.addEventListener('formModalAcreedores-show', function() {
        $('#formModalAcreedores').modal({ backdrop: 'static', keyboard: false });
    });
    window.addEventListener('formModalAcreedores-hide', function() {
        $('#formModalAcreedores').modal('hide');
    });
    window.addEventListener('deleteModalAcreedores-show', function() {
        $('#deleteModalAcreedores').modal({ backdrop: 'static', keyboard: false });
    });
    window.addEventListener('deleteModalAcreedores-hide', function() {
        $('#deleteModalAcreedores').modal('hide');
    });

    // === Loading State Management ===
    const tablasAfectadas = ['tablaTotales', 'tablaPendientesDetalle', 'tablaPagos'];
    const manejarEstadoTablas = (estado) => {
        tablasAfectadas.forEach(tabla => {
            const elemento = document.querySelector(`[wire\\:loading\\.${tabla}]`);
            if (elemento) {
                if (estado === 'loading') {
                    elemento.classList.add('loading');
                } else {
                    elemento.classList.remove('loading');
                }
            }
        });
    };
    Livewire.on('loading', () => manejarEstadoTablas('loading'));
    Livewire.on('loaded', () => manejarEstadoTablas('loaded'));

    // === Error Handler ===
    Livewire.on('error', (error) => {
        console.error('Error en Livewire:', error);
    });

    $(document).ready(function() {
        console.log('Modales disponibles:', {
            nuevoFondo: $('#modalNuevoFondo').length > 0
        });
    });

    // === Preservar posición de scroll al recargar ===
    window.addEventListener('beforeunload', function() {
        sessionStorage.setItem('cajaChicaScrollY', window.scrollY);
    });
    window.addEventListener('load', function() {
        var saved = sessionStorage.getItem('cajaChicaScrollY');
        if (saved) {
            sessionStorage.removeItem('cajaChicaScrollY');
            setTimeout(function() {
                window.scrollTo(0, parseInt(saved, 10));
            }, 100);
        }
    });

    // === Modal Edit Fondo Escape Handler ===
    Livewire.on('modal-edit-fondo-opened', function() {
        setTimeout(() => {
            const editMonto = document.getElementById('editMonto');
            if (editMonto) {
                editMonto.focus();
                editMonto.select();
            }
        }, 300);
    });
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            if (document.getElementById('modalEditarFondo')) {
                Livewire.getByName('tesoreria.caja-chica.modales.modal-editar-fondo')[0].cerrarModal();
            }
            if (document.getElementById('modalRecuperar')) {
                Livewire.getByName('tesoreria.caja-chica.modales.modal-recuperar-saldos')[0].cerrarModal();
            }
            if (document.getElementById('modalRecuperarRendido')) {
                Livewire.getByName('tesoreria.caja-chica.modales.modal-recuperar-rendido')[0].cerrarModal();
            }
            if (document.getElementById('modalRecuperarPago')) {
                Livewire.getByName('tesoreria.caja-chica.modales.modal-recuperar-pago')[0].cerrarModal();
            }
        }
    });

    // === Modal Editar Detalle (Pendiente) ===
    $(document).ready(function() {
        $('#modalEditarDetalle').on('shown.bs.modal', function() {
            $('#inputNumeroPendiente').focus();
            const form = $(this).find('form');
            const inputs = form.find('input:not([type="hidden"]), textarea, select');
            inputs.off('keydown').on('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    const currentIndex = inputs.index(this);
                    const nextIndex = currentIndex + 1;
                    if (nextIndex < inputs.length) {
                        $(inputs[nextIndex]).focus();
                    } else {
                        form.closest('.modal-content').find('.btn-primary').focus();
                    }
                }
            });
        });
        $('#modalEditarDetalle').on('hidden.bs.modal', function() {
            window.livewire.emit('resetForm');
        });
        window.livewire.on('pendienteActualizado', () => {
            $('#modalEditarDetalle').modal('hide');
            setTimeout(function() {
                window.dispatchEvent(new CustomEvent('swal:success', {
                    detail: {
                        title: 'Éxito',
                        text: 'Pendiente actualizado con éxito'
                    }
                }));
                $('.modal-backdrop').remove();
                $('body').removeClass('modal-open');
                $('body').css('overflow', 'auto');
            }, 100);
        });
    });
});
