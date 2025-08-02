{{-- resources/views/tesoreria/valores/stock.blade.php --}}
@extends('layouts.tesoreria')

@section('breadcrumb')
    <li class="breadcrumb-item">Valores</li>
    <li class="breadcrumb-item active">Resumen de Stock</li>
@endsection

@section('page-content')
    @livewire('tesoreria.valores.stock')
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Actualizar automáticamente cada 5 minutos
    setInterval(function() {
        Livewire.emit('refreshComponent');
    }, 300000); // 5 minutos
});
</script>
@endpush