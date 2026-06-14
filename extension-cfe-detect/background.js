console.log("CFE Detector extension loaded");

chrome.runtime.onInstalled.addListener(function () {
    console.log("Extension installed/reloaded");
    chrome.notifications.create("installed", {
        type: "basic",
        iconUrl: "icons/icon128.png",
        title: "Extension Cargada",
        message: "Detector de CFEs instalado correctamente."
    });
});

chrome.downloads.onCreated.addListener(function (downloadItem) {
    console.log("[CFE] Download started:", downloadItem.id, downloadItem.filename);
});

// Detectar descargas completadas
chrome.downloads.onChanged.addListener(function (delta) {
    if (!(delta.state && delta.state.current === "complete")) return;

    console.log("[CFE] Download completed! ID:", delta.id);

    chrome.downloads.search({ id: delta.id }, function (results) {
        if (!results || results.length === 0) {
            console.log("[CFE] No download found for id:", delta.id);
            return;
        }

        var download = results[0];
        var filepath = download.filename || "";
        var filename = "unknown";

        if (filepath) {
            var parts = filepath.replace(/\\/g, "/").split("/");
            filename = parts[parts.length - 1];
        }

        var mime = download.mime || "";
        console.log("[CFE] File:", filename, "| MIME:", mime, "| Path:", filepath);

        // Verificar si es PDF (en Linux Chrome a veces mime es octet-stream o falta extensión)
        var filenameLower = filename.toLowerCase();
        var mimeLower = mime.toLowerCase();
        var urlLower = download.url ? download.url.toLowerCase() : "";

        var isPdf = mimeLower.includes("pdf") ||
                    filenameLower.includes(".pdf") ||
                    urlLower.includes(".pdf") ||
                    filenameLower.includes("cfe") ||
                    filenameLower.includes("factura") ||
                    filenameLower.includes("ticket") ||
                    filenameLower.includes("recibo") ||
                    filenameLower.includes("comprobante");

        // Si es extremadamente dudoso pero tiene un tamaño razonable para un CFE (< 2MB) 
        // y no es un tipo de archivo claramente ajeno, le damos el beneficio de la duda.
        if (!isPdf) {
            var size = download.fileSize || download.totalBytes || download.bytesReceived || 0;
            if (size > 0 && size < 2 * 1024 * 1024) {
                var excludedExts = [".png", ".jpg", ".jpeg", ".gif", ".mp4", ".mp3", ".zip", ".rar", ".exe", ".iso", ".csv", ".xlsx", ".doc", ".docx", ".tar", ".gz"];
                var isExcluded = excludedExts.some(ext => filenameLower.endsWith(ext) || urlLower.endsWith(ext));
                if (!isExcluded) {
                    isPdf = true;
                    console.log("[CFE] Forzando análisis para archivo desconocido pero pequeño:", filename);
                }
            }
        }

        if (!isPdf) {
            console.log("[CFE] Not a PDF/CFE. Ignoring.", filename, mime);
            return;
        }

        if (!filepath) {
            console.warn("[CFE] Empty filepath. We will try to rely on download.url");
        }

        console.log("[CFE] PDF/CFE detected! Sending to server...");

        chrome.storage.local.get(["serverUrl"], function (result) {
            var serverUrl = result.serverUrl || "http://localhost/oficinas/public";
            analizarEnServidor(delta.id, filename === "unknown" ? "documento.pdf" : filename, filepath, serverUrl, download.url);
        });
    });
});

/**
 * Envía el filepath al servidor para análisis.
 * No intenta leer el archivo localmente (imposible en MV3 service workers).
 */
function analizarEnServidor(downloadId, filename, filepath, serverUrl, downloadUrl) {
    var analyzeUrl = serverUrl + "/api/cfe/analizar-archivo";

    function handleServerResponse(data) {
        console.log("[CFE] Server response:", JSON.stringify(data));
        if (!data || !data.es_cfe) {
            console.log("[CFE] Not a CFE:", data && data.mensaje);
            // Si el servidor no pudo leer el archivo (modo remoto o permisos de Linux), abrir analyze.html
            // para que el usuario pueda arrastrar el PDF manualmente.
            var msg = (data && data.mensaje) ? data.mensaje.toLowerCase() : "";
            if (msg && (
                msg.indexOf("no se pudo acceder") !== -1 ||
                msg.indexOf("no se puede acceder") !== -1 ||
                msg.indexOf("el archivo no existe") !== -1 ||
                msg.indexOf("error") !== -1 ||
                msg.indexOf("permission") !== -1 ||
                msg.indexOf("failed to open stream") !== -1
            )) {
                console.log("[CFE] Server cannot access file or reading error. Opening analyze.html for manual upload.");
                openAnalyzeWindow(downloadId, filename, filepath, { requireManual: true });
            }
            return;
        }

        // Es un CFE válido
        if (data.tipo_cfe_codigo === "multas_cobradas") {
            console.log("[CFE] Multa detectada! Intentando auto-registro...");
            registrarMultaAuto(data.datos, serverUrl)
                .then(function (res) {
                    if (res && res.success) {
                        console.log("[CFE] Auto-registro exitoso:", res.redirect_url);
                        openOrFocusAppTab(res.redirect_url, serverUrl);
                    } else {
                        console.warn("[CFE] Fallo auto-registro:", res && res.mensaje);
                        openAnalyzeWindow(downloadId, filename, filepath, data);
                    }
                })
                .catch(function (err) {
                    console.error("[CFE] Error red auto-registro:", err);
                    openAnalyzeWindow(downloadId, filename, filepath, data);
                });
        } else {
            console.log("[CFE] CFE válido detectado:", data.tipo_cfe, "- Abriendo ventana de análisis...");
            openAnalyzeWindow(downloadId, filename, filepath, data);
        }
    }

    function fallbackFilepathRequest() {
        var formData = new FormData();
        formData.append("filepath", filepath);
        formData.append("filename", filename);

        console.log("[CFE] POST to:", analyzeUrl, "filepath:", filepath);

        fetch(analyzeUrl, {
            method: "POST",
            body: formData
        })
        .then(function (response) {
            if (!response.ok) throw new Error("HTTP " + response.status + " desde " + analyzeUrl);
            return response.json();
        })
        .then(handleServerResponse)
        .catch(function (err) {
            console.error("[CFE] Error comunicando con servidor:", err);
            // Abrir analyze.html para que el usuario pueda subir el PDF manualmente
            openAnalyzeWindow(downloadId, filename, filepath, { requireManual: true });
        });
    }

    if (downloadUrl && downloadUrl.startsWith("http")) {
        console.log("[CFE] Intentando descargar Blob desde URL:", downloadUrl);
        fetch(downloadUrl)
            .then(function(res) {
                if (!res.ok) throw new Error("HTTP " + res.status);
                var contentType = res.headers.get("content-type") || "";
                if (contentType.toLowerCase().includes("text/html")) {
                    throw new Error("La URL devolvió HTML (probablemente login/redirección)");
                }
                return res.blob();
            })
            .then(function(blob) {
                var formData = new FormData();
                formData.append("pdf_file", blob, filename);
                formData.append("filepath", filepath);
                formData.append("filename", filename);

                return fetch(analyzeUrl, {
                    method: "POST",
                    body: formData
                });
            })
            .then(function(response) {
                if (!response.ok) throw new Error("HTTP " + response.status);
                return response.json();
            })
            .then(function(data) {
                // Si enviamos el blob pero el servidor dice que no es un CFE válido,
                // puede ser porque la URL era protegida y descargó un HTML en vez del PDF real.
                if (!data || !data.es_cfe) {
                    console.warn("[CFE] El servidor rechazó el Blob (probablemente descargó HTML/Login). Fallback...");
                    throw new Error("Blob invalid");
                }
                handleServerResponse(data);
            })
            .catch(function(err) {
                console.warn("[CFE] Falló el fetch desde la URL o el blob no era válido. Usando fallback filepath...", err);
                if (filepath) {
                    fallbackFilepathRequest();
                } else {
                    // Si ni siquiera hay filepath, forzamos abrir la ventana de carga manual
                    console.error("[CFE] No hay filepath para fallback. Abriendo ventana manual.");
                    openAnalyzeWindow(downloadId, filename, filepath, { requireManual: true });
                }
            });
    } else {
        if (filepath) {
            fallbackFilepathRequest();
        } else {
            console.error("[CFE] No hay downloadUrl ni filepath. Abriendo ventana manual.");
            openAnalyzeWindow(downloadId, filename, filepath, { requireManual: true });
        }
    }
}

// Escuchar mensajes desde analyze.js
chrome.runtime.onMessage.addListener(function (request, sender, sendResponse) {
    console.log("[CFE] Message received:", request.action);

    if (request.action === "analyzePdf") {
        var serverUrl = request.serverUrl || "http://localhost/oficinas/public";
        var analyzeUrl = serverUrl + "/api/cfe/analizar-archivo";

        var formData = new FormData();
        formData.append("filepath", request.filepath || "");
        formData.append("filename", request.filename || "");

        fetch(analyzeUrl, {
            method: "POST",
            body: formData
        })
        .then(function (r) { return r.json(); })
        .then(function (data) { sendResponse({ success: true, data: data }); })
        .catch(function (err) { sendResponse({ success: false, error: err.message }); });

        return true; // async response
    }
});

function openAnalyzeWindow(downloadId, filename, filepath, data) {
    var key = "cfe_analysis_" + downloadId;
    var store = {};
    store[key] = data;

    chrome.storage.local.set(store, function () {
        var analyzeUrl = chrome.runtime.getURL("analyze.html");
        analyzeUrl += "?downloadId=" + downloadId;
        analyzeUrl += "&filename=" + encodeURIComponent(filename);
        analyzeUrl += "&filepath=" + encodeURIComponent(filepath);
        analyzeUrl += "&preanalyzed=true";

        console.log("[CFE] Opening analyze.html:", analyzeUrl);
        chrome.tabs.create({ url: analyzeUrl, active: true });
    });
}

function registrarMultaAuto(datos, serverUrl) {
    return fetch(serverUrl + "/api/cfe/registrar-multa-auto", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "Accept": "application/json"
        },
        body: JSON.stringify({ datos: datos })
    }).then(function (r) { return r.json(); });
}

function openOrFocusAppTab(targetUrl, baseUrl) {
    chrome.tabs.query({}, function (tabs) {
        var foundTab = null;
        for (var i = 0; i < tabs.length; i++) {
            if (tabs[i].url && tabs[i].url.toLowerCase().startsWith(baseUrl.toLowerCase())) {
                foundTab = tabs[i];
                break;
            }
        }
        if (foundTab) {
            chrome.tabs.update(foundTab.id, { url: targetUrl, active: true });
            chrome.windows.update(foundTab.windowId, { focused: true });
        } else {
            chrome.tabs.create({ url: targetUrl, active: true });
        }
    });
}
