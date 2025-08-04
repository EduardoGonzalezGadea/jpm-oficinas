@extends('layouts.app')

@section('titulo', 'Valores - JPM Oficinas')

@section('contenido')
    <div class="spa-container">
        <nav class="nav navbar-expand-md nav-pills nav-justified">
            <li class="nav-item">
                <a class="nav-link" href="{{ route('tesoreria.valores.stock') }}" wire:navigate>STOCK</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="{{ route('tesoreria.valores.libretas') }}" wire:navigate>LIBRETAS</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="{{ route('tesoreria.valores.recibos') }}" wire:navigate>RECIBOS</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="{{ route('tesoreria.valores.entradas') }}" wire:navigate>ENTRADAS</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="{{ route('tesoreria.valores.salidas') }}" wire:navigate>SALIDAS</a>
            </li>
        </nav>
        <hr class="mt-0 mb-1">
        <div class="spa-content">
            @livewire($component, $data ?? [])
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        function updateActiveLink() {
            const links = document.querySelectorAll('.nav-pills .nav-link');
            const currentUrl = window.location.href;

            links.forEach(link => {
                // Normaliza las URLs para evitar problemas con barras finales, etc.
                if (link.href.replace(/\/$/, "") === currentUrl.replace(/\/$/, "")) {
                    link.classList.add('active');
                } else {
                    link.classList.remove('active');
                }
            });
        }

        // Se ejecuta cuando el DOM inicial está listo
        document.addEventListener('DOMContentLoaded', updateActiveLink);

        // Se ejecuta después de que Livewire ha navegado a una nueva página
        document.addEventListener('livewire:navigated', updateActiveLink);

        // Agrega un listener a cada enlace para una respuesta visual inmediata
        document.querySelectorAll('.nav-pills .nav-link').forEach(link => {
            link.addEventListener('click', function() {
                // Elimina 'active' de todos los enlaces del grupo
                document.querySelectorAll('.nav-pills .nav-link').forEach(l => l.classList.remove('active'));
                // Agrega 'active' solo al enlace clickeado
                this.classList.add('active');
            });
        });
    </script>
@endsection
