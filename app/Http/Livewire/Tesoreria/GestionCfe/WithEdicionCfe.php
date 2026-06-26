<?php

namespace App\Http\Livewire\Tesoreria\GestionCfe;

use App\DataTransferObjects\CfeData;
use App\Models\Tesoreria\CajaConcepto;
use App\Models\Tesoreria\TesCfe;
use App\Models\Tesoreria\TesCfeItem;
use App\Models\Tesoreria\SiifDistribucion;

trait WithEdicionCfe
{
    public bool $mostrarModalEditar = false;
    public $cfeEditarId = null;
    public $editFecha = '';
    public $editCajaConceptoSeleccionado = null;
    public $editSiifDependenciaSeleccionado = null;
    public array $editItemDistribuciones = [];
    public array $editCfeItems = [];
    public bool $editConceptoRequiereDistribucion = false;
    public $editDocumentoTipo = '';
    public $editDocumentoSerie = '';
    public $editDocumentoNumero = '';

    public function editarCfe(int $cfeId): void
    {
        $cfe = TesCfe::with('items')->findOrFail($cfeId);

        if ($cfe->items->contains(fn($i) => $i->planilla_er_id !== null)) {
            $this->dispatchBrowserEvent('swal:toast-error', [
                'text' => 'No se puede editar este CFE porque uno o más de sus ítems ya integran una planilla.',
            ]);
            return;
        }

        $this->cfeEditarId = $cfe->id;
        $this->editFecha = $cfe->fecha?->format('Y-m-d') ?? '';

        $this->editDocumentoTipo = $cfe->documento_tipo ?? '';
        $this->editDocumentoSerie = $cfe->documento_serie ?? '';
        $this->editDocumentoNumero = $cfe->documento_numero ?? '';

        $this->editCfeItems = $cfe->items->map(fn($i) => [
            'id' => $i->id,
            'detalle' => $i->detalle,
            'descripcion' => $i->descripcion,
            'importe' => $i->importe,
            'siif_distribucion_id' => $i->siif_distribucion_id,
        ])->toArray();

        $this->editItemDistribuciones = [];
        foreach ($this->editCfeItems as $idx => $item) {
            $this->editItemDistribuciones[$idx] = $item['siif_distribucion_id'] ?? '';
        }

        $concepto = $cfe->tes_caja_concepto_id ? CajaConcepto::find($cfe->tes_caja_concepto_id) : null;
        $this->editConceptoRequiereDistribucion = $concepto ? $concepto->requiere_distribucion : false;

        $this->editCajaConceptoSeleccionado = $cfe->tes_caja_concepto_id;
        $this->editSiifDependenciaSeleccionado = $cfe->siif_distribucion_dependencia_id;

        $this->mostrarModalEditar = true;
        $this->dispatchBrowserEvent('abrir-modal-editar-cfe');
    }

    public function updatedEditCajaConceptoSeleccionado($value): void
    {
        $concepto = CajaConcepto::find($value);
        $this->editConceptoRequiereDistribucion = $concepto ? $concepto->requiere_distribucion : false;

        $this->resetEditItemDistribuciones();

        if (!empty($value) && !empty($this->editSiifDependenciaSeleccionado)) {
            $this->autoAsignarEditDistribuciones();
        }
    }

    public function updatedEditSiifDependenciaSeleccionado($value): void
    {
        $this->resetEditItemDistribuciones();

        if (!empty($value) && !empty($this->editCajaConceptoSeleccionado)) {
            $this->autoAsignarEditDistribuciones();
        }
    }

    private function resetEditItemDistribuciones(): void
    {
        $this->editItemDistribuciones = [];
        foreach ($this->editCfeItems as $index => $item) {
            $this->editItemDistribuciones[$index] = $item['siif_distribucion_id'] ?? '';
        }
    }

    private function autoAsignarEditDistribuciones(): void
    {
        if (empty($this->editCfeItems)) {
            return;
        }

        $concepto = CajaConcepto::find($this->editCajaConceptoSeleccionado);
        if (!$concepto || !$concepto->siif_distribucion_tipo_id) {
            return;
        }

        foreach ($this->editCfeItems as $index => $item) {
            $detalle = trim($item['detalle'] ?? '');
            if (empty($detalle)) {
                continue;
            }

            $ultimosItems = TesCfeItem::where('detalle', $detalle)
                ->whereNotNull('siif_distribucion_id')
                ->whereNull('deleted_at')
                ->orderBy('id', 'desc')
                ->take(10)
                ->get();

            if ($ultimosItems->isEmpty()) {
                continue;
            }

            $frecuencias = $ultimosItems->groupBy('siif_distribucion_id')
                ->map->count()
                ->sortDesc();

            $distribucionId = $frecuencias->keys()->first();

            $existe = SiifDistribucion::where('id', $distribucionId)
                ->where('tipo_id', $concepto->siif_distribucion_tipo_id)
                ->where('dependencia_id', $this->editSiifDependenciaSeleccionado)
                ->whereNull('deleted_at')
                ->exists();

            if ($existe) {
                $this->editItemDistribuciones[$index] = (string) $distribucionId;
            }
        }
    }

    public function guardarEdicion(): void
    {
        if (empty($this->cfeEditarId)) {
            return;
        }

        $rules = [
            'editFecha' => 'nullable|date',
            'editCajaConceptoSeleccionado' => 'required|integer|min:1|exists:tes_caja_conceptos,id',
            'editSiifDependenciaSeleccionado' => 'nullable|integer|exists:siif_distribucion_dependencias,id',
        ];

        if ($this->editConceptoRequiereDistribucion) {
            $hasMissing = false;
            foreach ($this->editCfeItems as $index => $item) {
                if (empty($this->editItemDistribuciones[$index])) {
                    $hasMissing = true;
                    break;
                }
            }

            if ($hasMissing) {
                $this->dispatchBrowserEvent('swal:toast-error', [
                    'text' => 'El concepto de caja seleccionado requiere asignar una distribución SIIF a cada uno de los ítems.',
                ]);
                return;
            }

            $rules['editItemDistribuciones.*'] = 'required|integer|exists:siif_distribucions,id';
        } else {
            $rules['editItemDistribuciones.*'] = 'nullable|integer|exists:siif_distribucions,id';
        }

        $this->validate($rules, [
            'editCajaConceptoSeleccionado.required' => 'Debe seleccionar un concepto de caja.',
            'editCajaConceptoSeleccionado.min' => 'Debe seleccionar un concepto de caja válido.',
            'editCajaConceptoSeleccionado.exists' => 'El concepto de caja seleccionado no existe.',
            'editSiifDependenciaSeleccionado.exists' => 'La dependencia de distribución SIIF seleccionada no existe.',
            'editItemDistribuciones.*.required' => 'Debe seleccionar una distribución para todos los ítems.',
            'editItemDistribuciones.*.exists' => 'La distribución SIIF seleccionada no existe.',
        ]);

        try {
            $data = new CfeData(
                fecha: $this->editFecha ?: null,
                tes_caja_concepto_id: $this->editCajaConceptoSeleccionado,
                siif_distribucion_dependencia_id: $this->editSiifDependenciaSeleccionado,
                items: $this->editCfeItems,
                item_distribuciones: $this->editItemDistribuciones,
            );

            $this->cfeCreator->updateCfe($this->cfeEditarId, $data);

            $this->dispatchBrowserEvent('cerrar-modal-editar-cfe');
            $this->cancelarEdicion();

            $this->dispatchBrowserEvent('swal:modal', [
                'type' => 'success',
                'title' => 'CFE Actualizado',
                'text' => 'El CFE ha sido actualizado correctamente.',
            ]);

        } catch (\RuntimeException $e) {
            $this->dispatchBrowserEvent('swal:toast-error', [
                'text' => $e->getMessage(),
            ]);
        } catch (\Exception $e) {
            $this->dispatchBrowserEvent('swal:toast-error', [
                'text' => 'Error al guardar la edición: ' . $e->getMessage(),
            ]);
        }
    }

    public function cancelarEdicion(): void
    {
        $this->dispatchBrowserEvent('cerrar-modal-editar-cfe');
        $this->mostrarModalEditar = false;
        $this->cfeEditarId = null;
        $this->editFecha = '';
        $this->editCajaConceptoSeleccionado = null;
        $this->editSiifDependenciaSeleccionado = null;
        $this->editItemDistribuciones = [];
        $this->editCfeItems = [];
        $this->editConceptoRequiereDistribucion = false;
        $this->editDocumentoTipo = '';
        $this->editDocumentoSerie = '';
        $this->editDocumentoNumero = '';
    }
}
