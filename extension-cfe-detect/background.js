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
    console.log("Download started:", downloadItem.id, downloadItem.filename);
});

// Usar onChanged para detectar descargas completadas
chrome.downloads.onChanged.addListener(function (delta) {
    // Solo procesar cuando el estado cambia a "complete"
    if (delta.state && delta.state.current === "complete") {
        console.log("Download completed! ID:", delta.id);

        chrome.downloads.search({ id: delta.id }, function (results) {
            if (!results || results.length === 0) {
                console.log("No results found");
                return;
            }

            var download = results[0];
            var filename = "unknown";
            var filepath = download.filename || "";

            if (download.filename) {
                var parts = download.filename.replace(/\\/g, "/").split("/");
                filename = parts[parts.length - 1];
            }

            var mime = download.mime || "";

            console.log("Download processed - File:", filename, "Mime:", mime);

            // Verificar si es PDF
            var isPdf = false;
            var lowerMime = mime.toLowerCase();
            var lowerFile = filename.toLowerCase();

            if (lowerMime.indexOf("pdf") !== -1) {
                isPdf = true;
            }
            if (lowerFile.indexOf(".pdf") !== -1) {
                isPdf = true;
            }

            if (isPdf) {
                console.log("PDF detected! Starting analysis...");

                // Obtener URL del servidor
                chrome.storage.local.get(['serverUrl'], function (result) {
                    var serverUrl = result.serverUrl || "http://localhost/oficinas/public";

                    // Analizar internamente ANTES de abrir la pestaña
                    analyzePdfInternal(delta.id, filename, serverUrl)
                        .then(function (data) {
                            if (data && data.es_cfe) {

                                // LOGICA NUEVA: Auto-registro para multas
                                if (data.tipo_cfe_codigo === 'multas_cobradas' || data.tipo_cfe_codigo === 'Multas Cobradas') {
                                    console.log("Multa detectada! Intentando auto-registro...");

                                    registrarMultaAuto(data.datos, serverUrl)
                                        .then(function (res) {
                                            if (res.success) {
                                                console.log("Auto-registro exitoso:", res.redirect_url);
                                                // Reutilizar pestaña existente o abrir una nueva
                                                openOrFocusAppTab(res.redirect_url, serverUrl);
                                            } else {
                                                console.error("Fallo auto-registro:", res.mensaje);
                                                // Fallback a ventana de análisis
                                                openAnalyzeWindow(delta.id, filename, filepath, data);
                                            }
                                        })
                                        .catch(function (err) {
                                            console.error("Error red auto-registro:", err);
                                            openAnalyzeWindow(delta.id, filename, filepath, data);
                                        });
                                } else {
                                    console.log("Valid CFE detected! Opening analyze window...");
                                    openAnalyzeWindow(delta.id, filename, filepath, data);
                                }

                            } else {
                                console.log("PDF analyzed but NOT a valid/allowed CFE. Ignoring.");
                            }
                        })
                        .catch(function (err) {
                            console.error("Error identifying PDF content:", err);
                        });
                });
            } else {
                console.log("Not a PDF, ignoring.");
            }
        });
    }
});

chrome.runtime.onMessage.addListener(function (request, sender, sendResponse) {
    console.log("Message received:", request);

    if (request.action === "analyzePdf") {
        analyzePdfFromDownload(request, sendResponse);
        return true; // Indicates async response
    }

    if (request.action === "processPdf") {
        sendResponse({ status: "processing" });
        return true;
    }
});

// Funcion interna que devuelve Promise
function analyzePdfInternal(downloadId, filename, serverUrl) {
    return new Promise(function (resolve, reject) {
        chrome.downloads.search({ id: parseInt(downloadId) }, function (results) {
            if (!results || results.length === 0) {
                reject(new Error("Download not found"));
                return;
            }

            var download = results[0];
            if (!download.exists) {
                reject(new Error("File does not exist: " + download.filename));
                return;
            }

            var filePath = download.filename;

            // Leer el archivo usando fetch con file://
            fetch('file:///' + filePath.replace(/\\/g, '/'))
                .then(function (response) {
                    if (!response.ok) {
                        throw new Error("Failed to read file: HTTP " + response.status);
                    }
                    return response.blob();
                })
                .then(function (blob) {
                    // Enviar al servidor
                    var formData = new FormData();
                    formData.append('pdf_file', blob, filename);
                    formData.append('filepath', filePath);
                    formData.append('filename', filename);

                    var analyzeUrl = serverUrl + "/api/cfe/analizar-archivo";

                    return fetch(analyzeUrl, {
                        method: "POST",
                        credentials: "include",
                        body: formData
                    });
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
                    resolve(data); // { success: true, es_cfe: true/false, ... }
                })
                .catch(function (error) {
                    reject(error);
                });
        });
    });
}

// Wrapper para mantener compatibilidad con onMessage (si fuera necesario)
function analyzePdfFromDownload(request, sendResponse) {
    var downloadId = request.downloadId;
    var filename = request.filename;
    var serverUrl = request.serverUrl;

    analyzePdfInternal(downloadId, filename, serverUrl)
        .then(function (data) {
            sendResponse({ success: true, data: data });
        })
        .catch(function (error) {
            sendResponse({ success: false, error: error.message });
        });
}

function openAnalyzeWindow(downloadId, filename, filepath, data) {
    var key = 'cfe_analysis_' + downloadId;
    var store = {};
    store[key] = data;

    chrome.storage.local.set(store, function () {
        var analyzeUrl = chrome.runtime.getURL("analyze.html");
        analyzeUrl += "?downloadId=" + downloadId;
        analyzeUrl += "&filename=" + encodeURIComponent(filename);
        analyzeUrl += "&filepath=" + encodeURIComponent(filepath);
        analyzeUrl += "&preanalyzed=true";

        chrome.tabs.create({
            url: analyzeUrl,
            active: true
        });
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
    }).then(response => response.json());
}

function openOrFocusAppTab(targetUrl, baseUrl) {
    chrome.tabs.query({}, function (tabs) {
        var foundTab = null;

        // Buscar una pestaña que coincida con la URL base de la aplicación
        for (var i = 0; i < tabs.length; i++) {
            var tab = tabs[i];
            if (tab.url && tab.url.toLowerCase().startsWith(baseUrl.toLowerCase())) {
                foundTab = tab;
                break;
            }
        }

        if (foundTab) {
            // Si existe, actualizarla y enfocarla
            chrome.tabs.update(foundTab.id, { url: targetUrl, active: true });
            chrome.windows.update(foundTab.windowId, { focused: true });
        } else {
            // Si no, crear una nueva
            chrome.tabs.create({ url: targetUrl, active: true });
        }
    });
}
