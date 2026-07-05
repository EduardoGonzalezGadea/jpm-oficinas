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
                    Livewire.emit('borrarCfe', id);
                }
            });
        }

        document.addEventListener('livewire:load', function () {
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
                @this.call('cancelarCarga');
            });

            window.addEventListener('swal:confirmar-orden-cobro-duplicada', (event) => {
                const data = event.detail;
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
                        @this.call('confirmarCarga', true);
                    } else if (result.isDenied) {
                        @this.call('cancelarCarga');
                    }
                });
            });

            window.addEventListener('swal:confirmar-guardar-referencia-duplicada', (event) => {
                const data = event.detail;
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
                        @this.call('confirmarCarga', true);
                    } else if (result.isDenied) {
                        @this.call('cancelarCarga');
                    }
                });
            });

            window.addEventListener('swal:modal', (event) => {
                const data = event.detail;
                Swal.fire({
                    icon: data.type || 'info',
                    title: data.title || 'Información',
                    text: data.text || '',
                });
            });

            window.addEventListener('swal:toast-error', (event) => {
                const data = event.detail;
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
                const data = event.detail;
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

            window.addEventListener('swal:documento-duplicado-bloqueante', (event) => {
                const data = event.detail;
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
                @this.call('cancelarNuevo');
            });

            $('#modalEditarCfe').on('hidden.bs.modal', function () {
                @this.call('cancelarEdicion');
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
                        @this.call('cancelarEdicion');
                    }
                });
            });
        });
    </script>
@endpush
