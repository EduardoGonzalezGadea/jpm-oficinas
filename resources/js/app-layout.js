(function() {
    'use strict';

    // Helper para leer event.detail compatible con Livewire v2, v3 y v4.
    function detail(event) {
        if (!event) return {};
        var d = event.detail !== undefined ? event.detail : event;
        return (Array.isArray(d) && d.length > 0) ? d[0] : (d || {});
    }

    // Helper global para las vistas: des-envuelve el payload de un evento Livewire
    window.LiveEvent = function (event) {
        return detail(event);
    };

    // --- Fix para compatibilidad entre Bootstrap 4 Modal y SweetAlert2 ---
    // Evita que el mecanismo _enforceFocus de Bootstrap 4 secuestre el foco cuando SweetAlert2 está visible.
    if (window.jQuery && window.jQuery.fn && window.jQuery.fn.modal && window.jQuery.fn.modal.Constructor) {
        var proto = window.jQuery.fn.modal.Constructor.prototype;
        proto._enforceFocus = function () {
            var self = this;
            $(document)
                .off('focusin.bs.modal')
                .on('focusin.bs.modal', function (event) {
                    // Si el foco está dentro de un contenedor de SweetAlert2, no intervenir
                    if ($(event.target).closest('.swal2-container').length) {
                        return;
                    }
                    if (document !== event.target &&
                        self._element !== event.target &&
                        !$(self._element).has(event.target).length) {
                        self._element.focus();
                    }
                });
        };
    }

    // --- Manejo seguro de apertura y cierre de modales de Bootstrap ---
    function safeShowModal(modalId) {
        if (!modalId) return;
        var id = String(modalId).replace(/^#/, '');
        var $el = $('#' + id);
        if (!$el.length) return;

        if ($('.modal.show').length === 0) {
            $('.modal-backdrop').remove();
        }

        var bsModal = $el.data('bs.modal');
        if (bsModal && bsModal._isTransitioning) {
            bsModal._isTransitioning = false;
        }

        setTimeout(function() {
            if (!$el.hasClass('show')) {
                $el.modal('show');
            }
        }, 20);
    }

    function safeHideModal(modalId) {
        if (!modalId) {
            $('.modal.show').each(function() {
                var bsModal = $(this).data('bs.modal');
                if (bsModal && bsModal._isTransitioning) {
                    bsModal._isTransitioning = false;
                }
                $(this).modal('hide');
            });
            setTimeout(function() {
                if ($('.modal.show').length === 0) {
                    $('.modal-backdrop').remove();
                    $('body').removeClass('modal-open').css('padding-right', '');
                }
            }, 100);
            return;
        }
        var id = String(modalId).replace(/^#/, '');
        var $el = $('#' + id);
        if (!$el.length) return;

        var bsModal = $el.data('bs.modal');
        if (bsModal && bsModal._isTransitioning) {
            bsModal._isTransitioning = false;
        }

        setTimeout(function() {
            if ($el.hasClass('show') || $el.is(':visible')) {
                $el.modal('hide');
            }
            setTimeout(function() {
                if ($('.modal.show').length === 0) {
                    $('.modal-backdrop').remove();
                    $('body').removeClass('modal-open').css('padding-right', '');
                }
            }, 100);
        }, 20);
    }

    // Limpieza global de backdrops y restauración de scroll al cerrar modales
    $(document).on('hidden.bs.modal', '.modal', function () {
        if (document.activeElement) {
            document.activeElement.blur();
        }
        // Si aún queda otro modal abierto, mantener la clase modal-open
        setTimeout(function() {
            if ($('.modal.show').length > 0) {
                $('body').addClass('modal-open');
            } else {
                $('body').removeClass('modal-open').css('padding-right', '');
                $('.modal-backdrop').remove();
            }
        }, 50);
    });

    // Limpieza al navegar entre rutas con Livewire
    document.addEventListener('livewire:navigating', function () {
        $('.modal').modal('hide');
        $('.modal-backdrop').remove();
    });

    // Limpieza de backdrop para modales declarativos de Livewire
    // Los modales @if no disparan hidden.bs.modal, por lo que debemos
    // observar el DOM y limpiar cuando el modal desaparece.
    (function() {
        var cleanupTimer = null;
        var observer = new MutationObserver(function() {
            clearTimeout(cleanupTimer);
            cleanupTimer = setTimeout(function() {
                // Si no hay ningún modal visible ni abriéndose
                var hasVisibleModal = $('.modal.show').length > 0 || 
                                     document.querySelectorAll('.modal[style*="display: block"]').length > 0;
                
                if (!hasVisibleModal) {
                    var hasBackdrops = document.querySelectorAll('.modal-backdrop').length > 0;
                    var hasModalOpen = document.body.classList.contains('modal-open');
                    if (hasBackdrops || hasModalOpen) {
                        $('.modal-backdrop').remove();
                        $('body').removeClass('modal-open').css('padding-right', '');
                    }
                }
            }, 300);
        });
        observer.observe(document.body, { childList: true, subtree: true });
    })();

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

    // --- Livewire: hook para manejo de errores HTTP y listeners de modales ---
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

        // openInNewTab via Livewire.on
        Livewire.on('openInNewTab', function(data) {
            var url = Array.isArray(data) ? data[0] : data;
            if (url) window.open(url, '_blank');
        });

        // Listeners de modales registrados directamente en Livewire
        Livewire.on('show-modal', function(data) {
            var d = Array.isArray(data) ? (data[0] || {}) : (data || {});
            var modalId = d.id || d.modal || (typeof d === 'string' ? d : null);
            safeShowModal(modalId);
        });

        Livewire.on('hide-modal', function(data) {
            var d = Array.isArray(data) ? (data[0] || {}) : (data || {});
            var modalId = d.id || d.modal || (typeof d === 'string' ? d : null);
            safeHideModal(modalId);
        });

        Livewire.on('close-modal', function(data) {
            var d = Array.isArray(data) ? (data[0] || {}) : (data || {});
            var modalId = d.id || d.modal || (typeof d === 'string' ? d : null);
            safeHideModal(modalId);
        });

        Livewire.on('showEditarModal', function() { safeShowModal('editarChequeModal'); });
        Livewire.on('hideEditarModal', function() { safeHideModal('editarChequeModal'); });
        Livewire.on('cajaConceptoStore', function() { safeHideModal('cajaConceptoModal'); });
        Livewire.on('cajaConceptoUpdate', function() { safeHideModal('cajaConceptoModal'); });
        Livewire.on('eventualStore', function() { safeHideModal('eventualModal'); });
        Livewire.on('eventualUpdate', function() { safeHideModal('eventualModal'); safeHideModal('ingresoModal'); });
        Livewire.on('chequeEmitido', function() { safeHideModal('emitirChequeModal'); });
        Livewire.on('chequeAnulado', function() { safeHideModal('anularChequeModal'); });
        Livewire.on('chequeEditado', function() { safeHideModal('editarChequeModal'); });
        Livewire.on('bancoStore', function() { safeHideModal('modalBanco'); safeHideModal('modal'); });
        Livewire.on('bancoUpdate', function() { safeHideModal('modalBanco'); safeHideModal('modal'); });
        Livewire.on('cuentaStore', function() { safeHideModal('modalCuenta'); safeHideModal('modal'); });
        Livewire.on('cuentaUpdate', function() { safeHideModal('modalCuenta'); safeHideModal('modal'); });
    });

    // --- Escuchadores de eventos para Modales (Window Custom Events) ---
    window.addEventListener('show-modal', function(event) {
        var d = detail(event);
        var modalId = d.id || d.modal || (typeof d === 'string' ? d : null);
        safeShowModal(modalId);
    });

    window.addEventListener('hide-modal', function(event) {
        var d = detail(event);
        var modalId = d.id || d.modal || (typeof d === 'string' ? d : null);
        safeHideModal(modalId);
    });

    window.addEventListener('close-modal', function(event) {
        var d = detail(event);
        var modalId = d.id || d.modal || (typeof d === 'string' ? d : null);
        safeHideModal(modalId);
    });

    // Compatibilidad con eventos clásicos de CRUD
    window.addEventListener('itemStore', function() {
        safeHideModal('modal');
    });

    window.addEventListener('itemUpdated', function() {
        safeHideModal('modal');
    });

    window.addEventListener('itemDeleted', function() {
        safeHideModal('modal');
    });

    // --- SweetAlert2: Listener genérico 'swal' (compatibilidad con componentes legacy) ---
    // Usado por Cheques, Valores, CajaChica y otros que hacen dispatch('swal', ...)
    window.addEventListener('swal', function(event) {
        var d = detail(event);
        var isToast = d.toast === true;
        var icon = d.icon || d.type || 'info';
        var title = d.title || '';
        var text = d.text || d.message || '';

        if (isToast) {
            var Toast = Swal.mixin({
                toast: true,
                position: d.position || 'top-end',
                showConfirmButton: d.showConfirmButton !== undefined ? d.showConfirmButton : false,
                timer: d.timer || 3000,
                timerProgressBar: true,
                didOpen: function(toast) {
                    toast.addEventListener('mouseenter', Swal.stopTimer);
                    toast.addEventListener('mouseleave', Swal.resumeTimer);
                }
            });
            Toast.fire({ icon: icon, title: title || text, text: title ? text : '' });
        } else {
            Swal.fire({
                icon: icon,
                title: title || (icon === 'error' ? 'Error' : icon === 'success' ? 'Éxito' : icon === 'warning' ? 'Advertencia' : 'Aviso'),
                text: text,
                confirmButtonText: 'Cerrar',
                confirmButtonColor: icon === 'error' ? '#d33' : '#3085d6'
            });
        }
    });

    // --- SweetAlert2: Listener Global para 'alert' (Toast / Modal) ---

    window.addEventListener('alert', function(event) {
        var d = detail(event);
        var type = d.type || 'info';
        var message = d.message || d.title || '';
        var isToast = d.toast !== false; // por defecto toast

        if (isToast) {
            var Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3500,
                timerProgressBar: true,
                didOpen: function(toast) {
                    toast.addEventListener('mouseenter', Swal.stopTimer);
                    toast.addEventListener('mouseleave', Swal.resumeTimer);
                }
            });
            Toast.fire({
                icon: type,
                title: message,
            });
        } else {
            Swal.fire({
                icon: type,
                title: d.title || (type === 'error' ? 'Error' : 'Aviso'),
                text: message,
                confirmButtonText: 'Aceptar',
                confirmButtonColor: '#3085d6'
            });
        }
    });

    // --- SweetAlert Listeners Complementarios ---
    window.addEventListener('swal:success', function(event) {
        var d = detail(event);
        var Toast = Swal.mixin({
            toast: true, position: 'top-end', showConfirmButton: false,
            timer: 3500, timerProgressBar: true,
            didOpen: function(toast) {
                toast.addEventListener('mouseenter', Swal.stopTimer);
                toast.addEventListener('mouseleave', Swal.resumeTimer);
            }
        });
        if (d && typeof d === 'object') {
            Toast.fire({ icon: 'success', title: d.title || 'Éxito', text: d.text || '' });
        } else {
            Toast.fire({ icon: 'success', title: String(d) });
        }
    });

    window.addEventListener('show-success-alert', function(event) {
        var d = detail(event);
        var Toast = Swal.mixin({
            toast: true, position: 'top-end', showConfirmButton: false,
            timer: 3500, timerProgressBar: true,
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
            text: d.text || d.message || '',
            confirmButtonText: 'Cerrar',
            confirmButtonColor: '#d33'
        });
    });

    window.addEventListener('swal:alert', function(event) {
        var d = detail(event);
        Swal.fire({
            icon: d.type || 'info',
            title: d.title || '',
            text: d.text || d.message || '',
            confirmButtonText: 'Cerrar',
            confirmButtonColor: '#3085d6'
        }).then(function() {
            if (d.modalToClose) {
                safeHideModal(d.modalToClose);
            }
        });
    });

    window.addEventListener('swal:toast-error', function(event) {
        var d = detail(event);
        var Toast = Swal.mixin({
            toast: true, position: 'top-end', showConfirmButton: false,
            timer: 4500, timerProgressBar: true,
            didOpen: function(toast) {
                toast.addEventListener('mouseenter', Swal.stopTimer);
                toast.addEventListener('mouseleave', Swal.resumeTimer);
            }
        });
        Toast.fire({ icon: 'error', title: d.text || d.message || 'Error en la operación.' });
    });

    window.addEventListener('swal:toast-success', function(event) {
        var d = detail(event);
        var Toast = Swal.mixin({
            toast: true, position: 'top-end', showConfirmButton: false,
            timer: 3500, timerProgressBar: true,
            didOpen: function(toast) {
                toast.addEventListener('mouseenter', Swal.stopTimer);
                toast.addEventListener('mouseleave', Swal.resumeTimer);
            }
        });
        Toast.fire({ icon: 'success', title: d.text || d.message || 'Operación completada correctamente.' });
    });

    window.addEventListener('swal:modal', function(event) {
        var d = detail(event);
        Swal.fire({
            icon: d.type || 'info',
            title: d.title || 'Información',
            text: d.text || d.message || '',
            confirmButtonText: 'Aceptar',
            confirmButtonColor: '#3085d6'
        });
    });

    window.addEventListener('swal:modal-error', function(event) {
        var d = detail(event);
        Swal.fire({
            icon: 'error',
            title: d.title || 'Error',
            text: d.text || d.message || '',
            confirmButtonText: 'Cerrar',
            confirmButtonColor: '#d33'
        });
    });

    window.addEventListener('swal:warning', function(event) {
        var d = detail(event);
        Swal.fire({
            icon: 'warning',
            title: d.title || 'Advertencia',
            text: d.text || d.message || '',
            confirmButtonText: 'Aceptar',
            confirmButtonColor: '#f39c12'
        });
    });

    window.addEventListener('swal:toast', function(event) {
        var d = detail(event);
        var Toast = Swal.mixin({
            toast: true, position: 'top-end', showConfirmButton: false,
            timer: 3500, timerProgressBar: true,
            didOpen: function(toast) {
                toast.addEventListener('mouseenter', Swal.stopTimer);
                toast.addEventListener('mouseleave', Swal.resumeTimer);
            }
        });
        Toast.fire({
            icon: d.type || d.icon || 'info',
            title: d.text || d.message || d.title || ''
        });
    });

    window.addEventListener('swal:toast-warning', function(event) {
        var d = detail(event);
        var Toast = Swal.mixin({
            toast: true, position: 'top-end', showConfirmButton: false,
            timer: 3500, timerProgressBar: true,
            didOpen: function(toast) {
                toast.addEventListener('mouseenter', Swal.stopTimer);
                toast.addEventListener('mouseleave', Swal.resumeTimer);
            }
        });
        Toast.fire({ icon: 'warning', title: d.text || d.message || '' });
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
                if (d.componentId && window.Livewire) {
                    var comp = Livewire.find(d.componentId);
                    if (comp) comp.call(d.method, d.id);
                } else if (window.Livewire) {
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
                if (d.componentId && window.Livewire) {
                    var comp = Livewire.find(d.componentId);
                    if (comp) comp.call(d.method, result.value);
                } else if (window.Livewire) {
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
            if (result.isConfirmed && window.Livewire) {
                Livewire.dispatch(data.swalMethod, { id: data.swalId });
            }
        });
    });

    // --- Spinner global (usado por PlanillaVer y otros) ---
    window.addEventListener('show-global-spinner', function() {
        var loader = document.getElementById('loader');
        if (loader) loader.style.display = 'flex';
    });

    window.addEventListener('hide-global-spinner', function() {
        var loader = document.getElementById('loader');
        if (loader) loader.style.display = 'none';
    });

    // --- openInNewTab vía window event ---
    window.addEventListener('openInNewTab', function(event) {
        var d = detail(event);
        var url = d.url || (typeof event.detail === 'string' ? event.detail : null);
        if (url) window.open(url, '_blank');
    });

    // --- Backup AJAX (respaldo) ---
    document.addEventListener('DOMContentLoaded', function() {
        var btnRespaldo = document.getElementById('btn-crear-respaldo-menu');
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

})();
