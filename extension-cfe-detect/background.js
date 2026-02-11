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
                console.log("PDF detected! Opening analyze window...");

                // Abrir ventana de analisis
                var analyzeUrl = chrome.runtime.getURL("analyze.html");
                analyzeUrl += "?downloadId=" + delta.id;
                analyzeUrl += "&filename=" + encodeURIComponent(filename);
                analyzeUrl += "&filepath=" + encodeURIComponent(filepath);

                chrome.tabs.create({
                    url: analyzeUrl,
                    active: true
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

function analyzePdfFromDownload(request, sendResponse) {
    var downloadId = request.downloadId;
    var filename = request.filename;
    var serverUrl = request.serverUrl;

    console.log("Analyzing PDF from download:", downloadId);

    chrome.downloads.search({ id: parseInt(downloadId) }, function (results) {
        if (!results || results.length === 0) {
            sendResponse({ success: false, error: "Download not found" });
            return;
        }

        var download = results[0];

        if (!download.exists) {
            sendResponse({ success: false, error: "File does not exist: " + download.filename });
            return;
        }

        console.log("Reading file:", download.filename);

        var filePath = download.filename;

        // Usar FileSystemFileHandle para leer el archivo
        // Primero intentamos obtener un fileHandle de la descarga
        chrome.downloads.getFileIcon(parseInt(downloadId), { size: 128 }, function (iconUrl) {
            console.log("File icon URL:", iconUrl);
        });

        // Leer el archivo usando fetch con file://
        fetch('file:///' + filePath.replace(/\\/g, '/'))
            .then(function (response) {
                if (!response.ok) {
                    throw new Error("Failed to read file: HTTP " + response.status);
                }
                return response.blob();
            })
            .then(function (blob) {
                console.log("File read successfully, size:", blob.size);

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
                    return response.json().then(function(err) {
                        throw new Error(err.mensaje || "HTTP " + response.status);
                    });
                }
                return response.json();
            })
            .then(function (data) {
                sendResponse({ success: true, data: data });
            })
            .catch(function (error) {
                console.error("Error:", error);
                sendResponse({ success: false, error: error.message });
            });
    });
}
