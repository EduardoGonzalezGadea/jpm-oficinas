/**
 * Fix para eventos wire:click que no se registran
 * Solución SIMPLIFICADA usando Bootstrap 4 nativo
 */

(function() {
    'use strict';

    function initLivewireClickFix() {
        console.log('🔧 Livewire Click Fix: Inicializando...');

        // Esperar a que Livewire esté listo
        var checkInterval = setInterval(function() {
            if (typeof Livewire !== 'undefined' && Livewire.all().length > 0) {
                clearInterval(checkInterval);
                setupClickHandlers();
            }
        }, 100);

        // Timeout después de 5 segundos
        setTimeout(function() {
            clearInterval(checkInterval);
        }, 5000);
    }

    function setupClickHandlers() {
        console.log('🔧 Configurando manejadores de clic...');

        // Buscar todos los elementos con wire:click
        var elements = document.querySelectorAll('[wire\\:click]');
        console.log('🔧 Elementos con wire:click encontrados:', elements.length);

        elements.forEach(function(element) {
            var wireClick = element.getAttribute('wire:click');
            
            // Solo agregar si no tiene ya un listener
            if (!element.dataset.livewireClickFixed) {
                element.dataset.livewireClickFixed = 'true';
                
                element.addEventListener('click', function(e) {
                    // NO prevenir default - dejar que Livewire maneje si puede
                    
                    // Obtener el componente Livewire
                    var component = Livewire.all()[0];
                    
                    if (component && component.$wire) {
                        try {
                            // Extraer el nombre del método y parámetros
                            var match = wireClick.match(/^([a-zA-Z0-9_$]+)(\((.*)\))?$/);
                            
                            if (match) {
                                var methodName = match[1];
                                var params = match[3] ? match[3].split(',').map(function(p) {
                                    return p.trim().replace(/['"]/g, '');
                                }) : [];
                                
                                // Solo interceptar si es un método que sabemos que falla
                                if (methodName === 'openPrintModal' || methodName === 'edit' || methodName === 'showDetails' || methodName === 'confirmDelete') {
                                    e.preventDefault();
                                    console.log('🔧 Ejecutando:', methodName);
                                    
                                    // Llamar el método
                                    if (params.length > 0) {
                                        component.$wire[methodName](...params);
                                    } else {
                                        component.$wire[methodName]();
                                    }
                                }
                            }
                        } catch (error) {
                            console.error('✗ Error:', error);
                        }
                    }
                });
            }
        });
        
        // Configurar botones de cierre de modales
        setupModalCloseHandlers();
    }
    
    function setupModalCloseHandlers() {
        // Buscar botones con data-close-modal
        var closeButtons = document.querySelectorAll('[data-close-modal]');
        
        closeButtons.forEach(function(button) {
            if (!button.dataset.closeHandlerFixed) {
                button.dataset.closeHandlerFixed = 'true';
                
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    var modalType = button.getAttribute('data-close-modal');
                    console.log('🔧 Cerrando modal tipo:', modalType);
                    
                    // Cerrar visualmente
                    var modal = button.closest('.modal');
                    if (modal) {
                        modal.style.display = 'none';
                        modal.classList.remove('show');
                    }
                    
                    // Remover backdrops
                    var backdrops = document.querySelectorAll('.modal-backdrop');
                    backdrops.forEach(function(bd) {
                        if (bd.parentNode) bd.parentNode.removeChild(bd);
                    });
                    
                    document.body.classList.remove('modal-open');
                    
                    // Actualizar estado Livewire
                    setTimeout(function() {
                        var component = Livewire.all()[0];
                        if (component && component.$wire) {
                            if (modalType === 'print') {
                                component.$wire.showPrintModal = false;
                            } else if (modalType === 'detail') {
                                component.$wire.showDetailModal = false;
                            } else if (modalType === 'edit') {
                                component.$wire.showModal = false;
                            } else if (modalType === 'delete') {
                                component.$wire.showDeleteModal = false;
                            }
                            console.log('✓ Modal cerrado');
                        }
                    }, 100);
                });
            }
        });
        
        // Cerrar con backdrop
        var backdrops = document.querySelectorAll('.modal-backdrop');
        backdrops.forEach(function(backdrop) {
            if (!backdrop.dataset.backdropHandlerFixed) {
                backdrop.dataset.backdropHandlerFixed = 'true';
                
                backdrop.addEventListener('click', function() {
                    console.log('🔧 Clic en backdrop');
                    var modals = document.querySelectorAll('.modal.show');
                    modals.forEach(function(m) {
                        m.style.display = 'none';
                        m.classList.remove('show');
                    });
                    
                    document.querySelectorAll('.modal-backdrop').forEach(function(bd) {
                        if (bd.parentNode) bd.parentNode.removeChild(bd);
                    });
                    
                    document.body.classList.remove('modal-open');
                    
                    setTimeout(function() {
                        var component = Livewire.all()[0];
                        if (component && component.$wire) {
                            component.$wire.showPrintModal = false;
                            component.$wire.showModal = false;
                            component.$wire.showDetailModal = false;
                            component.$wire.showDeleteModal = false;
                        }
                    }, 100);
                });
            }
        });
    }
    
    // Re-inicializar después de cada actualización de Livewire
    document.addEventListener('livewire:init', function() {
        console.log('🔧 Livewire inicializado');
        
        Livewire.hook('commit', function({ component, succeed }) {
            succeed(function() {
                // Re-escanear después de cada actualización
                setTimeout(function() {
                    setupClickHandlers();
                }, 100);
            });
        });
        
        // Configurar handlers iniciales
        setTimeout(function() {
            setupClickHandlers();
        }, 500);
    });

    // Iniciar cuando el DOM esté listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initLivewireClickFix);
    } else {
        initLivewireClickFix();
    }

})();
