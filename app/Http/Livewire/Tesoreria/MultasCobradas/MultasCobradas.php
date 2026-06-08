<?php

namespace App\Http\Livewire\Tesoreria\MultasCobradas;

use App\Models\Tesoreria\TesMultasCobradas;
use App\Services\Tesoreria\MedioPagoService;
use App\Services\Tesoreria\MultasCobradasService;
use App\Traits\ConvertirMayusculas;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;
use Livewire\WithPagination;

class MultasCobradas extends Component
{
    use WithPagination, ConvertirMayusculas;

    protected $paginationTheme = 'bootstrap';

    // Filtros de listado
    public $search = '';
    public $anio;
    public $mes;

    // Estado de modales
    public $editMode           = false;
    public $showModal          = false;
    public $showDeleteModal    = false;
    public $showDetailModal    = false;
    public $showPrintModal     = false;
    public $deleteId           = null;
    public $edit_id            = '';

    // UX
    public $isLoading = false;
    public $isSaving  = false;

    // Fechas para el modal de impresión
    public $fechaDesde;
    public $fechaHasta;

    // Campos del formulario (header)
    public $registro_id;
    public $recibo;
    public $cedula;
    public $nombre;
    public $domicilio;
    public $adicional;
    public $fecha;
    public $monto;
    public $referencias;
    public $adenda;
    public $forma_pago;

    // Ítems del formulario
    public $items_form = [];

    // Datos auxiliares
    public $selectedRegistro     = null;
    public $registroAEliminar    = null;
    public $resumenFechaDesde;
    public $resumenFechaHasta;
    public $temp_tel;
    public $temp_periodo_desde;
    public $temp_periodo_hasta;
    public $sugerenciasDetalle  = [];
    public $mediosDisponibles   = [];

    protected $queryString = [
        'search'            => ['except' => ''],
        'anio'              => ['except' => ''],
        'mes'               => ['except' => ''],
        'resumenFechaDesde' => ['except' => ''],
        'resumenFechaHasta' => ['except' => ''],
        'edit_id'           => ['except' => ''],
    ];

    protected function rules()
    {
        return (new \App\Http\Requests\Tesoreria\CrearMultaRequest())->rules();
    }

    // =========================================================================
    // PROPIEDAD CALCULADA
    // =========================================================================

    public function getSumaItemsProperty(): float
    {
        return collect($this->items_form)->sum(fn ($item) => (float) ($item['importe'] ?: 0));
    }

    // =========================================================================
    // CICLO DE VIDA
    // =========================================================================

    public function mount()
    {
        $this->anio           = date('Y');
        $this->mes            = date('n');
        $this->fecha          = date('Y-m-d');
        $this->fechaDesde     = date('Y-m-d');
        $this->fechaHasta     = date('Y-m-d');
        $this->resumenFechaDesde = date('Y-m-d');
        $this->resumenFechaHasta = date('Y-m-d');

        $this->cargarAuxiliares();
        $this->resetItemsForm();

        // Abrir formulario de edición si viene por URL o sesión
        if ($this->edit_id) {
            try { $this->edit($this->edit_id); } catch (\Exception) {}
        } elseif (session()->has('edit_multa_id')) {
            try { $this->edit(session('edit_multa_id')); } catch (\Exception) {}
        }
    }

    // =========================================================================
    // RENDER
    // =========================================================================

    public function render(MultasCobradasService $service)
    {
        $this->isLoading = true;

        $useCache = config('cache.default') !== 'null';

        $cacheKeyR = $service->cacheKeyRegistros($this->anio, $this->mes, $this->search, $this->page ?? 1);
        $cacheKeyT = $service->cacheKeyTotales(
            $this->normalizarFecha($this->resumenFechaDesde),
            $this->normalizarFecha($this->resumenFechaHasta)
        );

        if ($useCache) {
            try {
                $registros = Cache::remember($cacheKeyR, now()->addMinutes(5),
                    fn () => $service->listar($this->anio, $this->mes, $this->search));
                $totalesPorMedio = Cache::remember($cacheKeyT, now()->addMinutes(5),
                    fn () => $service->calcularTotalesMediosPago(
                        $this->normalizarFecha($this->resumenFechaDesde),
                        $this->normalizarFecha($this->resumenFechaHasta)
                    ));
            } catch (\Exception) {
                $registros       = $service->listar($this->anio, $this->mes, $this->search);
                $totalesPorMedio = $service->calcularTotalesMediosPago(
                    $this->normalizarFecha($this->resumenFechaDesde),
                    $this->normalizarFecha($this->resumenFechaHasta)
                );
            }
        } else {
            $registros       = $service->listar($this->anio, $this->mes, $this->search);
            $totalesPorMedio = $service->calcularTotalesMediosPago(
                $this->normalizarFecha($this->resumenFechaDesde),
                $this->normalizarFecha($this->resumenFechaHasta)
            );
        }

        $this->isLoading = false;

        return view('livewire.tesoreria.multas-cobradas.multas-cobradas', compact('registros', 'totalesPorMedio'));
    }

    // =========================================================================
    // MODAL DE IMPRESIÓN
    // =========================================================================

    public function openPrintModal()
    {
        $this->fechaDesde  = date('Y-m-d');
        $this->fechaHasta  = date('Y-m-d');
        $this->showPrintModal = true;
    }

    public function generarReporte()
    {
        $this->validate(['fechaDesde' => 'required|date', 'fechaHasta' => 'required|date|after_or_equal:fechaDesde']);
        $this->emit('openInNewTab', route('tesoreria.multas-cobradas.imprimir-detalles', [
            'fechaDesde' => $this->fechaDesde, 'fechaHasta' => $this->fechaHasta,
        ]));
        $this->showPrintModal = false;
    }

    public function generarReporteResumen()
    {
        $this->validate(['fechaDesde' => 'required|date', 'fechaHasta' => 'required|date|after_or_equal:fechaDesde']);
        $this->emit('openInNewTab', route('tesoreria.multas-cobradas.imprimir-resumen', [
            'fechaDesde' => $this->fechaDesde, 'fechaHasta' => $this->fechaHasta,
        ]));
        $this->showPrintModal = false;
    }

    public function generarPdfDetallado()
    {
        $this->validate(['fechaDesde' => 'required|date', 'fechaHasta' => 'required|date|after_or_equal:fechaDesde']);
        $this->emit('openInNewTab', route('tesoreria.multas-cobradas.imprimir-detalles', [
            'fechaDesde' => $this->fechaDesde, 'fechaHasta' => $this->fechaHasta, 'pdf' => 1,
        ]));
        $this->showPrintModal = false;
    }

    public function generarPdfResumen()
    {
        $this->validate(['fechaDesde' => 'required|date', 'fechaHasta' => 'required|date|after_or_equal:fechaDesde']);
        $this->emit('openInNewTab', route('tesoreria.multas-cobradas.imprimir-resumen', [
            'fechaDesde' => $this->fechaDesde, 'fechaHasta' => $this->fechaHasta, 'pdf' => 1,
        ]));
        $this->showPrintModal = false;
    }

    // =========================================================================
    // FORMULARIO — ÍTEMS
    // =========================================================================

    public function resetItemsForm()
    {
        $this->items_form = [
            ['_uid' => uniqid(), 'detalle' => '', 'descripcion' => '', 'importe' => ''],
        ];
    }

    public function addItem()
    {
        $this->items_form[] = ['_uid' => uniqid(), 'detalle' => '', 'descripcion' => '', 'importe' => ''];
        $this->dispatchBrowserEvent('update-total', ['total' => $this->suma_items]);
    }

    public function removeItem($index)
    {
        unset($this->items_form[$index]);
        $this->items_form = array_values($this->items_form);
        if (empty($this->items_form)) {
            $this->addItem();
        }
        $this->dispatchBrowserEvent('update-total', ['total' => $this->suma_items]);
    }

    public function updatedItemsForm()
    {
        $this->monto = round(collect($this->items_form)->sum(fn ($i) => (float) ($i['importe'] ?: 0)), 2);
    }

    // =========================================================================
    // CRUD
    // =========================================================================

    public function create()
    {
        $this->resetForm();
        $this->editMode  = false;
        $this->showModal = true;
    }

    public function edit($id)
    {
        $this->showDetailModal   = false;
        $this->selectedRegistro  = null;

        $registro = TesMultasCobradas::with('items')->findOrFail($id);

        $this->registro_id = $registro->id;
        $this->recibo      = $registro->recibo;
        $this->cedula      = $registro->cedula;
        $this->nombre      = $registro->nombre;
        $this->domicilio   = $registro->domicilio;
        $this->adicional   = $registro->adicional;
        $this->fecha       = $registro->fecha->format('Y-m-d');
        $this->monto       = $registro->monto;
        $this->referencias = $registro->referencias;
        $this->adenda      = $registro->adenda;
        $this->forma_pago  = $registro->forma_pago;

        $this->extraerCamposAdicional($registro->adicional);

        $this->items_form = $registro->items->map(fn ($i) => [
            'id'          => $i->id,
            'detalle'     => $i->detalle,
            'descripcion' => $i->descripcion,
            'importe'     => $i->importe,
            '_uid'        => uniqid(),
        ])->toArray();

        if (empty($this->items_form)) {
            $this->resetItemsForm();
        }

        $this->editMode  = true;
        $this->showModal = true;
    }

    public function save(MultasCobradasService $service, $force = false)
    {
        $this->isSaving = true;

        $this->adicional = $service->construirAdicional(
            $this->temp_tel,
            $this->temp_periodo_desde,
            $this->temp_periodo_hasta
        );

        $this->validate();

        try {
            $service->validarConsistenciaMontos((float) $this->monto, $this->items_form);
            $formaPagoNormalizada = $service->normalizarFormaPago(
                $this->forma_pago ?: 'SIN DATOS',
                $force ? null : (float) $this->monto
            );

            $datos = $this->convertirCamposAMayusculas(
                ['nombre', 'domicilio', 'adicional', 'referencias', 'adenda'],
                [
                    'recibo'     => trim($this->recibo),
                    'cedula'     => preg_replace('/[^0-9KkRUTrut-]/', '', $this->cedula),
                    'nombre'     => $this->nombre,
                    'domicilio'  => $this->domicilio,
                    'adicional'  => $this->adicional,
                    'fecha'      => $this->fecha,
                    'monto'      => $this->monto,
                    'referencias'=> $this->referencias,
                    'adenda'     => $this->adenda,
                    'forma_pago' => $formaPagoNormalizada,
                ]
            );

            if ($this->editMode) {
                $cobro = TesMultasCobradas::findOrFail($this->registro_id);
                $service->actualizar($cobro, $datos, $this->items_form, auth()->id());
                session()->flash('message', 'Multa cobrada actualizada exitosamente.');
            } else {
                $service->crear($datos, $this->items_form, auth()->id());
                session()->flash('message', 'Multa cobrada registrada exitosamente.');
            }

            $this->showModal = false;
            $this->resetForm();
            $this->cargarAuxiliares();
            $service->invalidarCache();
        } catch (\Exception $e) {
            session()->flash('error', 'Error al guardar: ' . $e->getMessage());
        } finally {
            $this->isSaving = false;
        }
    }

    public function confirmDelete($id)
    {
        $this->deleteId           = $id;
        $this->registroAEliminar  = TesMultasCobradas::with('items')->find($id);
        $this->showDeleteModal    = true;
    }

    public function delete(MultasCobradasService $service)
    {
        $registro = TesMultasCobradas::find($this->deleteId);
        if ($registro) {
            $service->eliminar($registro, auth()->id());
            session()->flash('message', 'Registro eliminado correctamente.');
        }
        $this->showDeleteModal   = false;
        $this->registroAEliminar = null;
        $this->cargarAuxiliares();
        $service->invalidarCache();
    }

    public function showDetails($id)
    {
        $this->selectedRegistro  = TesMultasCobradas::with('items', 'creator')->findOrFail($id);
        $this->showDetailModal   = true;
    }

    // =========================================================================
    // AUXILIARES DE VISTA
    // =========================================================================

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function formatearFormaPagoUy(?string $formaPago): string
    {
        $formaPago = trim($formaPago ?? '');
        if ($formaPago === '') {
            return '';
        }
        $service = app(MedioPagoService::class);
        $partes  = $service->parsearMedioPago($formaPago);

        $result = [];
        foreach ($partes as $parte) {
            $nombre = $parte['nombre'] ?? $parte['nombre_original'] ?? '';
            $valor  = $parte['valor'];
            $result[] = $valor !== null
                ? sprintf('%s: %s', $nombre, number_format($valor, 2, ',', '.'))
                : $nombre;
        }
        return implode(' / ', array_filter($result));
    }

    // =========================================================================
    // PRIVADOS
    // =========================================================================

    private function cargarAuxiliares(): void
    {
        $service = app(MultasCobradasService::class);
        $this->sugerenciasDetalle = $service->sugerenciasDetalle();
        $this->mediosDisponibles  = app(MedioPagoService::class)->obtenerMediosDisponibles();
    }

    private function resetForm(): void
    {
        $this->registro_id        = null;
        $this->recibo             = '';
        $this->cedula             = '';
        $this->nombre             = '';
        $this->domicilio          = '';
        $this->adicional          = '';
        $this->fecha              = date('Y-m-d');
        $this->monto              = '';
        $this->referencias        = '';
        $this->adenda             = '';
        $this->forma_pago         = '';
        $this->temp_tel           = '';
        $this->temp_periodo_desde = '';
        $this->temp_periodo_hasta = '';
        $this->resetItemsForm();
        $this->resetErrorBag();
    }

    private function extraerCamposAdicional(?string $adicional): void
    {
        $this->temp_tel           = '';
        $this->temp_periodo_desde = '';
        $this->temp_periodo_hasta = '';

        if (!$adicional) {
            return;
        }

        $adicional = trim($adicional);

        if (preg_match('/TEL\.\s*(.+?)(?=\s+PER[ÍI]ODO\s+|$)/ui', $adicional, $mTel)) {
            $tel = trim($mTel[1]);
            if ($tel && !preg_match('/\d{2}\/\d{2}\/\d{4}/', $tel)) {
                $this->temp_tel = $tel;
            }
        }

        if (preg_match('/PER[ÍI]ODO\s+(\d{2}\/\d{2}\/\d{4})\s*-\s*(\d{2}\/\d{2}\/\d{4})/ui', $adicional, $mPer)) {
            $this->temp_periodo_desde = trim($mPer[1]);
            $this->temp_periodo_hasta = trim($mPer[2]);
        }

        if (empty($this->temp_tel) && empty($this->temp_periodo_desde)) {
            $this->temp_tel = $adicional;
        }
    }

    private function normalizarFecha(?string $fecha): ?string
    {
        if (!$fecha) {
            return null;
        }
        try {
            if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $fecha)) {
                return \Carbon\Carbon::createFromFormat('d/m/Y', $fecha)->format('Y-m-d');
            }
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
                return $fecha;
            }
        } catch (\Exception) {}
        return null;
    }
}
