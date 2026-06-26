<?php

namespace App\Http\Livewire\Tesoreria\GestionCfe;

use App\DataTransferObjects\CfeData;
use App\Models\Tesoreria\CajaConcepto;
use App\Models\Tesoreria\TesCfeItem;
use App\Models\Tesoreria\SiifDistribucion;

trait WithNuevoCfe
{
    public bool $mostrarModalNuevo = false;
    public string $nuevoDocumentoTipo = '';
    public string $nuevoDocumentoSerie = '';
    public string $nuevoDocumentoNumero = '';
    public string $nuevoFecha = '';
    public string $nuevoReceptorNombre = '';
    public string $nuevoReceptorRuc = '';
    public $nuevoCajaConceptoSeleccionado = null;
    public $nuevoSiifDependenciaSeleccionado = 1;
    public array $nuevoItems = [];
    public array $nuevoMediosPago = [];
    public array $nuevoItemDistribuciones = [];
    public string $nuevoReferencias = '';
    public string $nuevoAdenda = '';
    public bool $nuevoConceptoRequiereDistribucion = false;

    public function nuevoCfe(): void
    {
        $this->reset([
            'nuevoDocumentoTipo', 'nuevoDocumentoSerie', 'nuevoDocumentoNumero',
            'nuevoReceptorNombre', 'nuevoReceptorRuc',
            'nuevoCajaConceptoSeleccionado', 'nuevoMediosPago',
            'nuevoItemDistribuciones', 'nuevoConceptoRequiereDistribucion',
            'nuevoReferencias', 'nuevoAdenda',
        ]);
        $this->nuevoFecha = now()->format('Y-m-d');
        $this->nuevoSiifDependenciaSeleccionado = 1;
        $this->nuevoItems = [
            ['detalle' => '', 'descripcion' => '', 'cantidad' => 1, 'precio' => 0, 'importe' => 0],
        ];
        $this->nuevoItemDistribuciones = [''];
        $this->mostrarModalNuevo = true;
        $this->dispatchBrowserEvent('abrir-modal-nuevo-cfe');
    }

    public function cancelarNuevo(): void
    {
        $this->mostrarModalNuevo = false;
        $this->reset([
            'nuevoDocumentoTipo', 'nuevoDocumentoSerie', 'nuevoDocumentoNumero',
            'nuevoFecha', 'nuevoReceptorNombre', 'nuevoReceptorRuc',
            'nuevoCajaConceptoSeleccionado', 'nuevoSiifDependenciaSeleccionado',
            'nuevoItems', 'nuevoMediosPago', 'nuevoItemDistribuciones',
            'nuevoConceptoRequiereDistribucion', 'nuevoReferencias', 'nuevoAdenda',
        ]);
        $this->nuevoSiifDependenciaSeleccionado = 1;
        $this->dispatchBrowserEvent('cerrar-modal-nuevo-cfe');
    }

    public function recalcularImporteItemNuevo(int $index): void
    {
        if (isset($this->nuevoItems[$index])) {
            $cantidad = (float)($this->nuevoItems[$index]['cantidad'] ?? 1);
            $precio = (float)($this->nuevoItems[$index]['precio'] ?? 0);
            $this->nuevoItems[$index]['importe'] = round($cantidad * $precio, 2);
        }
    }

    public function agregarItemNuevo(): void
    {
        $this->nuevoItems[] = ['detalle' => '', 'descripcion' => '', 'cantidad' => 1, 'precio' => 0, 'importe' => 0];
        $this->nuevoItemDistribuciones[] = '';
        if ($this->nuevoCajaConceptoSeleccionado && $this->nuevoSiifDependenciaSeleccionado) {
            $this->autoAsignarDistribucionesNuevo();
        }
    }

    public function quitarItemNuevo(int $index): void
    {
        if (count($this->nuevoItems) <= 1) {
            return;
        }
        unset($this->nuevoItems[$index], $this->nuevoItemDistribuciones[$index]);
        $this->nuevoItems = array_values($this->nuevoItems);
        $this->nuevoItemDistribuciones = array_values($this->nuevoItemDistribuciones);
    }

    public function agregarMedioPagoNuevo(): void
    {
        $this->nuevoMediosPago[] = ['tipo' => '', 'valor' => 0];
    }

    public function quitarMedioPagoNuevo(int $index): void
    {
        unset($this->nuevoMediosPago[$index]);
        $this->nuevoMediosPago = array_values($this->nuevoMediosPago);
    }

    public function updatedNuevoCajaConceptoSeleccionado($value): void
    {
        $concepto = CajaConcepto::find($value);
        $this->nuevoConceptoRequiereDistribucion = $concepto ? $concepto->requiere_distribucion : false;

        $this->nuevoItemDistribuciones = [];
        foreach ($this->nuevoItems as $idx => $item) {
            $this->nuevoItemDistribuciones[$idx] = '';
        }

        if (!empty($value) && !empty($this->nuevoSiifDependenciaSeleccionado)) {
            $this->autoAsignarDistribucionesNuevo();
        }
    }

    public function updatedNuevoSiifDependenciaSeleccionado($value): void
    {
        $this->nuevoItemDistribuciones = [];
        foreach ($this->nuevoItems as $idx => $item) {
            $this->nuevoItemDistribuciones[$idx] = '';
        }

        if (!empty($value) && !empty($this->nuevoCajaConceptoSeleccionado)) {
            $this->autoAsignarDistribucionesNuevo();
        }
    }

    private function autoAsignarDistribucionesNuevo(): void
    {
        if (empty($this->nuevoItems)) {
            return;
        }

        $concepto = CajaConcepto::find($this->nuevoCajaConceptoSeleccionado);
        if (!$concepto || !$concepto->siif_distribucion_tipo_id) {
            return;
        }

        foreach ($this->nuevoItems as $index => $item) {
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
                ->where('dependencia_id', $this->nuevoSiifDependenciaSeleccionado)
                ->whereNull('deleted_at')
                ->exists();

            if ($existe) {
                $this->nuevoItemDistribuciones[$index] = (string) $distribucionId;
            }
        }
    }

    public function guardarNuevo(): void
    {
        $rules = [
            'nuevoDocumentoTipo' => 'required|string|max:50',
            'nuevoDocumentoSerie' => 'nullable|string|max:10',
            'nuevoDocumentoNumero' => 'required|string|max:20',
            'nuevoFecha' => 'required|date',
            'nuevoReceptorNombre' => 'required|string|max:255',
            'nuevoCajaConceptoSeleccionado' => 'required|integer|min:1|exists:tes_caja_conceptos,id',
            'nuevoSiifDependenciaSeleccionado' => 'nullable|integer|exists:siif_distribucion_dependencias,id',
            'nuevoItems' => 'required|array|min:1',
            'nuevoItems.*.detalle' => 'required|string|max:500',
            'nuevoItems.*.importe' => 'required|numeric|min:0',
        ];

        $concepto = CajaConcepto::find($this->nuevoCajaConceptoSeleccionado);
        $requiereDistribucion = $concepto ? $concepto->requiere_distribucion : false;

        if ($requiereDistribucion) {
            $hasMissing = false;
            foreach ($this->nuevoItems as $index => $item) {
                if (empty($this->nuevoItemDistribuciones[$index])) {
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

            $rules['nuevoItemDistribuciones.*'] = 'required|integer|exists:siif_distribucions,id';
        } else {
            $rules['nuevoItemDistribuciones.*'] = 'nullable|integer|exists:siif_distribucions,id';
        }

        $this->validate($rules, [
            'nuevoDocumentoTipo.required' => 'El tipo de documento es obligatorio.',
            'nuevoDocumentoNumero.required' => 'El número de documento es obligatorio.',
            'nuevoFecha.required' => 'La fecha es obligatoria.',
            'nuevoReceptorNombre.required' => 'El nombre del receptor es obligatorio.',
            'nuevoCajaConceptoSeleccionado.required' => 'Debe seleccionar un concepto de caja.',
            'nuevoItems.required' => 'Debe agregar al menos un ítem.',
            'nuevoItems.*.detalle.required' => 'El detalle del ítem es obligatorio.',
            'nuevoItems.*.importe.required' => 'El importe del ítem es obligatorio.',
            'nuevoItems.*.importe.numeric' => 'El importe debe ser un número.',
            'nuevoItemDistribuciones.*.required' => 'Debe seleccionar una distribución para todos los ítems.',
            'nuevoItemDistribuciones.*.exists' => 'La distribución SIIF seleccionada no existe.',
        ]);

        try {
            $data = new CfeData(
                documento_tipo: $this->nuevoDocumentoTipo,
                documento_serie: $this->nuevoDocumentoSerie ?: null,
                documento_numero: $this->nuevoDocumentoNumero,
                fecha: $this->nuevoFecha,
                receptor_nombre_denominacion: $this->nuevoReceptorNombre,
                receptor_documento_ruc: $this->nuevoReceptorRuc ?: null,
                tes_caja_concepto_id: $this->nuevoCajaConceptoSeleccionado,
                siif_distribucion_dependencia_id: $this->nuevoSiifDependenciaSeleccionado,
                items: $this->nuevoItems,
                medios_pago: $this->nuevoMediosPago,
                item_distribuciones: $this->nuevoItemDistribuciones,
                referencias: $this->nuevoReferencias ?: null,
                adenda: $this->nuevoAdenda ?: null,
                moneda: 'UYU',
            );

            $this->cfeCreator->createManual($data);

            $tipoDoc = $this->nuevoDocumentoTipo;
            $serieDoc = $this->nuevoDocumentoSerie;
            $numDoc = $this->nuevoDocumentoNumero;

            $this->cancelarNuevo();

            $this->dispatchBrowserEvent('swal:modal', [
                'type' => 'success',
                'title' => 'CFE Creado',
                'text' => "El CFE {$tipoDoc} {$serieDoc}-{$numDoc} ha sido creado correctamente.",
            ]);

        } catch (\InvalidArgumentException $e) {
            $this->dispatchBrowserEvent('swal:toast-error', [
                'text' => $e->getMessage(),
            ]);
        } catch (\Exception $e) {
            $this->dispatchBrowserEvent('swal:modal', [
                'type' => 'error',
                'title' => 'Error al guardar',
                'text' => 'Hubo un problema guardando el CFE: ' . $e->getMessage(),
            ]);
        }
    }
}
