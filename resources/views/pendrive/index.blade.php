@extends('layouts.app')

@push('styles')
    <style>
        #drop-zone {
            border: 2px dashed #ccc;
            border-radius: 10px;
            padding: 40px;
            text-align: center;
            color: #aaa;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
        }

        #drop-zone.drag-over {
            border-color: #007bff;
            color: #007bff;
            background-color: rgba(0, 123, 255, 0.05);
        }

        #drop-zone .icon {
            font-size: 48px;
        }

        #drop-zone .text {
            margin-top: 15px;
            font-size: 18px;
        }

        #file-input {
            display: none;
        }

        #upload-progress {
            margin-top: 20px;
            display: none;
        }

        .progress-bar {
            transition: width 0.3s ease;
        }

        #alert-container {
            position: fixed;
            top: 80px;
            right: 20px;
            z-index: 1050;
            width: 350px;
        }

        .list-group-item span {
            word-break: break-all;
        }
    </style>
@endpush

@section('content')
    <div class="container">
        <div id="alert-container"></div>
        <h2>Pendrive Virtual</h2>

        <div class="card mb-4">
            <div class="card-header">Subir Archivo</div>
            <div class="card-body">
                <form id="upload-form" action="{{ route('pendrive.upload') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div id="drop-zone">
                        <div class="icon"><i class="fas fa-cloud-upload-alt"></i></div>
                        <div class="text">Arrastra y suelta un archivo aquí, o haz clic para seleccionarlo.</div>
                        <small class="text-muted">Tamaño máximo: 100MB</small>
                    </div>
                    <input type="file" name="file" id="file-input">
                    <div id="upload-progress" class="mt-3" style="display: none;">
                        <div class="progress">
                            <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar"
                                style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">Archivos Almacenados</div>
            <div class="card-body">
                <ul id="file-list" class="list-group" data-url-base="{{ route('pendrive.index') }}">
                    @forelse ($files as $file)
                        <li class="list-group-item d-flex justify-content-between align-items-center"
                            data-filename="{{ basename($file) }}">
                            <div class="d-flex align-items-center">
                                <img src="{{ route('pendrive.thumbnail', ['filename' => basename($file)]) }}"
                                    alt="Miniatura de {{ basename($file) }}" class="thumbnail me-3"
                                    style="width: 60px; height: 60px; object-fit: cover; border-radius: 4px;">
                                <span>
                                    {{ basename($file) }}
                                </span>
                            </div>
                            <div class="btn-group" role="group">
                                <a href="{{ route('pendrive.download', ['filename' => basename($file)]) }}"
                                    class="btn btn-secondary btn-sm" title="Descargar archivo"
                                    onclick="showDownloadToast('{{ basename($file) }}'); return false;">
                                    <i class="fas fa-download"></i>
                                </a>
                                <button type="button" class="btn btn-danger btn-sm delete-btn"
                                    data-filename="{{ basename($file) }}" title="Eliminar archivo">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>
                        </li>
                    @empty
                        <li class="list-group-item no-files">No hay archivos almacenados.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // --- Existing variables ---
            const dropZone = document.getElementById('drop-zone');
            const fileInput = document.getElementById('file-input');
            const uploadForm = document.getElementById('upload-form');
            const progressContainer = document.getElementById('upload-progress');
            const progressBar = progressContainer.querySelector('.progress-bar');
            const fileList = document.getElementById('file-list');
            const alertContainer = document.getElementById('alert-container');

            // --- CSRF Token for all AJAX requests ---
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const urlBase = fileList.dataset.urlBase;

            // --- Existing Upload Logic ---
            dropZone.addEventListener('click', () => fileInput.click());
            fileInput.addEventListener('change', () => handleFile(fileInput.files[0]));
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                dropZone.addEventListener(eventName, preventDefaults, false);
            });

            function preventDefaults(e) {
                e.preventDefault();
                e.stopPropagation();
            }
            ['dragenter', 'dragover'].forEach(eventName => {
                dropZone.addEventListener(eventName, () => dropZone.classList.add('drag-over'), false);
            });
            ['dragleave', 'drop'].forEach(eventName => {
                dropZone.addEventListener(eventName, () => dropZone.classList.remove('drag-over'), false);
            });
            dropZone.addEventListener('drop', (e) => {
                handleFile(e.dataTransfer.files[0]);
            }, false);

            function handleFile(file) {
                if (!file) return;
                uploadFile(file);
            }

            function uploadFile(file) {
                const formData = new FormData();
                formData.append('file', file);

                fetch(uploadForm.action, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-CSRF-TOKEN': csrfToken
                        }
                    })
                    .then(response => response.json().then(data => ({
                        status: response.status,
                        body: data
                    })))
                    .then(({
                        status,
                        body
                    }) => {
                        if (status >= 200 && status < 300) {
                            // Mostrar notificación con SweetAlert2
                            window.dispatchEvent(new CustomEvent('swal:success', {
                                detail: {
                                    text: body.success
                                }
                            }));
                            addFileToList(file.name);
                        } else {
                            // Mostrar error con SweetAlert2
                            window.dispatchEvent(new CustomEvent('swal:toast-error', {
                                detail: {
                                    text: body.error || 'Error en la subida.'
                                }
                            }));
                        }
                    })
                    .catch(() => {
                        // Mostrar error de red con SweetAlert2
                        window.dispatchEvent(new CustomEvent('swal:toast-error', {
                            detail: {
                                text: 'Error de red. No se pudo completar la subida.'
                            }
                        }));
                    });
            }

            // --- NEW: Delete Logic ---
            fileList.addEventListener('click', function(e) {
                const deleteButton = e.target.closest('.delete-btn');
                if (deleteButton) {
                    const filename = deleteButton.dataset.filename;

                    Swal.fire({
                        title: '¿Estás seguro?',
                        text: `El archivo "${filename}" se eliminará permanentemente.`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: 'Sí, ¡eliminar!',
                        cancelButtonText: 'Cancelar'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            performDelete(filename);
                        }
                    });
                }
            });

            function performDelete(filename) {
                const url = `${urlBase}/${encodeURIComponent(filename)}`;

                fetch(url, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        }
                    })
                    .then(response => response.json().then(data => ({
                        ok: response.ok,
                        body: data
                    })))
                    .then(({
                        ok,
                        body
                    }) => {
                        if (ok) {
                            // Mostrar notificación de eliminación con SweetAlert2 toast
                            window.dispatchEvent(new CustomEvent('swal:success', {
                                detail: {
                                    text: body.success || 'El archivo ha sido eliminado.'
                                }
                            }));
                            removeFileFromList(filename);
                        } else {
                            // Mostrar error de eliminación con SweetAlert2 toast
                            window.dispatchEvent(new CustomEvent('swal:toast-error', {
                                detail: {
                                    text: body.error || 'No se pudo eliminar el archivo.'
                                }
                            }));
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        // Mostrar error de red con SweetAlert2 toast
                        window.dispatchEvent(new CustomEvent('swal:toast-error', {
                            detail: {
                                text: 'Error de Red. No se pudo conectar con el servidor.'
                            }
                        }));
                    });
            }

            // --- Helper Functions ---
            function showAlert(message, type = 'info') {
                const alertId = `alert-${Date.now()}`;
                const alert = document.createElement('div');
                alert.id = alertId;
                alert.className = `alert alert-${type} alert-dismissible fade show`;
                alert.role = 'alert';
                alert.innerHTML = `
            ${message}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        `;
                alertContainer.appendChild(alert);

                setTimeout(() => {
                    const alertElement = document.getElementById(alertId);
                    if (alertElement) {
                        // Bootstrap's collapse/fade requires jQuery for events, so we just remove it
                        alertElement.remove();
                    }
                }, 5000);
            }

            function addFileToList(filename) {
                const noFilesLi = fileList.querySelector('.no-files');
                if (noFilesLi) {
                    noFilesLi.remove();
                }

                const downloadUrl = `${urlBase}/download/${encodeURIComponent(filename)}`;
                const thumbnailUrl = `${urlBase}/thumbnail/${encodeURIComponent(filename)}`;

                const li = document.createElement('li');
                li.className = 'list-group-item d-flex justify-content-between align-items-center';
                li.dataset.filename = filename;
                li.innerHTML = `
            <div class="d-flex align-items-center">
                <img src="${thumbnailUrl}"
                     alt="Miniatura de ${filename}"
                     class="thumbnail me-3"
                     style="width: 60px; height: 60px; object-fit: cover; border-radius: 4px;"
                     onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHZpZXdCb3g9IjAgMCA2MCA2MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9IjYwIiBoZWlnaHQ9IjYwIiBmaWxsPSIjZTVlN2ViIi8+CjxyZWN0IHdpZHRoPSI2MCIgaGVpZ2h0PSI2MCIgZmlsbD0iI2ZmZiIvPgo8cmVjdCB4PSI1MCIgeT0iMzAiIHdpZHRoPSIyMCIgaGVpZ2h0PSIyMCIgZmlsbD0iI2ZmZiIvPgo8cmVjdCB4PSI0MCIgeT0iMzAiIHdpZHRoPSIxMCIgaGVpZ2h0PSIxMCIgZmlsbD0iI2ZmZiIvPgo8L3N2Zz4K';">
                <span>
                    ${filename}
                </span>
            </div>
            <div class="btn-group" role="group">
                <a href="${downloadUrl}" class="btn btn-secondary btn-sm" title="Descargar archivo">
                    <i class="fas fa-download"></i>
                </a>
                <button type="button" class="btn btn-danger btn-sm delete-btn" data-filename="${filename}" title="Eliminar archivo">
                    <i class="fas fa-trash-alt"></i>
                </button>
            </div>
        `;
                fileList.prepend(li);
            }

            function removeFileFromList(filename) {
                const li = fileList.querySelector(`li[data-filename="${filename}"]`);
                if (li) {
                    li.remove();
                }
                if (fileList.children.length === 0) {
                    const noFilesLi = document.createElement('li');
                    noFilesLi.className = 'list-group-item no-files';
                    noFilesLi.textContent = 'No hay archivos almacenados.';
                    fileList.appendChild(noFilesLi);
                }
            }

            // Función para mostrar toast de descarga
            function showDownloadToast(filename) {
                window.dispatchEvent(new CustomEvent('swal:success', {
                    detail: {
                        text: `Iniciando descarga del archivo: ${filename}`
                    }
                }));

                // Realizar la descarga después de mostrar el toast
                const downloadUrl = `${urlBase}/download/${encodeURIComponent(filename)}`;
                window.location.href = downloadUrl;
            }
        });
    </script>
@endpush
