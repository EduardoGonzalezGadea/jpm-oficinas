/**
 * Este script se ejecuta automáticamente en las páginas de la aplicación
 * para indicar que la extensión está instalada.
 */
(function () {
    // Añadir un atributo al elemento HTML para que la web lo detecte
    document.documentElement.setAttribute('data-cfe-extension-installed', 'true');

    // También podemos despachar un evento personalizado por si la web 
    // está escuchando activamente
    window.dispatchEvent(new CustomEvent('cfe-extension-detected'));

    console.log("Extensión CFE Detectada e inyectada en la página.");
})();
