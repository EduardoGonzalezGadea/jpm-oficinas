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
                        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#modalIngresoCheque">
                            <i class="fas fa-book mr-1"></i>Ingreso de Cheques
                        </button>
                    </div>
                </div>
                <div class="card-body p-1 pt-2">
                    <div class="nav nav-pills d-print-none" id="cheque-menu" role="tablist">
                        <a href="#emitir" class="nav-link active py-1 px-2" data-toggle="pill" role="tab" data-tab="emitir">
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
                        <div class="tab-pane fade show active" id="emitir">
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
<div wire:ignore.self class="modal fade" id="modalIngresoCheque" tabindex="-1" role="dialog" aria-labelledby="modalIngresoChequeLabel" aria-hidden="true">
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
        const libretaFormSelector = '#modalIngresoCheque form';

        function setupLibretaFormNavigation() {
            const form = $(libretaFormSelector);
            if (!form.length) return;

            form.off('keydown.libretaNav');
            form.on('keydown.libretaNav', function(e) {
                if (e.key === 'Enter') {
                    const focusable = form.find('input:not([readonly]), select, button[type="submit"]');
                    const currentElement = $(document.activeElement);
                    const currentIndex = focusable.index(currentElement);

                    if (currentElement.is('button[type="submit"]')) {
                        return;
                    }

                    e.preventDefault();
                    if (currentIndex > -1 && (currentIndex + 1) < focusable.length) {
                        const nextElement = focusable.eq(currentIndex + 1);
                        nextElement.focus();
                    }
                }
            });
        }

        function getActiveTab() {
            return localStorage.getItem('activeChequeTab') || 'emitir';
        }

        function saveActiveTab(tabName) {
            localStorage.setItem('activeChequeTab', tabName);
        }

        function refreshTabData(tabName) {
            if (tabName === 'emitir') {
                Livewire.dispatch('refreshEmitir');
            } else if (tabName === 'planillas') {
                Livewire.dispatch('refreshPlanillas');
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

        $('#modalIngresoCheque').on('shown.bs.modal', function() {
            setupLibretaFormNavigation();
        });
    });

    document.addEventListener('livewire:init', function() {
        Livewire.hook('commit', ({ component, succeed }) => {
            succeed(() => {
                if (component.name === 'tesoreria.cheque.cheque-libreta') {
                    queueMicrotask(function() {
                        const form = $('#modalIngresoCheque form');
                        if (form.length) form.trigger('focus');
                    });
                }
            });
        });

        Livewire.on('close-modal', (payload) => {
            const modalId = typeof payload === 'string' ? payload : (payload && payload.modalId);
            if (modalId) {
                $('#' + modalId).modal('hide');
            }
        });
    });
</script>
@endpush