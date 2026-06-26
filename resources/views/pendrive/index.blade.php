@extends('layouts.app')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/pendrive-styles.css') }}">
@endpush

@section('content')
<div class="container-fluid p-0 m-0">
    <div id="pendrive-container">
        <div class="card mb-4">
            <div class="card-header bg-info text-white card-header-gradient py-2 px-3 d-flex justify-content-between align-items-center">
                <h4 class="mb-0"><strong><i class="fas fa-hdd mr-2"></i>Pendrive Virtual</strong></h4>
            </div>
        </div>

        <style>
            .upload-container {
                position: relative;
                transition: all 0.3s ease;
            }

            .upload-box {
                border: 2px dashed #ced4da;
                border-radius: 10px;
                background-color: rgba(128, 128, 128, 0.05);
                padding: 1.5rem;
                text-align: center;
                cursor: pointer;
                transition: all 0.3s ease;
                position: relative;
                overflow: hidden;
            }

            .upload-box:hover {
                border-color: #17a2b8;
                background-color: rgba(128, 128, 128, 0.1);
                transform: translateY(-2px);
                box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            }

            .upload-box.drag-over {
                border-color: #17a2b8;
                background-color: rgba(23, 162, 184, 0.1);
                transform: scale(1.02);
            }

            .upload-box i {
                font-size: 2.5rem;
                margin-bottom: 0.5rem;
                transition: transform 0.3s ease;
            }

            .upload-box:hover i {
                transform: scale(1.1);
            }

            .upload-box input[type="file"] {
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                opacity: 0;
                cursor: pointer;
                z-index: 10;
            }

            .upload-loading-overlay {
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.3);
                display: none !important;
                align-items: center;
                justify-content: center;
                z-index: 20;
                border-radius: 8px;
            }
        </style>

        <div id="alert-container"></div>

        <!-- Upload Card -->
        <div class="card mb-4">
            <div class="card-header bg-info text-white card-header-gradient py-2 px-3 d-flex justify-content-between align-items-center">
                <h4 class="mb-0"><strong><i class="fas fa-cloud-upload-alt mr-2"></i>Subir Archivos</strong></h4>
            </div>
            <div class="card-body py-3">
                <form id="upload-form" action="{{ route('pendrive.upload') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="upload-container">
                        <div id="drop-zone" class="upload-box">
                            <input type="file" name="file" id="file-input">
                            <div id="upload-loading" class="upload-loading-overlay" style="display: none !important;">
                                <div class="text-white text-center">
                                    <i class="fas fa-spinner fa-spin fa-2x mb-2"></i>
                                    <div class="font-weight-bold text-uppercase small">Subiendo...</div>
                                </div>
                            </div>
                            <div class="upload-content">
                                <i class="fas fa-cloud-upload-alt text-info"></i>
                                <h6 class="font-weight-bold mb-1">Arrastra tu archivo aquí</h6>
                                <p class="text-muted small mb-0">Haz clic para seleccionar o arrastra cualquier archivo</p>
                                <small class="text-muted"><i class="fas fa-info-circle mr-1"></i>Tamaño máximo: 100MB</small>
                            </div>
                        </div>
                        <div id="upload-progress" class="mt-3" style="display: none;">
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar progress-bar-striped progress-bar-animated"
                                     role="progressbar" style="width: 0%;" aria-valuenow="0"
                                     aria-valuemin="0" aria-valuemax="100">0%</div>
                            </div>
                            <div class="text-center mt-1">
                                <small class="text-muted">Subiendo archivo...</small>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Files List Card -->
        <div class="card">
            <div class="card-header bg-info text-white card-header-gradient py-2 px-3 d-flex justify-content-between align-items-center">
                <h4 class="mb-0"><strong><i class="fas fa-folder-open mr-2"></i>Archivos Almacenados</strong></h4>
                <span class="badge badge-light ml-2" id="file-count">
                    @if(isset($files) && count($files) > 0)
                        {{ count($files) }} archivo{{ count($files) != 1 ? 's' : '' }}
                    @else
                        0 archivos
                    @endif
                </span>
            </div>
            <div class="card-body p-2">
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
@endsection

@push('scripts')
    <script>
        function formatBytes(bytes, decimals = 2) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const dm = decimals < 0 ? 0 : decimals;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
        }

        document.addEventListener('DOMContentLoaded', function() {
            const dropZone = document.getElementById('drop-zone');
            const fileInput = document.getElementById('file-input');
            const uploadForm = document.getElementById('upload-form');
            const progressContainer = document.getElementById('upload-progress');
            const progressBar = progressContainer.querySelector('.progress-bar');
            const fileList = document.getElementById('file-list');
            const fileCount = document.getElementById('file-count');
            const loadingOverlay = document.getElementById('upload-loading');

            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const urlBase = fileList.dataset.urlBase;

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
                dropZone.classList.remove('drag-over');
                handleFile(e.dataTransfer.files[0]);
            }, false);

            function handleFile(file) {
                if (!file) return;
                uploadFile(file);
            }

            function uploadFile(file) {
                const formData = new FormData();
                formData.append('file', file);

                loadingOverlay.style.display = 'flex';
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
                    .then(({ status, body }) => {
                        clearInterval(interval);
                        progressBar.style.width = '100%';
                        progressBar.textContent = '100%';
                        loadingOverlay.style.display = 'none';

                        setTimeout(() => {
                            progressContainer.style.display = 'none';
                            progressBar.style.width = '0%';
                            progressBar.textContent = '0%';
                        }, 500);

                        if (status >= 200 && status < 300) {
                            window.dispatchEvent(new CustomEvent('swal:success', {
                                detail: { text: body.success }
                            }));
                            addFileToList(file.name);
                            updateFileCount();
                        } else {
                            window.dispatchEvent(new CustomEvent('swal:toast-error', {
                                detail: { text: body.error || 'Error en la subida.' }
                            }));
                        }
                    })
                    .catch(() => {
                        clearInterval(interval);
                        progressContainer.style.display = 'none';
                        loadingOverlay.style.display = 'none';
                        window.dispatchEvent(new CustomEvent('swal:toast-error', {
                            detail: { text: 'Error de red. No se pudo completar la subida.' }
                        }));
                    });
            }

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
                    .then(({ ok, body }) => {
                        if (ok) {
                            window.dispatchEvent(new CustomEvent('swal:success', {
                                detail: { text: body.success || 'El archivo ha sido eliminado.' }
                            }));
                            removeFileFromList(filename);
                            updateFileCount();
                        } else {
                            window.dispatchEvent(new CustomEvent('swal:toast-error', {
                                detail: { text: body.error || 'No se pudo eliminar el archivo.' }
                            }));
                        }
                    })
                    .catch(error => {
                        window.dispatchEvent(new CustomEvent('swal:toast-error', {
                            detail: { text: 'Error de Red.' }
                        }));
                    });
            }

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

            function showDownloadToast(filename) {
                window.dispatchEvent(new CustomEvent('swal:success', {
                    detail: { text: `Iniciando descarga del archivo: ${filename}` }
                }));

                const downloadUrl = `${urlBase}/download/${encodeURIComponent(filename)}`;
                window.location.href = downloadUrl;
            }
        });
    </script>
@endpush
