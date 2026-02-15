// Configuracion del servidor - CAMBIAR SEGUN ENTORNO
var DEFAULT_URL = "http://localhost/oficinas/public";
var SERVER_URL = DEFAULT_URL;

// Obtener parametros de la URL
var urlParams = new URLSearchParams(window.location.search);
var downloadId = urlParams.get("downloadId") || urlParams.get("id");
var filename = urlParams.get("filename") || "Desconocido";
var filepath = urlParams.get("filepath") || "";

document.getElementById("filename").textContent = filename;

var statusDiv = document.getElementById("status");
var cfeDataDiv = document.getElementById("cfe-data");
var cfeFieldsDiv = document.getElementById("cfe-fields");
var actionsDiv = document.getElementById("actions");

var cfeInfo = null;

// Funcion para cerrar la ventana
function closeWindow() {
    if (chrome.tabs && chrome.tabs.getCurrent) {
        chrome.tabs.getCurrent(function (tab) {
            if (tab) {
                chrome.tabs.remove(tab.id);
            } else {
                window.close();
            }
        });
    } else {
        window.close();
    }
}

// Verificar si hay configuracion guardada
chrome.storage.local.get(["serverUrl"], function (result) {
    if (result.serverUrl) {
        SERVER_URL = result.serverUrl;
    }
    // Iniciar analisis despues de cargar configuracion
    analyzePdf();
});

// Analizar el PDF
function analyzePdf() {
    statusDiv.innerHTML = '<div class="spinner"></div><p>Analizando archivo...</p>';

    if (!downloadId) {
        showError("No se proporcionó ID de descarga");
        return;
    }

    // Primero revisar si el background ya lo analizó y guardó el resultado
    var storageKey = 'cfe_analysis_' + downloadId;
    chrome.storage.local.get([storageKey], function (result) {
        if (result[storageKey]) {
            console.log("Usando resultado pre-analizado del almacenamiento local");
            var data = result[storageKey];

            // Limpiar el storage para no acumular basura
            chrome.storage.local.remove([storageKey]);

            if (data.es_cfe) {
                showCfeData(data);
            } else {
                showNotCfe(data.mensaje || "Este PDF no contiene un CFE válido.");
            }
        } else {
            // Fallback: Analizar normalmente si no estaba en storage
            requestNewAnalysis();
        }
    });
}

function requestNewAnalysis() {
    chrome.runtime.sendMessage({
        action: "analyzePdf",
        downloadId: downloadId,
        filename: filename,
        serverUrl: SERVER_URL
    }, function (response) {
        if (chrome.runtime.lastError) {
            showError("Error de comunicación: " + chrome.runtime.lastError.message);
            return;
        }

        if (response && response.success) {
            var data = response.data;
            console.log("Respuesta del servidor:", data);

            if (data.es_cfe) {
                showCfeData(data);
            } else {
                showNotCfe(data.mensaje || "Este PDF no contiene un CFE válido.");
            }
        } else {
            showError(response && response.error ? response.error : "Error al analizar el PDF");
        }
    });
}

function showCfeData(data) {
    cfeInfo = data;

    statusDiv.className = "status success";
    statusDiv.innerHTML = "<p>&#10004; CFE Detectado: " + (data.tipo_cfe || "Desconocido") + "</p>";

    // Mostrar campos del CFE
    var fields = data.datos || {};
    var html = "";

    var labels = {
        "serie": "Serie",
        "numero": "Numero",
        "fecha": "Fecha",
        "monto": "Monto",
        "emisor": "Emisor",
        "receptor": "Receptor",
        "rut_emisor": "RUT Emisor",
        "rut_receptor": "RUT Receptor",
        "tipo_cfe": "Tipo CFE"
    };

    for (var key in fields) {
        if (fields.hasOwnProperty(key)) {
            var label = labels[key] || key;
            html += '<div class="row"><span class="label">' + label + ':</span><span class="value">' + fields[key] + '</span></div>';
        }
    }

    if (html === "") {
        html = '<div class="row"><span class="label">Info:</span><span class="value">Datos extraidos del CFE</span></div>';
    }

    cfeFieldsDiv.innerHTML = html;
    cfeDataDiv.style.display = "block";
    actionsDiv.style.display = "flex";
}

function showNotCfe(mensaje) {
    statusDiv.className = "status not-cfe";
    statusDiv.innerHTML = "<p>&#9888; " + mensaje + "</p>";

    actionsDiv.innerHTML = '<button class="btn btn-secondary" style="flex:1" id="close-btn">Cerrar</button>';
    actionsDiv.style.display = "flex";

    // Agregar evento al boton
    document.getElementById("close-btn").addEventListener("click", closeWindow);
}

function showError(mensaje) {
    statusDiv.className = "status error";
    statusDiv.innerHTML = "<p>&#10008; " + mensaje + "</p>";

    actionsDiv.innerHTML = '<button class="btn btn-secondary" style="flex:1" id="close-btn">Cerrar</button>';
    actionsDiv.style.display = "flex";

    // Agregar evento al boton
    document.getElementById("close-btn").addEventListener("click", closeWindow);
}

// Botones
document.getElementById("btn-cancel").addEventListener("click", closeWindow);

document.getElementById("btn-create").addEventListener("click", function () {
    if (!cfeInfo) return;

    var btn = document.getElementById("btn-create");
    btn.disabled = true;
    btn.textContent = "Creando...";

    fetch(SERVER_URL + "/api/cfe/crear-registro", {
        method: "POST",
        credentials: "include",
        headers: {
            "Content-Type": "application/json",
            "Accept": "application/json"
        },
        body: JSON.stringify({
            filepath: filepath,
            filename: filename,
            tipo_cfe: cfeInfo.tipo_cfe_codigo,
            datos: cfeInfo.datos
        })
    })
        .then(function (response) {
            return response.json();
        })
        .then(function (data) {
            if (data.success) {
                statusDiv.className = "status success";
                statusDiv.innerHTML = "\u003ci class='fas fa-check-circle'\u003e\u003c/i\u003e " + data.mensaje;

                // Buscar una pestaña existente de la aplicación
                chrome.tabs.query({}, function (tabs) {
                    var appTab = null;
                    var appBaseUrl = SERVER_URL.replace(/\/public$/, '');

                    // Buscar una pestaña que ya tenga la aplicación abierta
                    for (var i = 0; i < tabs.length; i++) {
                        if (tabs[i].url && tabs[i].url.indexOf(appBaseUrl) === 0) {
                            appTab = tabs[i];
                            break;
                        }
                    }

                    if (appTab) {
                        // Reutilizar pestaña existente
                        chrome.tabs.update(appTab.id, {
                            url: data.redirect_url,
                            active: true
                        }, function () {
                            // Enfocar la ventana que contiene la pestaña
                            chrome.windows.update(appTab.windowId, { focused: true });
                            // Cerrar el popup de análisis
                            setTimeout(closeWindow, 500);
                        });
                    } else {
                        // No hay pestaña existente, abrir una nueva
                        chrome.tabs.create({ url: data.redirect_url }, function () {
                            setTimeout(closeWindow, 500);
                        });
                    }
                });
            } else {
                statusDiv.className = "status error";
                statusDiv.innerHTML = "\u003ci class='fas fa-exclamation-triangle'\u003e\u003c/i\u003e " + data.mensaje;
                btn.disabled = false;
                btn.textContent = "Crear Registro";
            }
        })
        .catch(function (error) {
            showError("Error: " + error.message);
            btn.disabled = false;
            btn.textContent = "Crear Registro";
        });
});
