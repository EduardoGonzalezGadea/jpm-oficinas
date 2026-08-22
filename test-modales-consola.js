// ========================================
// SCRIPT DE PRUEBA - EJECUTAR EN CONSOLA
// ========================================
// Copia y pega TODO este código en la consola del navegador (F12)

console.log('=== DIAGNÓSTICO COMPLETO DE MODALES ===\n');

// 1. Verificar dependencias básicas
console.log('1. DEPENDENCIAS:');
console.log('   jQuery:', typeof $, '- Versión:', $.fn ? $.fn.jquery : 'N/A');
console.log('   Bootstrap Modal:', typeof $.fn.modal);
console.log('   ModalHelper:', typeof ModalHelper);
console.log('   Livewire:', typeof Livewire);
console.log('   Alpine:', typeof Alpine);
console.log('');

// 2. Verificar modales en el DOM
console.log('2. MODALES EN DOM:');
var $modales = $('.modal');
console.log('   Total modales encontrados:', $modales.length);
$modales.each(function(i) {
    var id = $(this).attr('id') || 'sin-id';
    var display = $(this).css('display');
    var classes = $(this).attr('class');
    console.log('   Modal #' + (i+1) + ':', id, '- Display:', display, '- Classes:', classes.split(' ').slice(0, 3).join(' '));
});
console.log('');

// 3. Verificar backdrops
console.log('3. BACKDROPS:');
console.log('   Backdrops activos:', $('.modal-backdrop').length);
console.log('   Body modal-open:', $('body').hasClass('modal-open'));
console.log('');

// 4. Test de apertura manual
console.log('4. TEST DE APERTURA MANUAL:');
console.log('   Intentando abrir modal manualmente...');

// Buscar el primer modal disponible
var primeraId = $modales.first().attr('id');
if (primeraId) {
    console.log('   Modal a probar:', primeraId);
    
    // Método 1: Bootstrap directo
    try {
        $('#' + primeraId).modal('show');
        setTimeout(function() {
            var estaVisible = $('#' + primeraId).hasClass('show');
            var display = $('#' + primeraId).css('display');
            console.log('   ✓ Bootstrap directo - Visible:', estaVisible, '- Display:', display);
            
            // Cerrar
            $('#' + primeraId).modal('hide');
        }, 1000);
    } catch(e) {
        console.error('   ✗ Error con Bootstrap directo:', e.message);
    }
    
    // Método 2: ModalHelper
    setTimeout(function() {
        try {
            if (typeof ModalHelper !== 'undefined') {
                ModalHelper.show(primeraId);
                setTimeout(function() {
                    var estaVisible = $('#' + primeraId).hasClass('show');
                    console.log('   ✓ ModalHelper - Visible:', estaVisible);
                    ModalHelper.hide(primeraId);
                }, 2000);
            } else {
                console.log('   ⚠ ModalHelper no disponible');
            }
        } catch(e) {
            console.error('   ✗ Error con ModalHelper:', e.message);
        }
    }, 1500);
} else {
    console.log('   ⚠ No se encontró ningún modal para probar');
}
console.log('');

// 5. Verificar componentes Livewire
console.log('5. COMPONENTES LIVEWIRE:');
if (typeof Livewire !== 'undefined') {
    try {
        var componentes = Livewire.all();
        console.log('   Total componentes:', componentes.length);
        componentes.slice(0, 3).forEach(function(c, i) {
            var nombre = c.__instance.fingerprint.name;
            console.log('   Componente #' + (i+1) + ':', nombre);
        });
    } catch(e) {
        console.log('   ⚠ No se pudieron listar componentes:', e.message);
    }
} else {
    console.log('   ⚠ Livewire no está disponible');
}
console.log('');

// 6. Verificar eventos
console.log('6. LISTENERS DE EVENTOS:');
console.log('   Event show-modal:', 'registrado (ver ModalHelper)');
console.log('   Event hide-modal:', 'registrado (ver ModalHelper)');
console.log('');

// 7. Información del navegador
console.log('7. NAVEGADOR:');
console.log('   UserAgent:', navigator.userAgent);
console.log('   Versión Chrome:', navigator.userAgent.match(/Chrome\/(\d+)/)?.[1] || 'N/A');
console.log('');

console.log('=== FIN DEL DIAGNÓSTICO ===');
console.log('');
console.log('PRÓXIMOS PASOS:');
console.log('1. Si viste un modal abrirse y cerrarse, el sistema funciona');
console.log('2. Si no se abrió nada, copia TODA esta salida y compártela');
console.log('3. Intenta hacer clic en "Editar" de una multa y reporta qué pasa');
