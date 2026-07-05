@extends('layouts.app')

@section('title', 'Tesorería | Oficinas - Caja Chica')

@push('styles')
<style>
    .fila-mes-anterior {
        background-color: rgba(253, 126, 20, 0.08) !important;
        border-left: 3px solid var(--color-accent) !important;
    }
    .table-container thead th {
        position: sticky;
        top: 0;
        z-index: 1;
        box-shadow: 0 1px 2px rgba(0,0,0,0.1);
    }
    @media (prefers-color-scheme: dark) {
        .table-container thead th {
            background-color: #343a40;
            color: #ffffff;
        }
    }
</style>
@endpush

@section('content')
    <div class="container-fluid py-0 px-0">
        <livewire:tesoreria.caja-chica.index />
    </div>
@endsection

@push('scripts')
<script src="{{ asset('js/tesoreria/caja-chica.js') }}"></script>
@if (session()->has('message'))
<script>
    Swal.fire({icon: 'success', title: 'Éxito', text: {!! json_encode(session('message')) !!}, toast: true, position: 'top-end', showConfirmButton: false, timer: 4000, timerProgressBar: true});
</script>
@endif
@if (session()->has('error'))
<script>
    Swal.fire({icon: 'error', title: 'Error', text: {!! json_encode(session('error')) !!}, toast: true, position: 'top-end', showConfirmButton: false, timer: 4000, timerProgressBar: true});
</script>
@endif
@endpush
