@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="mb-0">Gestión de Respaldos</h3>
                    </div>

                    <div class="card-body">
                        <div class="container">
                            <!-- Spinner Overlay -->
                            <div id="spinner-overlay"
                                style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(255,255,255,0.7); z-index:9999;">
                                <div
                                    style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%;">
                                    <div class="spinner-border text-primary" style="width: 4rem; height: 4rem;" role="status">
                                        <span class="sr-only">Procesando...</span>
                                    </div>
                                    <div class="mt-3 text-dark font-weight-bold">Procesando, por favor espere...</div>
                                </div>
                            </div>
                            <div class="row justify-content-center">
                                <div class="col-md-12">
                                    <div class="card">
                                        <div class="card-header">
                                            <h3 class="mb-0">Gestión de Respaldos</h3>
                                        </div>

                                        <div class="card-body">
                                            <div class="mb-4">
                                                <button id="btn-crear-respaldo" class="btn btn-primary">
                                                    <i class="fas fa-plus mr-2"></i>Crear Nuevo Respaldo
                                                </button>
                                            </div>

                                            <div class="table-responsive">
                                                <table class="table table-striped">
                                                    <thead>
                                                        <tr>
                                                            <th>Nombre del Archivo</th>
                                                            <th>Tamaño</th>
                                                            <th>Fecha</th>
                                                            <th>Acciones</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @forelse ($backups as $backup)
                                                            <tr>
                                                                <td>{{ $backup['name'] }}</td>
                                                                <td>{{ number_format($backup['size'] / 1048576, 2) }} MB
                                                                </td>
                                                                <td>{{ \Carbon\Carbon::createFromTimestamp($backup['date'])->format('d/m/Y H:i:s') }}
                                                                </td>
                                                                <td>
                                                                    <button class="btn btn-warning btn-sm btn-restaurar"
                                                                        data-file="{{ $backup['file'] }}">
                                                                        <i class="fas fa-undo mr-1"></i>Restaurar
                                                                    </button>
                                                                    <a href="{{ route('system.backups.download', ['file' => $backup['file']]) }}"
                                                                        class="btn btn-info btn-sm">
                                                                        <i class="fas fa-download mr-1"></i>Descargar
                                                                    </a>
                                                                </td>
                                                            </tr>
                                                        @empty
                                                            <tr>
                                                                <td colspan="4" class="text-center">No hay respaldos
                                                                    disponibles</td>
                                                            </tr>
                                                        @endforelse
                                                </table>

                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        $(function() {
            // Spinner helpers
            function showSpinner() {
                $('#spinner-overlay').fadeIn(200);
            }

            function hideSpinner() {
                $('#spinner-overlay').fadeOut(200);
            }

            // Crear respaldo
            $('#btn-crear-respaldo').on('click', function(e) {
                e.preventDefault();
                Swal.fire({
                    title: '¿Crear nuevo respaldo?',
                    text: 'Esto puede tardar unos minutos. ¿Desea continuar?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, crear respaldo',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        showSpinner();
                        $.ajax({
                            url: '{{ route('system.backups.create') }}',
                            method: 'GET',
                            success: function(data) {
                                hideSpinner();
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Respaldo creado',
                                    text: 'El respaldo se ha creado correctamente.'
                                }).then(() => {
                                    window.location.reload();
                                });
                            },
                            error: function(xhr) {
                                hideSpinner();
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: xhr.responseJSON?.message ||
                                        'Ocurrió un error al crear el respaldo.'
                                });
                            }
                        });
                    }
                });
            });

            // Restaurar respaldo
            $('.btn-restaurar').on('click', function(e) {
                e.preventDefault();
                var file = $(this).data('file');
                Swal.fire({
                    title: '¿Restaurar respaldo?',
                    text: 'Esta acción sobrescribirá la base de datos actual. ¿Desea continuar?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, restaurar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        showSpinner();
                        $.ajax({
                            url: '{{ route('system.backups.restore') }}',
                            method: 'POST',
                            data: {
                                backup: file,
                                _token: '{{ csrf_token() }}'
                            },
                            success: function(data) {
                                hideSpinner();
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Restauración completada',
                                    text: 'La base de datos fue restaurada correctamente.'
                                }).then(() => {
                                    window.location.reload();
                                });
                            },
                            error: function(xhr) {
                                hideSpinner();
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: xhr.responseJSON?.message ||
                                        'Ocurrió un error al restaurar la base de datos.'
                                });
                            }
                        });
                    }
                });
            });
        });
    </script>
@endsection
