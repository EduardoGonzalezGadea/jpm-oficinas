@extends('layouts.app')

@section('title', 'Gestión de Cheques')

@section('content')
<div class="container-fluid py-0 px-0" style="overflow-x: hidden;">
    <div class="row no-gutters">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-info text-white card-header-gradient py-2 d-flex justify-content-between align-items-center d-print-none">
                    <h4 class="card-title mb-0">
                        <strong><i class="fas fa-money-check mr-2"></i>Gestión de Cheques</strong>
                    </h4>
                    <div class="d-print-none">
                        <button class="btn btn-primary" data-toggle="modal" data-target="#modalIngresoCheque">
                            <i class="fas fa-book mr-1"></i>Ingreso de Cheques
                        </button>
                    </div>
                </div>
                <div class="card-body p-1 pt-2">
                    <div class="nav nav-pills d-print-none" id="cheque-menu" role="tablist">
                        <a href="#emitir" class="nav-link py-1 px-2" data-toggle="pill" role="tab" data-tab="emitir">
                            <i class="fas fa-paper-plane mr-1"></i>Cheques
                        </a>
                        <a href="#planillas" class="nav-link py-1 px-2" data-toggle="pill" role="tab" data-tab="planillas">
                            <i class="fas fa-file-alt mr-1"></i>Planillas
                        </a>
                        <a href="#reportes" class="nav-link py-1 px-2" data-toggle="pill" role="tab" data-tab="reportes">
                            <i class="fas fa-chart-bar mr-1"></i>Reportes
                        </a>
                    </div>
                    <hr class="mt-0 mb-3 d-print-none">
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
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Ingreso Cheque -->
<div class="modal fade" id="modalIngresoCheque" tabindex="-1" role="dialog" aria-labelledby="modalIngresoChequeLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalIngresoChequeLabel">Ingreso de Cheques</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                @livewire('tesoreria.cheque.cheque-libreta')
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
        });

        // Also set up navigation on Livewire updates for the libreta component
        if (typeof Livewire !== 'undefined') {
            Livewire.hook('message.processed', (message, component) => {
                // Check if the updated component is the cheque-libreta one
                if (component.name === 'tesoreria.cheque.cheque-libreta') {
                    setupLibretaFormNavigation();
                }
            });

            // Handle modal closing after save
            Livewire.on('close-modal', (modalId) => {
                $('#' + modalId).modal('hide');
            });
        }
    });
</script>
@endpush