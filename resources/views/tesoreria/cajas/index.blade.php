@extends('layouts.app')

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Gestión de Flujo de Caja</h3>
        </div>
        <div class="card-body">
            <ul class="nav nav-pills mb-3" id="pills-tab" role="tablist">
                <li class="nav-item" role="presentation">
                    <a class="nav-link active" id="pills-movimientos-tab" data-toggle="pill" href="#pills-movimientos"
                        role="tab" aria-controls="pills-movimientos" aria-selected="true">Movimientos del Día</a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link" id="pills-apertura-cierre-tab" data-toggle="pill" href="#pills-apertura-cierre"
                        role="tab" aria-controls="pills-apertura-cierre" aria-selected="false">Apertura y Cierre</a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link" id="pills-reportes-tab" data-toggle="pill" href="#pills-reportes" role="tab"
                        aria-controls="pills-reportes" aria-selected="false">Reportes</a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link" id="pills-configuracion-tab" data-toggle="pill" href="#pills-configuracion"
                        role="tab" aria-controls="pills-configuracion" aria-selected="false">Configuración</a>
                </li>
            </ul>
            <div class="tab-content" id="pills-tabContent">
                <div class="tab-pane fade show active" id="pills-movimientos" role="tabpanel"
                    aria-labelledby="pills-movimientos-tab">
                    @livewire('tesoreria.cajas.movimientos-diarios')
                </div>
                <div class="tab-pane fade" id="pills-apertura-cierre" role="tabpanel"
                    aria-labelledby="pills-apertura-cierre-tab">
                    @livewire('tesoreria.cajas.apertura-cierre')
                </div>
                <div class="tab-pane fade" id="pills-reportes" role="tabpanel" aria-labelledby="pills-reportes-tab">
                    {{-- @livewire('tesoreria.cajas.reportes-caja') --}}
                    <p>Contenido de Reportes...</p>
                </div>
                <div class="tab-pane fade" id="pills-configuracion" role="tabpanel"
                    aria-labelledby="pills-configuracion-tab">
                    {{-- @livewire('tesoreria.cajas.configuracion-caja') --}}
                    <p>Contenido de Configuración...</p>
                </div>
            </div>
        </div>
    </div>
@endsection
