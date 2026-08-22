/**
 * Modal Helper - Compatibilidad con Bootstrap 4.6 y navegadores antiguos
 * Proporciona una API consistente para abrir/cerrar modales desde Livewire
 */

(function() {
    'use strict';

    // Verificar que jQuery y Bootstrap estén cargados
    if (typeof jQuery === 'undefined') {
        console.error('Modal Helper requiere jQuery');
        return;
    }

    if (typeof jQuery.fn.modal === 'undefined') {
        console.error('Modal Helper requiere Bootstrap');
        return;
    }

    var ModalHelper = {
        /**
         * Abre un modal de Bootstrap 4
         * @param {string} modalId - ID del modal (con o sin #)
         */
        show: function(modalId) {
            var id = modalId.startsWith('#') ? modalId : '#' + modalId;
            try {
                $(id).modal('show');
                console.log('Modal abierto:', id);
            } catch (e) {
                console.error('Error al abrir modal:', id, e);
            }
        },

        /**
         * Cierra un modal de Bootstrap 4
         * @param {string} modalId - ID del modal (con o sin #)
         */
        hide: function(modalId) {
            var id = modalId.startsWith('#') ? modalId : '#' + modalId;
            try {
                $(id).modal('hide');
                console.log('Modal cerrado:', id);
            } catch (e) {
                console.error('Error al cerrar modal:', id, e);
            }
        },

        /**
         * Toggle de un modal
         * @param {string} modalId - ID del modal
         */
        toggle: function(modalId) {
            var id = modalId.startsWith('#') ? modalId : '#' + modalId;
            try {
                $(id).modal('toggle');
            } catch (e) {
                console.error('Error al toggle modal:', id, e);
            }
        },

        /**
         * Cierra todos los modales abiertos
         */
        hideAll: function() {
            try {
                $('.modal').modal('hide');
                console.log('Todos los modales cerrados');
            } catch (e) {
                console.error('Error al cerrar modales:', e);
            }
        },

        /**
         * Inicializa los event listeners de Livewire
         */
        initLivewireListeners: function() {
            // Listener para mostrar modales desde Livewire
            window.addEventListener('show-modal', function(event) {
                var modalId = event.detail.id || event.detail;
                ModalHelper.show(modalId);
            });

            // Listener para ocultar modales desde Livewire
            window.addEventListener('hide-modal', function(event) {
                var modalId = event.detail.id || event.detail;
                ModalHelper.hide(modalId);
            });

            // Listener para toggle de modales desde Livewire
            window.addEventListener('toggle-modal', function(event) {
                var modalId = event.detail.id || event.detail;
                ModalHelper.toggle(modalId);
            });

            console.log('✓ Modal Helper: Listeners de Livewire inicializados');
        },

        /**
         * Corrige modales que usan sintaxis de Bootstrap 5
         */
        fixBootstrap5Syntax: function() {
            // Corregir data-bs-toggle a data-toggle
            $('[data-bs-toggle="modal"]').each(function() {
                var $this = $(this);
                var target = $this.attr('data-bs-target');
                $this.attr('data-toggle', 'modal');
                if (target) {
                    $this.attr('data-target', target);
                }
                // No remover los atributos BS5 para mantener compatibilidad
            });

            // Corregir data-bs-dismiss a data-dismiss
            $('[data-bs-dismiss="modal"]').each(function() {
                $(this).attr('data-dismiss', 'modal');
            });

            // Corregir btn-close a close
            $('.btn-close').each(function() {
                var $this = $(this);
                if (!$this.hasClass('close')) {
                    $this.addClass('close');
                }
                // Agregar × si no tiene contenido
                if ($this.html().trim() === '') {
                    $this.html('<span aria-hidden="true">&times;</span>');
                }
            });

            console.log('✓ Modal Helper: Sintaxis Bootstrap 5 corregida');
        },

        /**
         * Inicializa el sistema de modales
         */
        init: function() {
            var self = this;

            // Esperar a que el DOM esté listo
            $(document).ready(function() {
                // Corregir sintaxis BS5
                self.fixBootstrap5Syntax();

                // Inicializar listeners de Livewire
                self.initLivewireListeners();

                // Re-aplicar correcciones después de actualizaciones de Livewire
                if (typeof Livewire !== 'undefined') {
                    Livewire.hook('message.processed', function() {
                        setTimeout(function() {
                            self.fixBootstrap5Syntax();
                        }, 100);
                    });
                }

                // Manejar modales controlados por Alpine.js / x-data
                self.initAlpineModals();

                // Cerrar modales al presionar ESC
                $(document).on('keydown', function(e) {
                    if (e.keyCode === 27) { // ESC
                        $('.modal.show').modal('hide');
                    }
                });

                console.log('✓ Modal Helper: Sistema inicializado');
            });
        },

        /**
         * Inicializa soporte para modales controlados por Alpine.js
         */
        initAlpineModals: function() {
            var self = this;
            
            // Usar un enfoque más ligero que no interfiera con Livewire
            // Solo observar cuando un modal se hace visible o invisible
            document.addEventListener('DOMContentLoaded', function() {
                // Cada 500ms verificar modales visibles
                setInterval(function() {
                    $('.modal').each(function() {
                        var $modal = $(this);
                        var display = $modal.css('display');
                        
                        // Si está visible pero no tiene la clase show
                        if (display === 'block' && !$modal.hasClass('show')) {
                            $modal.addClass('show');
                            $('body').addClass('modal-open');
                            if ($('.modal-backdrop').length === 0) {
                                $('<div class="modal-backdrop fade show"></div>').appendTo('body');
                            }
                        } 
                        // Si no está visible pero tiene la clase show
                        else if (display === 'none' && $modal.hasClass('show')) {
                            $modal.removeClass('show');
                            if ($('.modal.show').length === 0) {
                                $('body').removeClass('modal-open');
                                $('.modal-backdrop').remove();
                            }
                        }
                    });
                }, 500);
            });

            console.log('✓ Modal Helper: Soporte para Alpine.js inicializado');
        }
    };

    // Exponer globalmente
    window.ModalHelper = ModalHelper;

    // Inicializar automáticamente
    ModalHelper.init();

})();
