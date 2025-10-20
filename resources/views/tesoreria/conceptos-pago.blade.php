@extends('layouts.app')

@section('title', 'Gestión de Conceptos de Pago')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Gestión de Conceptos de Pago</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#modalConcepto">
                            <i class="fas fa-plus"></i> Nuevo Concepto
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="input-group">
                                <input type="text" class="form-control" placeholder="Buscar conceptos..." id="searchInput">
                                <div class="input-group-append">
                                    <button class="btn btn-outline-secondary" type="button" id="searchButton">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover" id="tablaConceptos">
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Descripción</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Los datos se cargarán dinámicamente -->
                            </tbody>
                        </table>
                    </div>
                    <div id="paginacion" class="mt-3">
                        <!-- La paginación se cargará dinámicamente -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Crear/Editar Concepto -->
<div class="modal fade" id="modalConcepto" tabindex="-1" role="dialog" aria-labelledby="modalConceptoLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalConceptoLabel">Nuevo Concepto de Pago</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="formConcepto">
                <div class="modal-body">
                    <input type="hidden" id="conceptoId">
                    <div class="form-group">
                        <label for="nombre">Nombre</label>
                        <input type="text" class="form-control" id="nombre" name="nombre" required>
                    </div>
                    <div class="form-group">
                        <label for="descripcion">Descripción</label>
                        <textarea class="form-control" id="descripcion" name="descripcion" rows="3"></textarea>
                    </div>
                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="activo" name="activo" checked>
                            <label class="custom-control-label" for="activo">Activo</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    $(document).ready(function() {
        let currentPage = 1;

        // Cargar datos iniciales
        cargarConceptos();

        // Evento de búsqueda
        $('#searchButton, #searchInput').on('click keyup', function(e) {
            if (e.type === 'click' || e.keyCode === 13) {
                currentPage = 1;
                cargarConceptos();
            }
        });

        // Manejo del formulario
        $('#formConcepto').on('submit', function(e) {
            e.preventDefault();
            const conceptoId = $('#conceptoId').val();
            const data = {
                nombre: $('#nombre').val(),
                descripcion: $('#descripcion').val(),
                activo: $('#activo').prop('checked')
            };

            const url = conceptoId ? `/tesoreria/conceptos-pago/${conceptoId}` : '/tesoreria/conceptos-pago';
            const method = conceptoId ? 'PUT' : 'POST';

            $.ajax({
                url: url,
                method: method,
                data: data,
                success: function(response) {
                    $('#modalConcepto').modal('hide');
                    cargarConceptos();
                    toastr.success('Concepto guardado exitosamente');
                },
                error: function(xhr) {
                    mostrarErrores(xhr.responseJSON.errors);
                }
            });
        });

        // Función para cargar conceptos
        function cargarConceptos() {
            const search = $('#searchInput').val();
            $.get(`/tesoreria/conceptos-pago?page=${currentPage}&search=${search}`, function(data) {
                const tabla = $('#tablaConceptos tbody');
                tabla.empty();

                data.data.forEach(concepto => {
                    tabla.append(`
                        <tr>
                            <td>${concepto.nombre}</td>
                            <td>${concepto.descripcion || ''}</td>
                            <td>
                                <span class="badge badge-${concepto.activo ? 'success' : 'danger'}">
                                    ${concepto.activo ? 'Activo' : 'Inactivo'}
                                </span>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-info" onclick="editarConcepto(${concepto.id})">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="eliminarConcepto(${concepto.id})">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    `);
                });

                actualizarPaginacion(data);
            });
        }

        // Función para actualizar paginación
        function actualizarPaginacion(data) {
            const paginacion = $('#paginacion');
            paginacion.empty();

            if (data.total > data.per_page) {
                let html = '<ul class="pagination justify-content-center">';

                // Botón anterior
                html += `
                    <li class="page-item ${data.current_page === 1 ? 'disabled' : ''}">
                        <a class="page-link" href="#" data-page="${data.current_page - 1}">Anterior</a>
                    </li>
                `;

                // Números de página
                for (let i = 1; i <= data.last_page; i++) {
                    html += `
                        <li class="page-item ${data.current_page === i ? 'active' : ''}">
                            <a class="page-link" href="#" data-page="${i}">${i}</a>
                        </li>
                    `;
                }

                // Botón siguiente
                html += `
                    <li class="page-item ${data.current_page === data.last_page ? 'disabled' : ''}">
                        <a class="page-link" href="#" data-page="${data.current_page + 1}">Siguiente</a>
                    </li>
                `;

                html += '</ul>';
                paginacion.html(html);

                // Evento de paginación
                $('.pagination .page-link').on('click', function(e) {
                    e.preventDefault();
                    currentPage = $(this).data('page');
                    cargarConceptos();
                });
            }
        }

        // Resetear formulario al abrir modal
        $('#modalConcepto').on('show.bs.modal', function() {
            $('#formConcepto')[0].reset();
            $('#conceptoId').val('');
            $('#modalConceptoLabel').text('Nuevo Concepto de Pago');
        });
    });

    // Función para editar concepto
    function editarConcepto(id) {
        $.get(`/tesoreria/conceptos-pago/${id}`, function(concepto) {
            $('#conceptoId').val(concepto.id);
            $('#nombre').val(concepto.nombre);
            $('#descripcion').val(concepto.descripcion);
            $('#activo').prop('checked', concepto.activo);

            $('#modalConceptoLabel').text('Editar Concepto de Pago');
            $('#modalConcepto').modal('show');
        });
    }

    // Función para eliminar concepto
    function eliminarConcepto(id) {
        if (confirm('¿Está seguro de que desea eliminar este concepto?')) {
            $.ajax({
                url: `/tesoreria/conceptos-pago/${id}`,
                method: 'DELETE',
                success: function() {
                    cargarConceptos();
                    toastr.success('Concepto eliminado exitosamente');
                },
                error: function(xhr) {
                    if (xhr.status === 422) {
                        toastr.error('No se puede eliminar el concepto porque tiene pagos asociados');
                    } else {
                        toastr.error('Error al eliminar el concepto');
                    }
                }
            });
        }
    }

    // Función para mostrar errores
    function mostrarErrores(errors) {
        Object.keys(errors).forEach(key => {
            toastr.error(errors[key][0]);
        });
    }
</script>
@endpush
