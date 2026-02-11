var DEFAULT_URL = "http://localhost/oficinas/public";

document.addEventListener("DOMContentLoaded", function () {
    var serverUrlInput = document.getElementById("serverUrl");
    var saveBtn = document.getElementById("saveUrl");
    var autoDetectBtn = document.getElementById("autoDetect");
    var savedMsg = document.getElementById("savedMsg");
    var openAppBtn = document.getElementById("openApp");

    // Cargar URL guardada
    chrome.storage.local.get(["serverUrl"], function (result) {
        serverUrlInput.value = result.serverUrl || DEFAULT_URL;
    });

    // Guardar URL
    saveBtn.addEventListener("click", function () {
        var url = serverUrlInput.value.trim();
        // Quitar barra final si existe
        if (url.endsWith("/")) {
            url = url.slice(0, -1);
        }

        chrome.storage.local.set({ serverUrl: url }, function () {
            savedMsg.style.display = "block";
            setTimeout(function () {
                savedMsg.style.display = "none";
            }, 2000);
        });
    });

    // Detectar automaticamente URL desde la pestaña actual
    autoDetectBtn.addEventListener("click", function () {
        chrome.tabs.query({ active: true, currentWindow: true }, function (tabs) {
            if (tabs && tabs[0] && tabs[0].url) {
                var currentUrl = tabs[0].url;
                // Extraer el origen y agregar /oficinas/public
                try {
                    var urlObj = new URL(currentUrl);
                    // Verificar si ya tiene /oficinas en la ruta
                    if (urlObj.pathname.indexOf("/oficinas") === 0) {
                        var detectedUrl = urlObj.origin + "/oficinas/public";
                        serverUrlInput.value = detectedUrl;
                    } else if (urlObj.pathname.indexOf("/public") !== -1) {
                        // Si ya esta en una pagina public, usar la URL tal cual
                        serverUrlInput.value = urlObj.origin + urlObj.pathname.replace(/\/public\/.*/, "/public");
                    } else {
                        // Usar el origen + /oficinas/public
                        serverUrlInput.value = urlObj.origin + "/oficinas/public";
                    }
                    savedMsg.style.display = "block";
                    savedMsg.textContent = "URL detectada desde pestaña";
                    setTimeout(function () {
                        savedMsg.style.display = "none";
                        savedMsg.textContent = "Guardado correctamente";
                    }, 2000);
                } catch (e) {
                    console.error("Error parsing URL:", e);
                }
            }
        });
    });

    // Abrir aplicacion
    openAppBtn.addEventListener("click", function () {
        chrome.storage.local.get(["serverUrl"], function (result) {
            var url = result.serverUrl || DEFAULT_URL;
            chrome.tabs.create({ url: url });
        });
    });
});
