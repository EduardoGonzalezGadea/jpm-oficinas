// Configuración del servidor - CAMBIAR SEGÚN ENTORNO
var DEFAULT_URL = "http://localhost/oficinas/public";
var SERVER_URL = DEFAULT_URL;

// Obtener parámetros de la URL
var urlParams = new URLSearchParams(window.location.search);
var downloadId = urlParams.get("downloadId") || urlParams.get("id");
var filename = urlParams.get("filename") || "Desconocido";
var filepath = urlParams.get("filepath") || "";

document.getElementById("filename").textContent = filename;

var statusDiv = document.getElementById("status");
var cfeDataDiv = document.getElementById("cfe-data");
var cfeFieldsDiv = document.getElementById("cfe-fields");
var actionsDiv = document.getElementById("actions");
var dropzone = document.getElementById("dropzone");
var fileInput = document.getElementById("file-input");

var cfeInfo = null;

// Detectar si el navegador es Firefox
var isFirefox = navigator.userAgent.toLowerCase().indexOf('firefox') > -1;

// Funciones auxiliares seguras para manipulación de DOM (evitando innerHTML)
function setStatusMessage(text) {
    statusDiv.textContent = "";
    var p = document.createElement("p");
    p.textContent = text;
    statusDiv.appendChild(p);
}

function showLoading(text) {
    statusDiv.textContent = "";
    var spinner = document.createElement("div");
    spinner.className = "spinner";
    var p = document.createElement("p");
    p.id = "status-text";
    p.textContent = text;
    statusDiv.appendChild(spinner);
    statusDiv.appendChild(p);
}

function showSingleActionButton(text, id, onClick) {
    actionsDiv.textContent = "";
    var btn = document.createElement("button");
    btn.className = "btn btn-secondary";
    btn.style.flex = "1";
    btn.id = id;
    btn.textContent = text;
    if (onClick) {
        btn.addEventListener("click", onClick);
    }
    actionsDiv.appendChild(btn);
}

function setCreateButtonContent(btn) {
    btn.textContent = "";
    var svg = document.createElementNS("http://www.w3.org/2000/svg", "svg");
    svg.setAttribute("style", "width:16px;height:16px;fill:currentColor");
    svg.setAttribute("viewBox", "0 0 24 24");
    var path = document.createElementNS("http://www.w3.org/2000/svg", "path");
    path.setAttribute("d", "M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z");
    svg.appendChild(path);
    btn.appendChild(svg);
    
    var textNode = document.createTextNode("Crear Registro");
    btn.appendChild(textNode);
}

function showDualActionButtons(onCancel, onCreate) {
    actionsDiv.textContent = "";
    
    var btnCancel = document.createElement("button");
    btnCancel.className = "btn btn-secondary";
    btnCancel.id = "btn-cancel";
    btnCancel.textContent = "Cancelar";
    if (onCancel) {
        btnCancel.addEventListener("click", onCancel);
    }
    
    var btnCreate = document.createElement("button");
    btnCreate.className = "btn btn-primary";
    btnCreate.id = "btn-create";
    setCreateButtonContent(btnCreate);
    if (onCreate) {
        btnCreate.addEventListener("click", onCreate);
    }
    
    actionsDiv.appendChild(btnCancel);
    actionsDiv.appendChild(btnCreate);
}

// Función para cerrar la pestaña/ventana
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

// Configurar los manejadores de eventos para Drag & Drop (Dropzone)
function initDropzone() {
    // Al hacer clic en la dropzone, abrir selector de archivos
    dropzone.addEventListener("click", function () {
        fileInput.click();
    });

    // Escuchar cuando el usuario selecciona un archivo
    fileInput.addEventListener("change", function (e) {
        if (e.target.files && e.target.files[0]) {
            handleSelectedFile(e.target.files[0]);
        }
    });

    // Eventos de arrastre
    dropzone.addEventListener("dragover", function (e) {
        e.preventDefault();
        e.stopPropagation();
        dropzone.classList.add("dragover");
    });

    dropzone.addEventListener("dragleave", function (e) {
        e.preventDefault();
        e.stopPropagation();
        dropzone.classList.remove("dragover");
    });

    dropzone.addEventListener("drop", function (e) {
        e.preventDefault();
        e.stopPropagation();
        dropzone.classList.remove("dragover");
        if (e.dataTransfer.files && e.dataTransfer.files[0]) {
            handleSelectedFile(e.dataTransfer.files[0]);
        }
    });
}

// Procesar el archivo PDF seleccionado por el usuario en Firefox/Fallback
function handleSelectedFile(file) {
    if (file.type !== "application/pdf" && !file.name.toLowerCase().endsWith(".pdf")) {
        alert("Por favor, selecciona un archivo PDF válido.");
        return;
    }

    // Actualizar nombre del archivo detectado
    filename = file.name;
    document.getElementById("filename").textContent = filename;

    // Ocultar Dropzone y mostrar estado de carga
    dropzone.style.display = "none";
    statusDiv.className = "status loading";
    showLoading("Analizando PDF de forma segura...");
    statusDiv.style.display = "block";
    cfeDataDiv.style.display = "none";
    actionsDiv.style.display = "none";

    // Enviar archivo al backend de Laravel
    var formData = new FormData();
    formData.append('pdf_file', file, filename);
    formData.append('filepath', filepath || filename);
    formData.append('filename', filename);

    var analyzeUrl = SERVER_URL + "/api/cfe/analizar-archivo";

    fetch(analyzeUrl, {
        method: "POST",
        credentials: "include",
        body: formData
    })
    .then(function (response) {
        if (!response.ok) {
            return response.json().then(function (err) {
                throw new Error(err.mensaje || "HTTP " + response.status);
            });
        }
        return response.json();
    })
    .then(function (data) {
        if (data.es_cfe) {
            showCfeData(data);
        } else {
            showNotCfe(data.mensaje || "Este PDF no contiene un CFE válido.");
        }
    })
    .catch(function (error) {
        showError("Fallo en el servidor: " + error.message);
    });
}

// Verificar si hay configuración guardada e iniciar
chrome.storage.local.get(["serverUrl"], function (result) {
    if (result.serverUrl) {
        SERVER_URL = result.serverUrl;
    }
    
    // Inicializar listeners del dropzone
    initDropzone();
    
    // Iniciar análisis
    analyzePdf();
});

// Analizar el PDF automáticamente (Chrome) o preparar Dropzone (Firefox)
function analyzePdf() {
    if (isFirefox) {
        console.log("Firefox detectado. Mostrando zona de arrastre debido a políticas de seguridad local.");
        statusDiv.style.display = "none";
        dropzone.style.display = "flex";
        
        // Mostrar botón de cancelar por defecto
        showSingleActionButton("Cancelar", "close-btn", closeWindow);
        actionsDiv.style.display = "flex";
        return;
    }

    showLoading("Analizando archivo...");

    if (!downloadId) {
        showError("No se proporcionó ID de descarga");
        return;
    }

    // Revisar si el background ya lo analizó y guardó el resultado
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

// Solicitar un nuevo análisis al background (Chrome)
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

// Mostrar los datos extraídos del CFE en la interfaz
function showCfeData(data) {
    cfeInfo = data;

    statusDiv.className = "status success";
    setStatusMessage("✔ CFE Detectado: " + (data.tipo_cfe || "Desconocido"));

    // Mostrar campos del CFE
    cfeFieldsDiv.textContent = "";
    var fields = data.datos || {};
    var hasFields = false;

    var labels = {
        // Comunes a todos los CFE
        "tipo_cfe":       "Tipo CFE",
        "serie":          "Serie",
        "numero":         "N\u00famero",
        "recibo":         "Recibo",
        "fecha":          "Fecha",
        "moneda":         "Moneda",
        "vencimiento":    "Vencimiento",
        "forma_pago_doc": "Forma de Pago (Doc.)",
        "periodo":        "Per\u00edodo",
        // Receptor
        "ruc_receptor":   "RUC Receptor",
        "rut_emisor":     "RUT Emisor",
        "ruc_emisor":     "RUT Emisor",
        "titular":        "Titular / Raz\u00f3n Social",
        "nombre":         "Nombre Receptor",
        "cedula":         "C\u00e9dula / RUT",
        // Montos
        "monto":          "Monto Total",
        "monto_total":    "Monto Total",
        // Pagos
        "medio_de_pago":  "Medio de Pago",
        "forma_pago":     "Forma de Pago",
        // Eventuales espec\u00edficos
        "ingreso":        "ING.",
        "orden_cobro":    "Referencia (Orden Cobro)",
        "referencias":    "Referencias",
        "adenda":         "Adenda",
        "detalle":        "Detalle",
        // Otros m\u00f3dulos
        "domicilio":      "Domicilio",
        "adicional":      "Informaci\u00f3n Adicional",
        "detalle_completo": "Detalle Completo"
    };

    // Campos ocultos (t\u00e9cnicos o ya representados por otros)
    var camposOcultos = { "items": true, "tipo_cfe": true };

    // Orden preferido de visualizaci\u00f3n
    var ordenCampos = [
        "serie", "numero", "recibo", "fecha", "vencimiento", "periodo",
        "titular", "nombre", "cedula", "ruc_receptor",
        "monto", "monto_total", "moneda",
        "forma_pago_doc", "forma_pago", "medio_de_pago",
        "ingreso", "detalle", "orden_cobro", "referencias", "adenda",
        "adicional", "detalle_completo", "domicilio"
    ];

    var camposRendered = {};

    function renderField(key, value) {
        if (camposRendered[key]) return;
        if (camposOcultos[key]) return;
        if (value === null || value === undefined) return;
        if (value === "" || value === 0) return;
        if (Array.isArray(value) && value.length === 0) return;

        camposRendered[key] = true;
        hasFields = true;

        var rowDiv = document.createElement("div");
        rowDiv.className = "row";

        var labelSpan = document.createElement("span");
        labelSpan.className = "label";
        labelSpan.textContent = (labels[key] || key) + ":";

        var valueSpan = document.createElement("span");
        valueSpan.className = "value";

        if (typeof value === "number") {
            valueSpan.textContent = value.toLocaleString("es-UY", {
                minimumFractionDigits: 2, maximumFractionDigits: 2
            });
        } else if (typeof value === "object") {
            valueSpan.textContent = JSON.stringify(value);
        } else {
            valueSpan.textContent = String(value);
        }

        rowDiv.appendChild(labelSpan);
        rowDiv.appendChild(valueSpan);
        cfeFieldsDiv.appendChild(rowDiv);
    }

    // Primero los campos en orden preferido
    ordenCampos.forEach(function(key) {
        if (fields.hasOwnProperty(key)) {
            renderField(key, fields[key]);
        }
    });

    // Luego cualquier campo extra no listado
    for (var key in fields) {
        if (fields.hasOwnProperty(key)) {
            renderField(key, fields[key]);
        }
    }

    // Renderizar \u00edtems como sub-lista si existen
    if (fields.items && Array.isArray(fields.items) && fields.items.length > 0) {
        hasFields = true;
        var itemsTitle = document.createElement("div");
        itemsTitle.className = "row";
        var itemsLabel = document.createElement("span");
        itemsLabel.className = "label";
        itemsLabel.style.fontWeight = "bold";
        itemsLabel.textContent = "\u00cdtems:";
        itemsTitle.appendChild(itemsLabel);
        cfeFieldsDiv.appendChild(itemsTitle);

        fields.items.forEach(function(item, idx) {
            var itemRow = document.createElement("div");
            itemRow.className = "row";
            itemRow.style.paddingLeft = "12px";
            itemRow.style.fontSize = "0.85em";
            itemRow.style.borderLeft = "2px solid #555";
            itemRow.style.marginBottom = "2px";

            var itemLabel = document.createElement("span");
            itemLabel.className = "label";
            itemLabel.textContent = (idx + 1) + ". " + (item.concepto || item.detalle || "\u00cdtem");

            var itemValue = document.createElement("span");
            itemValue.className = "value";
            var partes = [];
            if (item.descripcion)          partes.push(item.descripcion);
            if (item.cantidad !== undefined) partes.push("Cant: " + item.cantidad + (item.unidad ? " " + item.unidad : ""));
            if (item.importe !== undefined)  partes.push("$ " + Number(item.importe).toLocaleString("es-UY", { minimumFractionDigits: 2 }));
            itemValue.textContent = partes.join(" | ");

            itemRow.appendChild(itemLabel);
            itemRow.appendChild(itemValue);
            cfeFieldsDiv.appendChild(itemRow);
        });
    }

    if (!hasFields) {
        var rowDiv = document.createElement("div");
        rowDiv.className = "row";

        var labelSpan = document.createElement("span");
        labelSpan.className = "label";
        labelSpan.textContent = "Informaci\u00f3n:";

        var valueSpan = document.createElement("span");
        valueSpan.className = "value";
        valueSpan.textContent = "Datos listos para importaci\u00f3n";

        rowDiv.appendChild(labelSpan);
        rowDiv.appendChild(valueSpan);
        cfeFieldsDiv.appendChild(rowDiv);
    }

    cfeDataDiv.style.display = "block";

    // Restaurar los botones primarios
    showDualActionButtons(closeWindow, submitCfeRecord);
    actionsDiv.style.display = "flex";
}

// Mostrar cuando un PDF no es un CFE válido
function showNotCfe(mensaje) {
    statusDiv.className = "status not-cfe";
    setStatusMessage("⚠ " + mensaje);

    // Habilitar zona de carga por si quiere intentar con otro archivo
    dropzone.style.display = "flex";

    showSingleActionButton("Cerrar", "close-btn", closeWindow);
    actionsDiv.style.display = "flex";
}

// Mostrar errores de lectura o red
function showError(mensaje) {
    statusDiv.className = "status error";
    setStatusMessage("✘ " + mensaje);

    // Habilitar zona de arrastre para dar solución inmediata al usuario
    dropzone.style.display = "flex";

    showSingleActionButton("Cerrar", "close-btn", closeWindow);
    actionsDiv.style.display = "flex";
}

// Registrar el CFE en el sistema Laravel
function submitCfeRecord() {
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
            filepath: filepath || filename,
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
            setStatusMessage("✔ " + data.mensaje);

            // Buscar pestaña de la aplicación Laravel
            chrome.tabs.query({}, function (tabs) {
                var appTab = null;
                var appBaseUrl = SERVER_URL.replace(/\/public$/, '');

                for (var i = 0; i < tabs.length; i++) {
                    if (tabs[i].url && tabs[i].url.indexOf(appBaseUrl) === 0) {
                        appTab = tabs[i];
                        break;
                    }
                }

                if (appTab) {
                    // Reutilizar pestaña de la aplicación
                    chrome.tabs.update(appTab.id, {
                        url: data.redirect_url,
                        active: true
                    }, function () {
                        chrome.windows.update(appTab.windowId, { focused: true });
                        setTimeout(closeWindow, 800);
                    });
                } else {
                    // Abrir una nueva pestaña si no estaba abierta
                    chrome.tabs.create({ url: data.redirect_url }, function () {
                        setTimeout(closeWindow, 800);
                    });
                }
            });
        } else {
            statusDiv.className = "status error";
            setStatusMessage("⚠ " + data.mensaje);
            btn.disabled = false;
            setCreateButtonContent(btn);
        }
    })
    .catch(function (error) {
        showError("Error al registrar: " + error.message);
        btn.disabled = false;
        setCreateButtonContent(btn);
    });
}
