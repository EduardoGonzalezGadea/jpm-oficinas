<?php

namespace App\Livewire\Tesoreria\LibroDiario;

use App\Models\Tesoreria\LbConcepto;
use App\Models\Tesoreria\LbDetalle;
use App\Models\Tesoreria\LbTipo;
use App\Models\Tesoreria\MedioDePago;
use App\Models\Tesoreria\LibroDiario;
use App\Models\Tesoreria\TesCfe;
use App\Services\Tesoreria\LibroDiarioService;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class Asientos extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';
    protected LibroDiarioService $service;

    public $search = '';
    public $fecha_desde = '';
    public $fecha_hasta = '';
    public $filtro_tipo_id = '';
    public $filtro_concepto_id = '';
    public $anio;

    public $selectedItem = null;
    public $showCreateModal = false;
    public $showEditModal = false;
    public $showRedistribucionModal = false;
    public $showDetailsModal = false;

    public $fecha;
    public $tipo_id;
    public $concepto_id;
    public $detalle_id;
    public $medio_id;
    public $monto;
    public $identidad;
    public $denominacion;

    public $conceptos = [];
    public $detalles = [];
    public $tipos = [];
    public $medios = [];

    public $edit_id;
    public $edit_identidad;
    public $edit_denominacion;

    public $rd_fecha;
    public $rd_origen_concepto_id;
    public $rd_origen_detalle_id;
    public $rd_origen_identidad;
    public $rd_origen_denominacion;
    public $rd_destino_concepto_id;
    public $rd_destino_detalle_id;
    public $rd_destino_identidad;
    public $rd_destino_denominacion;
    public $rd_medio_id;
    public $rd_monto;

    public $rd_origen_detalles = [];
    public $rd_destino_detalles = [];

    protected $listeners = ['destroy' => 'destroy', 'confirmarEliminarAsientoConCfe', 'refreshComponent' => '$refresh'];

    public function boot(LibroDiarioService $service)
    {
        $this->service = $service;
    }

    public function mount()
    {
        $this->anio = now()->year;
        $this->fecha_desde = now()->startOfMonth()->format('Y-m-d');
        $this->fecha_hasta = now()->endOfMonth()->format('Y-m-d');
        $this->tipos = LbTipo::ordenado()->get();
        $this->medios = MedioDePago::libroDiario()->activos()->ordenado()->get();
        $this->conceptos = LbConcepto::ordenado()->get();
    }

    public function render()
    {
        $filtros = [
            'fecha_desde' => $this->fecha_desde,
            'fecha_hasta' => $this->fecha_hasta,
            'tipo_id' => $this->filtro_tipo_id ?: null,
            'concepto_id' => $this->filtro_concepto_id ?: null,
            'search' => $this->search,
            'anio' => $this->anio,
        ];

        $items = $this->service->listar($filtros, 25);
        $totales = [
            'entradas' => $items->sum(fn($i) => $i->signo_efectivo === 1 ? $i->monto : 0),
            'salidas' => $items->sum(fn($i) => $i->signo_efectivo === -1 ? $i->monto : 0),
        ];

        return view('livewire.tesoreria.libro-diario.asientos', [
            'items' => $items,
            'totales' => $totales,
        ])->extends('layouts.app')->section('content');
    }

    public function openCreateModal()
    {
        $this->resetCreateForm();
        $this->dispatch('show-modal', ['id' => 'createModal']);
    }

    public function openRedistribucionModal()
    {
        $this->resetRedistribucionForm();
        $this->dispatch('show-modal', ['id' => 'redistribucionModal']);
    }

    public function updatedTipoId()
    {
        $this->detalle_id = null;
        $this->detalles = $this->concepto_id
            ? LbDetalle::where('concepto_id', $this->concepto_id)->ordenado()->get()
            : [];
    }

    public function updatedConceptoId()
    {
        $this->detalle_id = null;
        $this->detalles = $this->concepto_id
            ? LbDetalle::where('concepto_id', $this->concepto_id)->activos()->ordenado()->get()
            : [];
    }

    public function updatedRdOrigenConceptoId()
    {
        $this->rd_origen_detalle_id = null;
        $this->rd_origen_detalles = $this->rd_origen_concepto_id
            ? LbDetalle::where('concepto_id', $this->rd_origen_concepto_id)->activos()->ordenado()->get()
            : [];
    }

    public function updatedRdDestinoConceptoId()
    {
        $this->rd_destino_detalle_id = null;
        $this->rd_destino_detalles = $this->rd_destino_concepto_id
            ? LbDetalle::where('concepto_id', $this->rd_destino_concepto_id)->activos()->ordenado()->get()
            : [];
    }

    public function store()
    {
        $this->identidad = mb_strtoupper($this->identidad ?? '');
        $this->denominacion = mb_strtoupper($this->denominacion ?? '');
        $this->validate([
            'fecha' => 'required|date',
            'tipo_id' => 'required|exists:tes_lb_tipos,id',
            'concepto_id' => 'required|exists:tes_lb_conceptos,id',
            'detalle_id' => 'required|exists:tes_lb_detalle,id',
            'medio_id' => 'required|exists:tes_medio_de_pagos,id',
            'monto' => 'required|numeric|min:0.01',
            'identidad' => 'nullable|string|max:255',
            'denominacion' => 'nullable|string|max:255',
        ]);

        $this->service->registrarAsiento([
            'fecha' => $this->fecha,
            'tipo_id' => $this->tipo_id,
            'concepto_id' => $this->concepto_id,
            'detalle_id' => $this->detalle_id,
            'medio_id' => $this->medio_id,
            'monto' => $this->monto,
            'identidad' => $this->identidad,
            'denominacion' => $this->denominacion,
        ]);

        $this->resetCreateForm();
        $this->dispatch('alert', ['type' => 'success', 'message' => 'Asiento registrado con éxito!', 'toast' => true]);
        $this->dispatch('close-modal', ['id' => 'createModal']);
    }

    public function storeRedistribucion()
    {
        $this->rd_origen_identidad = mb_strtoupper($this->rd_origen_identidad ?? '');
        $this->rd_origen_denominacion = mb_strtoupper($this->rd_origen_denominacion ?? '');
        $this->rd_destino_identidad = mb_strtoupper($this->rd_destino_identidad ?? '');
        $this->rd_destino_denominacion = mb_strtoupper($this->rd_destino_denominacion ?? '');
        $this->validate([
            'rd_fecha' => 'required|date',
            'rd_origen_concepto_id' => 'required|exists:tes_lb_conceptos,id',
            'rd_origen_detalle_id' => 'required|exists:tes_lb_detalle,id',
            'rd_destino_concepto_id' => 'required|exists:tes_lb_conceptos,id',
            'rd_destino_detalle_id' => 'required|exists:tes_lb_detalle,id',
            'rd_medio_id' => 'required|exists:tes_medio_de_pagos,id',
            'rd_monto' => 'required|numeric|min:0.01',
            'rd_destino_identidad' => 'nullable|string|max:255',
            'rd_destino_denominacion' => 'nullable|string|max:255',
        ]);

        $origen = [
            'fecha' => $this->rd_fecha,
            'concepto_id' => $this->rd_origen_concepto_id,
            'detalle_id' => $this->rd_origen_detalle_id,
            'medio_id' => $this->rd_medio_id,
            'monto' => $this->rd_monto,
            'identidad' => $this->rd_origen_identidad,
            'denominacion' => $this->rd_origen_denominacion,
        ];

        $destino = [
            'fecha' => $this->rd_fecha,
            'concepto_id' => $this->rd_destino_concepto_id,
            'detalle_id' => $this->rd_destino_detalle_id,
            'medio_id' => $this->rd_medio_id,
            'monto' => $this->rd_monto,
            'identidad' => $this->rd_destino_identidad,
            'denominacion' => $this->rd_destino_denominacion,
        ];

        $this->service->registrarRedistribucion($origen, $destino);

        $this->resetRedistribucionForm();
        $this->dispatch('alert', ['type' => 'success', 'message' => 'Redistribución registrada con éxito!', 'toast' => true]);
        $this->dispatch('close-modal', ['id' => 'redistribucionModal']);
    }

    public function showDetails($id)
    {
        $this->selectedItem = LibroDiario::with(['tipo', 'concepto', 'detalle', 'medio', 'parent', 'children'])->findOrFail($id);
        $this->dispatch('show-modal', ['id' => 'detailsModal']);
    }

    public function openEditModal($id)
    {
        $entry = LibroDiario::findOrFail($id);
        $this->edit_id = $id;
        $this->edit_identidad = $entry->identidad;
        $this->edit_denominacion = $entry->denominacion;
        $this->dispatch('show-modal', ['id' => 'editModal']);
    }

    public function update()
    {
        $this->edit_identidad = mb_strtoupper($this->edit_identidad ?? '');
        $this->edit_denominacion = mb_strtoupper($this->edit_denominacion ?? '');
        $this->validate([
            'edit_identidad' => 'nullable|string|max:255',
            'edit_denominacion' => 'nullable|string|max:255',
        ]);

        $this->service->actualizarCamposNoFinancieros($this->edit_id, [
            'identidad' => $this->edit_identidad,
            'denominacion' => $this->edit_denominacion,
        ]);

        $this->resetEditForm();
        $this->dispatch('alert', ['type' => 'success', 'message' => 'Asiento actualizado con éxito!', 'toast' => true]);
        $this->dispatch('close-modal', ['id' => 'editModal']);
    }

    public function destroy($id)
    {
        try {
            $entry = LibroDiario::with('cfe')->find($id);

            if (!$entry) {
                throw new \RuntimeException('Asiento no encontrado.');
            }

            if ($entry->cch_origen_type) {
                throw new \RuntimeException('No se puede eliminar un asiento generado por Caja Chica desde el Libro Diario. Use el módulo de Caja Chica.');
            }

            if ($entry->cfe_id) {
                $cfe = TesCfe::withCount(['items as items_en_planilla_count' => fn($q) => $q->whereNotNull('planilla_er_id')])->find($entry->cfe_id);
                if ($cfe && $cfe->items_en_planilla_count > 0) {
                    throw new \RuntimeException('No se puede eliminar el asiento porque la recaudación asociada ya tiene ítems en Planilla para Estado de Recaudación.');
                }

                $this->dispatch('swal:confirmar-eliminar-asiento-con-cfe', [
                    'asientoId' => $id,
                    'cfeSerie' => $cfe->documento_serie ? "{$cfe->documento_serie}-" : '',
                    'cfeNumero' => $cfe->documento_numero,
                    'cfeTipo' => $cfe->documento_tipo,
                ]);
            } else {
                $this->service->eliminarAsiento($id);
                $this->dispatch('alert', ['type' => 'success', 'message' => 'Asiento eliminado y saldos recalculados.', 'toast' => true]);
            }
        } catch (\RuntimeException $e) {
            $this->dispatch('alert', ['type' => 'error', 'message' => $e->getMessage(), 'toast' => true]);
        }
    }

    #[On('confirmarEliminarAsientoConCfe')]
    public function confirmarEliminarAsientoConCfe($id)
    {
        try {
            $this->service->eliminarAsientoConCfe($id);
            $this->dispatch('alert', ['type' => 'success', 'message' => 'Asiento y CFE asociado eliminados. Saldos recalculados.', 'toast' => true]);
        } catch (\RuntimeException $e) {
            $this->dispatch('alert', ['type' => 'error', 'message' => $e->getMessage(), 'toast' => true]);
        }
    }

    public function resetCreateForm()
    {
        $this->showCreateModal = false;
        $this->fecha = now()->format('Y-m-d');
        $this->tipo_id = null;
        $this->concepto_id = null;
        $this->detalle_id = null;
        $this->medio_id = null;
        $this->monto = null;
        $this->identidad = null;
        $this->denominacion = null;
        $this->detalles = [];
    }

    public function resetRedistribucionForm()
    {
        $this->showRedistribucionModal = false;
        $this->rd_fecha = now()->format('Y-m-d');
        $this->rd_origen_concepto_id = null;
        $this->rd_origen_detalle_id = null;
        $this->rd_origen_identidad = null;
        $this->rd_origen_denominacion = null;
        $this->rd_destino_concepto_id = null;
        $this->rd_destino_detalle_id = null;
        $this->rd_destino_identidad = null;
        $this->rd_destino_denominacion = null;
        $this->rd_medio_id = null;
        $this->rd_monto = null;
        $this->rd_origen_detalles = [];
        $this->rd_destino_detalles = [];
    }

    public function resetEditForm()
    {
        $this->showEditModal = false;
        $this->edit_id = null;
        $this->edit_identidad = null;
        $this->edit_denominacion = null;
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingFiltroTipoId()
    {
        $this->resetPage();
    }

    public function updatingFiltroConceptoId()
    {
        $this->resetPage();
    }

    public function limpiarFiltros()
    {
        $this->search = '';
        $this->filtro_tipo_id = '';
        $this->filtro_concepto_id = '';
        $this->fecha_desde = now()->startOfMonth()->format('Y-m-d');
        $this->fecha_hasta = now()->endOfMonth()->format('Y-m-d');
        $this->resetPage();
    }

    public function getMontoFormateadoProperty()
    {
        return fn($monto) => '$ ' . number_format($monto, 2, ',', '.');
    }
}
