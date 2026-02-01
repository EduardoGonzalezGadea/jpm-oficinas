@extends('layouts.app')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/pendrive-styles.css') }}">
@endpush

@section('content')
    <div id="pendrive-container">
        <div class="main-container">
            <div id="alert-container"></div>

            <h1 class="main-title">
                <i class="fas fa-hdd mr-3"></i>Pendrive Virtual
            </h1>

            <div class="row">
                <div class="col-12">
                    <!-- Upload Card -->
                    <div class="card upload-card mb-4">
                        <div class="card-header bg-primary text-white">
                            <i class="fas fa-cloud-upload-alt mr-2"></i>Subir Archivos
                        </div>
                        <div class="card-body">
                            <form id="upload-form" action="{{ route('pendrive.upload') }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <div id="drop-zone">
                                    <div class="icon pulse-icon"><i class="fas fa-cloud-upload-alt"></i></div>
                                    <div class="text">Arrastra y suelta tu archivo aquí</div>
                                    <div class="subtitle">o haz clic para seleccionar desde tu dispositivo</div>
                                    <small class="text-muted d-block mt-3">
                                        <i class="fas fa-info-circle mr-1"></i>Tamaño máximo permitido: 100MB
                                    </small>
                                </div>
                                <input type="file" name="file" id="file-input">
                                <div id="upload-progress" class="mt-3" style="display: none;">
                                    <div class="progress">
                                        <div class="progress-bar progress-bar-striped progress-bar-animated"
                                             role="progressbar" style="width: 0%;" aria-valuenow="0"
                                             aria-valuemin="0" aria-valuemax="100">0%</div>
                                    </div>
                                    <div class="text-center mt-2">
                                        <small class="text-muted">Subiendo archivo...</small>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Files List Card -->
                    <div class="card files-card">
                        <div class="card-header bg-success text-white">
                            <i class="fas fa-folder-open mr-2"></i>Archivos Almacenados
                            <span class="badge badge-light ml-2" id="file-count">
                                @if(isset($files) && count($files) > 0)
                                    {{ count($files) }} archivo{{ count($files) != 1 ? 's' : '' }}
                                @else
                                    0 archivos
                                @endif
                            </span>
                        </div>
                        <div class="card-body p-4">
                            <ul id="file-list" class="list-group list-group-flush" data-url-base="{{ route('pendrive.index') }}">
                                @forelse ($files as $file)
                                    <li class="list-group-item d-flex justify-content-between align-items-center flex-column flex-md-row"
                                        data-filename="{{ basename($file) }}">
                                        <div class="d-flex align-items-center flex-grow-1 mb-2 mb-md-0">
                                            <img src="{{ route('pendrive.thumbnail', ['filename' => basename($file)]) }}"
                                                 alt="Miniatura" class="thumbnail mr-3"
                                                 onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHZpZXdCb3g9IjAgMCA2MCA2MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9IjYwIiBoZWlnaHQ9IjYwIiBmaWxsPSIjZTVlN2ViIi8+CjxyZWN0IHdpZHRoPSI2MCIgaGVpZ2h0PSI2MCIgZmlsbD0iI2ZmZiIvPgo8cmVjdCB4PSI1MCIgeT0iMzAiIHdpZHRoPSIyMCIgaGVpZ2h0PSIyMCIgZmlsbD0iI2ZmZiIvPgo8cmVjdCB4PSI0MCIgeT0iMzAiIHdpZHRoPSIxMCIgaGVpZ2h0PSIxMCIgZmlsbD0iI2ZmZiIvPgo8L3N2Zz4K';">
                                            <div>
                                                <div class="filename">{{ basename($file) }}</div>
                                                <div class="file-info">
                                                    <i class="fas fa-calendar-alt mr-1"></i>
                                                    {{ date('d/m/Y H:i', filemtime($file)) }}
                                                    <span class="mx-2">•</span>
                                                    <i class="fas fa-weight-hanging mr-1"></i>
                                                    {{ formatBytes(filesize($file)) }}
                                                </div>
                                            </div>
                                        </div>
                                        <div class="action-buttons">
                                            <a href="{{ route('pendrive.download', ['filename' => basename($file)]) }}"
                                               class="btn btn-download btn-sm" title="Descargar archivo"
                                               onclick="showDownloadToast('{{ basename($file) }}'); return false;">
                                                <i class="fas fa-download mr-1"></i>Descargar
                                            </a>
                                            <button type="button" class="btn btn-delete btn-sm delete-btn"
                                                    data-filename="{{ basename($file) }}" title="Eliminar archivo">
                                                <i class="fas fa-trash-alt mr-1"></i>Eliminar
                                            </button>
                                        </div>
                                    </li>
                                @empty
                                    <li class="no-files-message">
                                        <i class="fas fa-folder-open fa-3x text-muted mb-3 d-block"></i>
                                        <div>No hay archivos almacenados</div>
                                        <small class="text-muted">Comienza subiendo tu primer archivo</small>
                                    </li>
                                @endforelse
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        // Helper function to format bytes
        function formatBytes(bytes, decimals = 2) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const dm = decimals < 0 ? 0 : decimals;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
        }

        document.addEventListener('DOMContentLoaded', function() {
            // --- Existing variables ---
            const dropZone = document.getElementById('drop-zone');
            const fileInput = document.getElementById('file-input');
            const uploadForm = document.getElementById('upload-form');
            const progressContainer = document.getElementById('upload-progress');
            const progressBar = progressContainer.querySelector('.progress-bar');
            const fileList = document.getElementById('file-list');
            const alertContainer = document.getElementById('alert-container');
            const fileCount = document.getElementById('file-count');

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

                // Show progress
                progressContainer.style.display = 'block';
                let progress = 0;
                const interval = setInterval(() => {
                    progress += Math.random() * 30;
                    if (progress > 90) progress = 90;
                    progressBar.style.width = progress + '%';
                    progressBar.textContent = Math.round(progress) + '%';
                }, 200);

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
                        clearInterval(interval);
                        progressBar.style.width = '100%';
                        progressBar.textContent = '100%';

                        setTimeout(() => {
                            progressContainer.style.display = 'none';
                            progressBar.style.width = '0%';
                            progressBar.textContent = '0%';
                        }, 500);

                        if (status >= 200 && status < 300) {
                            window.dispatchEvent(new CustomEvent('swal:success', {
                                detail: {
                                    text: body.success
                                }
                            }));
                            addFileToList(file.name);
                            updateFileCount();
                        } else {
                            window.dispatchEvent(new CustomEvent('swal:toast-error', {
                                detail: {
                                    text: body.error || 'Error en la subida.'
                                }
                            }));
                        }
                    })
                    .catch(() => {
                        clearInterval(interval);
                        progressContainer.style.display = 'none';
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
                        confirmButtonColor: '#f5576c',
                        cancelButtonColor: '#6b7280',
                        confirmButtonText: 'Sí, ¡eliminar!',
                        cancelButtonText: 'Cancelar',
                        backdrop: 'rgba(0, 0, 0, 0.5)'
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
                            window.dispatchEvent(new CustomEvent('swal:success', {
                                detail: {
                                    text: body.success || 'El archivo ha sido eliminado.'
                                }
                            }));
                            removeFileFromList(filename);
                            updateFileCount();
                        } else {
                            window.dispatchEvent(new CustomEvent('swal:toast-error', {
                                detail: {
                                    text: body.error || 'No se pudo eliminar el archivo.'
                                }
                            }));
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        window.dispatchEvent(new CustomEvent('swal:toast-error', {
                            detail: {
                                text: 'Error de Red. No se pudo conectar con el servidor.'
                            }
                        }));
                    });
            }

            // --- Helper Functions ---
            function updateFileCount() {
                const items = fileList.querySelectorAll('li[data-filename]');
                const count = items.length;
                if (fileCount) {
                    fileCount.textContent = count + ' archivo' + (count !== 1 ? 's' : '');
                }
            }

            function addFileToList(filename) {
                const noFilesLi = fileList.querySelector('.no-files-message');
                if (noFilesLi) {
                    noFilesLi.remove();
                }

                const downloadUrl = `${urlBase}/download/${encodeURIComponent(filename)}`;
                const thumbnailUrl = `${urlBase}/thumbnail/${encodeURIComponent(filename)}`;

                const li = document.createElement('li');
                li.className = 'list-group-item d-flex justify-content-between align-items-center flex-column flex-md-row';
                li.dataset.filename = filename;
                li.innerHTML = `
                    <div class="d-flex align-items-center flex-grow-1 mb-2 mb-md-0">
                        <img src="${thumbnailUrl}"
                             alt="Miniatura"
                             class="thumbnail mr-3"
                             onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHZpZXdCb3g9IjAgMCA2MCA2MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9IjYwIiBoZWlnaHQ9IjYwIiBmaWxsPSIjZTVlN2ViIi8+CjxyZWN0IHdpZHRoPSI2MCIgaGVpZ2h0PSI2MCIgZmlsbD0iI2ZmZiIvPgo8cmVjdCB4PSI1MCIgeT0iMzAiIHdpZHRoPSIyMCIgaGVpZ2h0PSIyMCIgZmlsbD0iI2ZmZiIvPgo8cmVjdCB4PSI0MCIgeT0iMzAiIHdpZHRoPSIxMCIgaGVpZ2h0PSIxMCIgZmlsbD0iI2ZmZiIvPgo8L3N2Zz4K';">
                        <div>
                            <div class="filename">${filename}</div>
                            <div class="file-info">
                                <i class="fas fa-calendar-alt mr-1"></i>
                                ${new Date().toLocaleDateString('es-ES')} ${new Date().toLocaleTimeString('es-ES')}
                                <span class="mx-2">•</span>
                                <i class="fas fa-weight-hanging mr-1"></i>
                                Reciente
                            </div>
                        </div>
                    </div>
                    <div class="action-buttons">
                        <a href="${downloadUrl}" class="btn btn-download btn-sm" title="Descargar archivo">
                            <i class="fas fa-download mr-1"></i>Descargar
                        </a>
                        <button type="button" class="btn btn-delete btn-sm delete-btn" data-filename="${filename}" title="Eliminar archivo">
                            <i class="fas fa-trash-alt mr-1"></i>Eliminar
                        </button>
                    </div>
                `;
                fileList.prepend(li);

                // Add animation
                li.style.opacity = '0';
                li.style.transform = 'translateY(-20px)';
                setTimeout(() => {
                    li.style.transition = 'all 0.5s ease';
                    li.style.opacity = '1';
                    li.style.transform = 'translateY(0)';
                }, 100);
            }

            function removeFileFromList(filename) {
                const li = fileList.querySelector(`li[data-filename="${filename}"]`);
                if (li) {
                    li.style.transition = 'all 0.3s ease';
                    li.style.opacity = '0';
                    li.style.transform = 'translateX(100px)';
                    setTimeout(() => {
                        li.remove();
                        if (fileList.children.length === 0) {
                            const noFilesLi = document.createElement('li');
                            noFilesLi.className = 'no-files-message';
                            noFilesLi.innerHTML = `
                                <i class="fas fa-folder-open fa-3x text-muted mb-3 d-block"></i>
                                <div>No hay archivos almacenados</div>
                                <small class="text-muted">Comienza subiendo tu primer archivo</small>
                            `;
                            fileList.appendChild(noFilesLi);
                        }
                    }, 300);
                }
            }

            // Función para mostrar toast de descarga
            function showDownloadToast(filename) {
                window.dispatchEvent(new CustomEvent('swal:success', {
                    detail: {
                        text: `Iniciando descarga del archivo: ${filename}`
                    }
                }));

                const downloadUrl = `${urlBase}/download/${encodeURIComponent(filename)}`;
                window.location.href = downloadUrl;
            }
        });
    </script>
@endpush
