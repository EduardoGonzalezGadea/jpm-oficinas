@extends('layouts.app')

@section('title', 'Gestión de Cheques')

@section('content')
<div class="container-fluid px-1 py-1">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header py-1">
                    <h3 class="card-title h5 mb-0">
                        <i class="fas fa-money-check mr-2"></i>Gestión de Cheques
                    </h3>
                </div>
                <div class="card-body p-1 pt-0">
                    <div class="nav nav-pills mb-2" id="cheque-menu" role="tablist">
                        <a href="#emitir" class="nav-link py-1 px-2" data-toggle="pill" role="tab" data-tab="emitir">
                            <i class="fas fa-paper-plane mr-1"></i>Cheques
                        </a>
                        <a href="#planillas" class="nav-link py-1 px-2" data-toggle="pill" role="tab" data-tab="planillas">
                            <i class="fas fa-file-alt mr-1"></i>Planillas
                        </a>
                        <a href="#reportes" class="nav-link py-1 px-2" data-toggle="pill" role="tab" data-tab="reportes">
                            <i class="fas fa-chart-bar mr-1"></i>Reportes
                        </a>
                        <a href="#libreta" class="nav-link py-1 px-2" data-toggle="pill" role="tab" data-tab="libreta">
                            <i class="fas fa-book mr-1"></i>Ingreso de Cheques
                        </a>
                    </div>
                    <hr class="mt-0 mb-3">
                    <div class="tab-content">
                        <div class="tab-pane fade" id="emitir">
                            @livewire('tesoreria.cheque.cheque-emitir')
                        </div>
                        <div class="tab-pane fade" id="planillas">
                            @livewire('tesoreria.cheque.planillas-index')
                        </div>
                        <div class="tab-pane fade" id="reportes">
                            @livewire('tesoreria.cheque.cheque-reportes')
                        </div>
                        <div class="tab-pane fade" id="libreta">
                            @livewire('tesoreria.cheque.cheque-libreta')
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        const libretaFormSelector = '#libreta form';

        function setupLibretaFormNavigation() {
            console.log('Setting up libreta form navigation.');
            const form = $(libretaFormSelector);
            if (!form.length) {
                console.log('Libreta form not found.');
                return;
            }

            // Detach any existing listener to avoid duplicates
            form.off('keydown.libretaNav');
            console.log('Existing keydown.libretaNav listener detached.');

            // Attach the new listener
            form.on('keydown.libretaNav', function(e) {
                console.log('Keydown event in libreta form. Key:', e.key, 'Target:', e.target);
                if (e.key === 'Enter') {
                    const focusable = form.find('input:not([readonly]), select, button[type="submit"]');
                    const currentElement = $(document.activeElement);
                    const currentIndex = focusable.index(currentElement);

                    if (currentElement.is('button[type="submit"]')) {
                        console.log('Enter pressed on submit button. Allowing default.');
                        // Allow form submission if Enter is pressed on the button
                        return;
                    }

                    e.preventDefault();
                    console.log('Prevented default. Moving focus.');

                    if (currentIndex > -1 && (currentIndex + 1) < focusable.length) {
                        const nextElement = focusable.eq(currentIndex + 1);
                        nextElement.focus();
                        console.log('Focused next element:', nextElement);
                    } else {
                        console.log('No next element to focus.');
                    }
                }
            });
            console.log('New keydown.libretaNav listener attached.');
        }

        function getActiveTab() {
            return localStorage.getItem('activeChequeTab') || 'emitir';
        }

        function saveActiveTab(tabName) {
            localStorage.setItem('activeChequeTab', tabName);
        }

        function refreshTabData(tabName) {
            if (tabName === 'emitir') {
                Livewire.emit('refreshEmitir');
            } else if (tabName === 'planillas') {
                Livewire.emit('refreshPlanillas');
            } else if (tabName === 'libreta') {
                Livewire.emit('refreshLibreta');
            }
        }

        // Set the active tab on page load
        let activeTab = getActiveTab();
        let tabLink = $(`#cheque-menu a[data-tab="${activeTab}"]`);
        if (tabLink.length) {
            tabLink.tab('show');
        } else {
            $('#cheque-menu a:first').tab('show');
        }

        // Handle actions AFTER the tab has been shown
        $('#cheque-menu a[data-toggle="pill"]').on('shown.bs.tab', function(e) {
            let tabName = $(e.target).data('tab');
            saveActiveTab(tabName);
            refreshTabData(tabName);

            if (tabName === 'libreta') {
                // Use requestAnimationFrame for robust focus after tab switch and rendering
                requestAnimationFrame(() => {
                    const serieInput = $('#serie');
                    if (serieInput.length) {
                        serieInput.focus();
                    }
                });
                // Set up navigation for the form inside the libreta tab
                setupLibretaFormNavigation();
            }
        });

        // Also set up navigation on Livewire updates for the libreta component
        if (typeof Livewire !== 'undefined') {
            Livewire.hook('message.processed', (message, component) => {
                // Check if the updated component is the cheque-libreta one
                if (component.name === 'tesoreria.cheque.cheque-libreta') {
                    setupLibretaFormNavigation();
                }
            });
        }
    });
</script>
@endpush
