@extends('layouts.app')

@section('title', 'Gestión de Pagos')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Gestión de Pagos</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#modalPago">
                            <i class="fas fa-plus"></i> Nuevo Pago
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="input-group">
                                <input type="text" class="form-control" placeholder="Buscar pagos..." id="searchInput">
                                <div class="input-group-append">
                                    <button class="btn btn-outline-secondary" type="button" id="searchButton">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover" id="tablaPagos">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Concepto</th>
                                    <th>Monto</th>
                                    <th>Medio de Pago</th>
                                    <th>Nº Comprobante</th>
                                    <th>Descripción</th>
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

<!-- Modal para Crear/Editar Pago -->
<!-- Modal para Crear/Editar Pago -->
<x-modal id="modalPago" title="Nuevo Pago">
    <form id="formPago">
        <input type="hidden" id="pagoId">
        <div class="form-group">
            <label for="fecha">Fecha</label>
            <input type="date" class="form-control" id="fecha" name="fecha" required>
        </div>
        <div class="form-group">
            <label for="concepto_id">Concepto</label>
            <select class="form-control" id="concepto_id" name="concepto_id" required>
                <!-- Los conceptos se cargarán dinámicamente -->
            </select>
        </div>
        <div class="form-group">
            <label for="monto">Monto</label>
            <input type="number" class="form-control" id="monto" name="monto" step="0.01" required>
        </div>
        <div class="form-group">
            <label for="medio_pago">Medio de Pago</label>
            <input type="text" class="form-control" id="medio_pago" name="medio_pago" required>
        </div>
        <div class="form-group">
            <label for="numero_comprobante">Nº Comprobante</label>
            <input type="text" class="form-control" id="numero_comprobante" name="numero_comprobante">
        </div>
        <div class="form-group">
            <label for="descripcion">Descripción</label>
            <textarea class="form-control" id="descripcion" name="descripcion" rows="3"></textarea>
        </div>
    </form>
    <x-slot name="footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-primary" form="formPago">Guardar</button>
    </x-slot>
</x-modal>

@push('scripts')
<script>
    $(document).ready(function() {
        let currentPage = 1;

        // Cargar datos iniciales
        cargarPagos();
        cargarConceptos();

        // Evento de búsqueda
        $('#searchButton, #searchInput').on('click keyup', function(e) {
            if (e.type === 'click' || e.keyCode === 13) {
                currentPage = 1;
                cargarPagos();
            }
        });

        // Manejo del formulario
        $('#formPago').on('submit', function(e) {
            e.preventDefault();
            const pagoId = $('#pagoId').val();
            const data = {
                fecha: $('#fecha').val(),
                concepto_id: $('#concepto_id').val(),
                monto: $('#monto').val(),
                medio_pago: $('#medio_pago').val(),
                numero_comprobante: $('#numero_comprobante').val(),
                descripcion: $('#descripcion').val()
            };

            const url = pagoId ? `/tesoreria/pagos/${pagoId}` : '/tesoreria/pagos';
            const method = pagoId ? 'PUT' : 'POST';

            $.ajax({
                url: url,
                method: method,
                data: data,
                success: function(response) {
                    $('#modalPago').modal('hide');
                    cargarPagos();
                    toastr.success('Pago guardado exitosamente');
                },
                error: function(xhr) {
                    mostrarErrores(xhr.responseJSON.errors);
                }
            });
        });

        // Función para cargar pagos
        function cargarPagos() {
            const search = $('#searchInput').val();
            $.get(`/tesoreria/pagos?page=${currentPage}&search=${search}`, function(data) {
                const tabla = $('#tablaPagos tbody');
                tabla.empty();

                data.data.forEach(pago => {
                    tabla.append(`
                        <tr>
                            <td>${pago.fecha}</td>
                            <td>${pago.concepto ? pago.concepto.nombre : ''}</td>
                            <td>${pago.monto}</td>
                            <td>${pago.medio_pago}</td>
                            <td>${pago.numero_comprobante || ''}</td>
                            <td>${pago.descripcion || ''}</td>
                            <td>
                                <button class="btn btn-sm btn-info" onclick="editarPago(${pago.id})">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="eliminarPago(${pago.id})">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    `);
                });

                actualizarPaginacion(data);
            });
        }

        // Función para cargar conceptos
        function cargarConceptos() {
            $.get('/tesoreria/pagos/conceptos/lista', function(data) {
                const select = $('#concepto_id');
                select.empty();
                select.append('<option value="">Seleccione un concepto</option>');

                data.forEach(concepto => {
                    select.append(`<option value="${concepto.id}">${concepto.nombre}</option>`);
                });
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
                    cargarPagos();
                });
            }
        }

        // Resetear formulario al abrir modal
        $('#modalPago').on('show.bs.modal', function() {
            $('#formPago')[0].reset();
            $('#pagoId').val('');
            $('#modalPagoLabel').text('Nuevo Pago');
        });
    });

    // Función para editar pago
    function editarPago(id) {
        $.get(`/tesoreria/pagos/${id}`, function(pago) {
            $('#pagoId').val(pago.id);
            $('#fecha').val(pago.fecha);
            $('#concepto_id').val(pago.concepto_id);
            $('#monto').val(pago.monto);
            $('#medio_pago').val(pago.medio_pago);
            $('#numero_comprobante').val(pago.numero_comprobante);
            $('#descripcion').val(pago.descripcion);

            $('#modalPagoLabel').text('Editar Pago');
            $('#modalPago').modal('show');
        });
    }

    // Función para eliminar pago
    function eliminarPago(id) {
        if (confirm('¿Está seguro de que desea eliminar este pago?')) {
            $.ajax({
                url: `/tesoreria/pagos/${id}`,
                method: 'DELETE',
                success: function() {
                    cargarPagos();
                    toastr.success('Pago eliminado exitosamente');
                },
                error: function() {
                    toastr.error('Error al eliminar el pago');
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