{{-- resources/views/tesoreria/valores/stock.blade.php --}}
@extends('layouts.valores')

@section('breadcrumb')
    <li class="breadcrumb-item">
        <a href="{{ route('tesoreria.valores.index') }}">Valores</a>
    </li>
    <li class="breadcrumb-item active">
        <a href="{{ route('tesoreria.valores.stock') }}">Resumen de Stock</a>
    </li>
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
