(function() {
    'use strict';

// Helper para leer event.detail compatible con Livewire v2 y v3.
  // En v3, dispatch('event', key: val) llega como event.detail = [{key: val}].
  // En v2 llegaba como event.detail = {key: val}.
  function detail(event) {
    var d = event.detail;
    return (Array.isArray(d) && d.length > 0) ? d[0] : (d || {});
  }

  // Helper global para las vistas: des-envuelve el payload de un evento Livewire
  // sin importar si llega envuelto (v3) u objeto directo (v2).
  window.LiveEvent = function (event) {
    return detail(event);
  };

    // --- Loader (solo para formularios y links normales, no Livewire) ---
    document.addEventListener('DOMContentLoaded', function() {
        var loader = document.getElementById('loader');

        window.addEventListener('hide-loader', function() {
            if (loader) loader.style.display = 'none';
        });

        function showLoader() {
            if (loader) loader.style.display = 'flex';
        }

        document.addEventListener('submit', function(e) {
            var hasNoLoader = e.target.hasAttribute('data-no-loader');
            var hasWireSubmit = Array.from(e.target.attributes).some(function(a) {
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

    // --- Livewire v3: hook para manejo de errores HTTP ---
    // Livewire v3 no tiene onError(); se usa el hook 'request' para capturar fallos.
    document.addEventListener('livewire:init', function() {
        Livewire.hook('request', function(ref) {
            var fail = ref.fail;
            if (typeof fail === 'function') {
                fail(function(ref2) {
                    var status = ref2.status;
                    var content = ref2.content;
                    var preventDefault = ref2.preventDefault;

                    if (window.isSessionExpiredResponse && window.isSessionExpiredResponse(status, content)) {
                        var payload = {};
                        try { payload = typeof content === 'string' ? JSON.parse(content) : (content || {}); } catch(e) {}
                        window.handleSessionExpired({
                            message: payload.message || payload.error || undefined,
                            redirect: payload.redirect || null,
                        });
                        if (typeof preventDefault === 'function') preventDefault();
                        return;
                    }

                    if (status === 500) {
                        Swal.fire({
                            title: 'Error en el servidor',
                            text: 'El servidor encontró un error inesperado al procesar la solicitud.',
                            icon: 'error',
                            confirmButtonText: 'Cerrar'
                        });
                        if (typeof preventDefault === 'function') preventDefault();
                    }
                });
            }
        });

        // Escuchar eventos de modal via Livewire.on (recibe params directamente en v3)
        Livewire.on('show-modal', function(params) {
            var modalId = (Array.isArray(params) ? params[0] : params)?.id
                       || (Array.isArray(params) ? params[0] : params)?.modal
                       || (typeof params === 'string' ? params : null);
            if (modalId) {
                var id = modalId.replace(/^#/, '');
                $('#' + id).modal('show');
            }
        });

        Livewire.on('hide-modal', function(params) {
            var modalId = (Array.isArray(params) ? params[0] : params)?.id
                       || (typeof params === 'string' ? params : null);
            if (modalId) {
                var id = modalId.replace(/^#/, '');
                $('#' + id).modal('hide');
            }
        });

        // openInNewTab via Livewire.on (v3)
        Livewire.on('openInNewTab', function(data) {
            var url = Array.isArray(data) ? data[0] : data;
            if (url) window.open(url, '_blank');
        });
    });

    // --- SweetAlert Listeners ---
    window.addEventListener('swal:success', function(event) {
        var d = detail(event);
        var Toast = Swal.mixin({
            toast: true, position: 'top-end', showConfirmButton: false,
            timer: 4000, timerProgressBar: true,
            didOpen: function(toast) {
                toast.addEventListener('mouseenter', Swal.stopTimer);
                toast.addEventListener('mouseleave', Swal.resumeTimer);
            }
        });
        if (d && typeof d === 'object') {
            Toast.fire({ icon: 'success', title: d.title || 'Éxito', text: d.text || '' });
        } else {
            Toast.fire({ icon: 'success', title: event.detail });
        }
    });

    window.addEventListener('show-success-alert', function(event) {
        var d = detail(event);
        var Toast = Swal.mixin({
            toast: true, position: 'top-end', showConfirmButton: false,
            timer: 4000, timerProgressBar: true,
            didOpen: function(toast) {
                toast.addEventListener('mouseenter', Swal.stopTimer);
                toast.addEventListener('mouseleave', Swal.resumeTimer);
            }
        });
        Toast.fire({ icon: 'success', title: d.message || '' });
    });

    window.addEventListener('swal:error', function(event) {
        var d = detail(event);
        Swal.fire({
            icon: 'error',
            title: d.title || 'Error',
            text: d.text || '',
            confirmButtonText: 'Cerrar'
        });
    });

    window.addEventListener('swal:alert', function(event) {
        var d = detail(event);
        Swal.fire({
            icon: d.type || 'info',
            title: d.title || '',
            text: d.text || '',
            confirmButtonText: 'Cerrar'
        }).then(function() {
            if (d.modalToClose) {
                $('#' + d.modalToClose).modal('hide');
            }
        });
    });

    window.addEventListener('swal:toast-error', function(event) {
        var d = detail(event);
        var Toast = Swal.mixin({
            toast: true, position: 'top-end', showConfirmButton: false,
            timer: 3000, timerProgressBar: true,
            didOpen: function(toast) {
                toast.addEventListener('mouseenter', Swal.stopTimer);
                toast.addEventListener('mouseleave', Swal.resumeTimer);
            }
        });
        Toast.fire({ icon: 'error', title: d.text || '' });
    });

    window.addEventListener('swal:toast-warning', function(event) {
        var d = detail(event);
        var Toast = Swal.mixin({
            toast: true, position: 'top-end', showConfirmButton: false,
            timer: 3000, timerProgressBar: true,
            didOpen: function(toast) {
                toast.addEventListener('mouseenter', Swal.stopTimer);
                toast.addEventListener('mouseleave', Swal.resumeTimer);
            }
        });
        Toast.fire({ icon: 'warning', title: d.text || '' });
    });

    // --- Modal Bootstrap 4 via Livewire dispatch ---
    window.addEventListener('show-modal', function(event) {
        var d = detail(event);
        var modalId = d.id || (typeof event.detail === 'string' ? event.detail : null);
        if (modalId) $('#' + modalId).modal('show');
    });

    window.addEventListener('hide-modal', function(event) {
        var d = detail(event);
        var modalId = d.id || (typeof event.detail === 'string' ? event.detail : null);
        if (modalId) $('#' + modalId).modal('hide');
    });

    // --- swal:confirm ---
    window.addEventListener('swal:confirm', function(event) {
        var d = detail(event);
        Swal.fire({
            title: d.title || '¿Estás seguro?',
            text: d.text || '¡No podrás revertir esto!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: d.confirmButtonText || 'Sí, acepto!',
            cancelButtonText: d.cancelButtonText || 'Cancelar'
        }).then(function(result) {
            if (result.isConfirmed) {
                if (d.componentId) {
                    Livewire.find(d.componentId).call(d.method, d.id);
                } else {
                    // v3: Livewire.dispatch en lugar de window.livewire.emit
                    Livewire.dispatch(d.method, { id: d.id });
                }
            }
        });
    });

    // --- swal:confirm-with-input ---
    window.addEventListener('swal:confirm-with-input', function(event) {
        var d = detail(event);
        Swal.fire({
            title: d.title || '',
            text: d.text || '',
            icon: 'warning',
            input: d.input || 'text',
            inputValue: d.inputValue || '',
            inputLabel: d.inputLabel || '',
            inputPlaceholder: d.inputPlaceholder || '',
            inputValidator: d.inputValidator ? new Function('return ' + d.inputValidator)() : null,
            inputAttributes: d.inputAttributes || {},
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: d.confirmButtonText || 'Sí, aceptar',
            cancelButtonText: d.cancelButtonText || 'Cancelar'
        }).then(function(result) {
            if (result.isConfirmed) {
                if (d.componentId) {
                    Livewire.find(d.componentId).call(d.method, result.value);
                } else {
                    Livewire.dispatch(d.method, { value: result.value });
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
            title: data.swalTitle || '¿Estás seguro?',
            text: data.swalText || '¡No podrás revertir esto!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: data.swalConfirmBtn || 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then(function(result) {
            if (result.isConfirmed) {
                // v3: usar Livewire.dispatch en lugar de window.livewire.emit
                Livewire.dispatch(data.swalMethod, { id: data.swalId });
            }
        });
    });

    // --- openInNewTab vía window event (compatibilidad) ---
    window.addEventListener('openInNewTab', function(event) {
        var d = detail(event);
        var url = d.url || (typeof event.detail === 'string' ? event.detail : null);
        if (url) window.open(url, '_blank');
    });

        // --- Backup AJAX (respaldo) ---
    document.addEventListener('DOMContentLoaded', function() {
        var btnRespaldo = document.getElementById('btn-crear-respaldo-menu');
        
        // Fix for Chrome warning: Blocked aria-hidden on an element because its descendant retained focus
        $(document).on('hide.bs.modal', '.modal', function () {
            if (document.activeElement) {
                document.activeElement.blur();
            }
        });

        if (!btnRespaldo) return;

        btnRespaldo.addEventListener('click', function(e) {
            e.preventDefault();
            Swal.fire({
                title: '¿Crear nuevo respaldo?',
                text: 'Esto puede tardar unos minutos. ¿Desea continuar?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, crear respaldo',
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
                                text: (xhr.responseJSON && xhr.responseJSON.message) || 'Ocurrió un error al crear el respaldo.',
                                confirmButtonText: 'Aceptar'
                            });
                        }
                    });
                }
            });
        });
    });

    // Livewire v3 dispatches PHP events as window events: $this->dispatch('show-modal') -> window 'show-modal'
    window.addEventListener('show-modal', function(event) {
        var params = event.detail;
        var p0 = Array.isArray(params) ? params[0] : params;
        var modalId = (p0 && p0.id) ? p0.id : ((p0 && p0.modal) ? p0.modal : (typeof params === 'string' ? params : null));
        
        if (typeof modalId === 'string') {
            var id = modalId.replace(/^#/, '');
            // setTimeout ensures Livewire has finished DOM morphing before Bootstrap modifies the DOM
            setTimeout(function() {
                $('#' + id).modal('show');
            }, 50);
        }
    });

    window.addEventListener('hide-modal', function(event) {
        var params = event.detail;
        var p0 = Array.isArray(params) ? params[0] : params;
        var modalId = (p0 && p0.id) ? p0.id : (typeof params === 'string' ? params : null);
        
        if (typeof modalId === 'string') {
            var id = modalId.replace(/^#/, '');
            setTimeout(function() {
                $('#' + id).modal('hide');
            }, 50);
        }
    });

})();
