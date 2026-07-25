(function() {
    'use strict';

    // --- Loader ---
    document.addEventListener('DOMContentLoaded', function() {
        const loader = document.getElementById('loader');

        window.addEventListener('hide-loader', () => {
            if (loader) loader.style.display = 'none';
        });

        if (typeof Livewire !== 'undefined') {
            Livewire.on('message.received', () => {
                if (loader) loader.style.display = 'none';
            });

            Livewire.onError((statusCode, response) => {
                if (loader) loader.style.display = 'none';

                if (window.isSessionExpiredResponse && window.isSessionExpiredResponse(statusCode, response)) {
                    const payload = typeof response === 'string' ? (function() {
                        try { return JSON.parse(response); } catch (e) { return {}; }
                    })() : (response || {});
                    window.handleSessionExpired({
                        message: payload.message || payload.error || undefined,
                        redirect: payload.redirect || null,
                    });
                    return false;
                }

                if (statusCode === 500) {
                    Swal.fire({
                        title: 'Error en el servidor',
                        text: 'El servidor encontró un error inesperado al procesar la solicitud.',
                        icon: 'error',
                        confirmButtonText: 'Cerrar'
                    });
                    return false;
                }
            });
        }

        function showLoader() {
            if (loader) loader.style.display = 'flex';
        }

        document.addEventListener('submit', function(e) {
            const hasNoLoader = e.target.hasAttribute('data-no-loader');
            const hasWireSubmit = Array.from(e.target.attributes).some(function(a) {
                return a.name.startsWith('wire:submit');
            });
            if (!hasNoLoader && !hasWireSubmit) showLoader();
        });

        document.addEventListener('click', function(e) {
            var target = e.target.closest('a');
            if (!target) return;
            var dt = target.getAttribute('data-toggle');
            if (dt === 'dropdown' || dt === 'tab' || dt === 'pill') return;
            if (target.href && !target.href.endsWith('#') && target.target !== '_blank' && !target.hasAttribute('data-no-loader')) {
                showLoader();
            }
        });

        window.addEventListener('pageshow', function(event) {
            if (event.persisted && loader) loader.style.display = 'none';
        });
    });

    // --- SweetAlert Listeners ---
    window.addEventListener('swal:success', function(event) {
        var Toast = Swal.mixin({
            toast: true, position: 'top-end', showConfirmButton: false,
            timer: 4000, timerProgressBar: true,
            didOpen: function(toast) {
                toast.addEventListener('mouseenter', Swal.stopTimer);
                toast.addEventListener('mouseleave', Swal.resumeTimer);
            }
        });
        if (typeof event.detail === 'object') {
            Toast.fire({ icon: 'success', title: event.detail.title || '\u00c9xito', text: event.detail.text || '' });
        } else {
            Toast.fire({ icon: 'success', title: event.detail });
        }
    });

    window.addEventListener('show-success-alert', function(event) {
        var Toast = Swal.mixin({
            toast: true, position: 'top-end', showConfirmButton: false,
            timer: 4000, timerProgressBar: true,
            didOpen: function(toast) {
                toast.addEventListener('mouseenter', Swal.stopTimer);
                toast.addEventListener('mouseleave', Swal.resumeTimer);
            }
        });
        Toast.fire({ icon: 'success', title: event.detail.message });
    });

    window.addEventListener('swal:error', function(event) {
        Swal.fire({
            icon: 'error',
            title: event.detail.title,
            text: event.detail.text,
            confirmButtonText: 'Cerrar'
        });
    });

    window.addEventListener('swal:alert', function(event) {
        Swal.fire({
            icon: event.detail.type,
            title: event.detail.title,
            text: event.detail.text,
            confirmButtonText: 'Cerrar'
        }).then(function() {
            if (event.detail.modalToClose) {
                $('#' + event.detail.modalToClose).modal('hide');
            }
        });
    });

    window.addEventListener('swal:toast-error', function(event) {
        var Toast = Swal.mixin({
            toast: true, position: 'top-end', showConfirmButton: false,
            timer: 3000, timerProgressBar: true,
            didOpen: function(toast) {
                toast.addEventListener('mouseenter', Swal.stopTimer);
                toast.addEventListener('mouseleave', Swal.resumeTimer);
            }
        });
        Toast.fire({ icon: 'error', title: event.detail.text });
    });

    window.addEventListener('swal:toast-warning', function(event) {
        var Toast = Swal.mixin({
            toast: true, position: 'top-end', showConfirmButton: false,
            timer: 3000, timerProgressBar: true,
            didOpen: function(toast) {
                toast.addEventListener('mouseenter', Swal.stopTimer);
                toast.addEventListener('mouseleave', Swal.resumeTimer);
            }
        });
        Toast.fire({ icon: 'warning', title: event.detail.text });
    });

    window.addEventListener('show-modal', function(event) {
        var modalId = event.detail.id;
        if (modalId) $('#' + modalId).modal('show');
    });

    window.addEventListener('hide-modal', function(event) {
        var modalId = event.detail.id;
        if (modalId) $('#' + modalId).modal('hide');
    });

    window.addEventListener('swal:confirm', function(event) {
        Swal.fire({
            title: event.detail.title,
            text: event.detail.text,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: event.detail.confirmButtonText || 'S\u00ed, acepto!',
            cancelButtonText: event.detail.cancelButtonText || 'Cancelar'
        }).then(function(result) {
            if (result.isConfirmed) {
                if (event.detail.componentId) {
                    Livewire.find(event.detail.componentId).call(event.detail.method, event.detail.id);
                } else {
                    window.livewire.emit(event.detail.method, event.detail.id);
                }
            }
        });
    });

    window.addEventListener('swal:confirm-with-input', function(event) {
        Swal.fire({
            title: event.detail.title,
            text: event.detail.text,
            icon: 'warning',
            input: event.detail.input || 'text',
            inputLabel: event.detail.inputLabel,
            inputPlaceholder: event.detail.inputPlaceholder,
            inputValidator: event.detail.inputValidator ? new Function('return ' + event.detail.inputValidator)() : null,
            inputAttributes: event.detail.inputAttributes || {},
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: event.detail.confirmButtonText || 'S\u00ed, aceptar',
            cancelButtonText: event.detail.cancelButtonText || 'Cancelar'
        }).then(function(result) {
            if (result.isConfirmed) {
                if (event.detail.componentId) {
                    Livewire.find(event.detail.componentId).call(event.detail.method, result.value);
                } else {
                    window.livewire.emit(event.detail.method, result.value);
                }
            }
        });
    });

    // --- data-swal-confirm global handler ---
    document.addEventListener('click', function(e) {
        var trigger = e.target.closest('[data-swal-confirm]');
        if (!trigger) return;

        e.preventDefault();
        var data = trigger.dataset;

        Swal.fire({
            title: data.swalTitle || '\u00bfEst\u00e1s seguro?',
            text: data.swalText || '\u00a1No podr\u00e1s revertir esto!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: data.swalConfirmBtn || 'S\u00ed, eliminar',
            cancelButtonText: 'Cancelar'
        }).then(function(result) {
            if (result.isConfirmed) {
                window.livewire.emit(data.swalMethod, data.swalId);
            }
        });
    });

    // --- openInNewTab ---
    window.addEventListener('openInNewTab', function(event) {
        window.open(event.detail, '_blank');
    });

    document.addEventListener('livewire:load', function() {
        window.livewire.on('openInNewTab', function(url) {
            window.open(url, '_blank');
        });
    });

    // --- Backup AJAX (respaldo) ---
    $(document).on('click', '#btn-crear-respaldo-menu', function(e) {
        e.preventDefault();
        Swal.fire({
            title: '\u00bfCrear nuevo respaldo?',
            text: 'Esto puede tardar unos minutos. \u00bfDesea continuar?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'S\u00ed, crear respaldo',
            cancelButtonText: 'Cancelar'
        }).then(function(result) {
            if (result.isConfirmed) {
                var loader = document.getElementById('loader');
                if (loader) loader.style.display = 'flex';

                $.ajax({
                    url: '/system/backups/create',
                    method: 'GET',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(data) {
                        if (loader) loader.style.display = 'none';
                        Swal.fire({
                            icon: 'success',
                            title: 'Respaldo creado',
                            text: data.message || 'El respaldo se ha creado correctamente.',
                            confirmButtonText: 'Aceptar'
                        }).then(function() {
                            if (window.location.pathname.includes('/system/backups')) {
                                window.location.reload();
                            }
                        });
                    },
                    error: function(xhr) {
                        if (loader) loader.style.display = 'none';
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: (xhr.responseJSON && xhr.responseJSON.message) || 'Ocurri\u00f3 un error al crear el respaldo.',
                            confirmButtonText: 'Aceptar'
                        });
                    }
                });
            }
        });
    });

})();
