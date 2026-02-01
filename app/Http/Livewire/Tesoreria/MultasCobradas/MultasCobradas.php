<?php

namespace App\Http\Livewire\Tesoreria\MultasCobradas;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Tesoreria\TesMultasCobradas;
use App\Models\Tesoreria\TesMultasItems;
use App\Models\Tesoreria\Multa;
use App\Traits\ConvertirMayusculas;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class MultasCobradas extends Component
{
    use WithPagination, ConvertirMayusculas;

    protected $paginationTheme = 'bootstrap';

    public $search = '';
    public $anio;
    public $mes;
    public $editMode = false;
    public $showModal = false;
    public $showDeleteModal = false;
    public $showDetailModal = false;
    public $showPrintModal = false;
    public $deleteId = null;

    // Estados de carga para UX
    public $isLoading = false;
    public $isSaving = false;

    // Fechas para el reporte (Print Modal)
    public $fechaDesde;
    public $fechaHasta;

    // Campos del modelo Header
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

    // Items para el formulario
    public $items_form = [];

    public $selectedRegistro = null;

    // Registro a eliminar con detalles
    public $registroAEliminar = null;

    // Fechas para el resumen de medios de pago
    public $resumenFechaDesde;
    public $resumenFechaHasta;

    protected $queryString = [
        'search' => ['except' => ''],
        'anio' => ['except' => ''],
        'mes' => ['except' => ''],
        'resumenFechaDesde' => ['except' => ''],
        'resumenFechaHasta' => ['except' => ''],
    ];

    protected $rules = [
        'recibo' => 'required|string|max:255',
        'fecha' => 'required|date',
        'monto' => 'required|numeric|min:0',
        'cedula' => 'nullable|string|max:255',
        'nombre' => 'nullable|string|max:255',
        'items_form.*.detalle' => 'required|string|max:255',
        'items_form.*.importe' => 'required|numeric|min:0',
    ];

    public function mount()
    {
        $this->anio = date('Y');
        $this->mes = date('n');
        $this->fecha = date('Y-m-d');

        // Inicializar fechas del reporte (fecha actual)
        $this->fechaDesde = date('Y-m-d');
        $this->fechaHasta = date('Y-m-d');

        // Inicializar fechas del resumen (por defecto el día actual)
        $this->resumenFechaDesde = date('Y-m-d');
        $this->resumenFechaHasta = date('Y-m-d');

        $this->resetItemsForm();
    }

    // =========================================================================
    // MÉTODOS DE CACHÉ INTELIGENTE
    // =========================================================================

    /**
     * Genera clave de caché basada en los filtros actuales
     */
    protected function getCacheKeyRegistros(): string
    {
        $page = $this->page ?? 1;
        return 'multas_cobradas.regs.' .
               $this->anio . '.' .
               $this->mes . '.' .
               md5($this->search) . '.' .
               $page;
    }

    /**
     * Genera clave de caché para totales por medio de pago
     */
    protected function getCacheKeyTotales(): string
    {
        return 'multas_cobradas.totales.' .
               $this->resumenFechaDesde . '.' .
               $this->resumenFechaHasta;
    }

    /**
     * Genera prefijo de etiqueta para caché basada en fecha actual
     */
    protected function getCacheTag(): string
    {
        return 'multas_cobradas_' . date('Y-m-d');
    }

    /**
     * Verifica si el caché está disponible y configurado correctamente
     */
    protected function cacheAvailable(): bool
    {
        try {
            // Verificar que el driver no sea 'null' y que soporte etiquetas
            if (config('cache.default') === 'null') {
                return false;
            }
            return Cache::getStore() instanceof \Illuminate\Cache\TaggableStore;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Invalida toda la caché relacionada con multas cobradas
     * Se llama automáticamente después de operaciones CRUD
     */
    protected function invalidateCache(): void
    {
        try {
            if ($this->cacheAvailable()) {
                Cache::tags([$this->getCacheTag()])->flush();
            }
            Cache::flush();
        } catch (\Exception $e) {
            // Silenciar errores de caché - no debe interrumpir la operación principal
        }
    }

    // =========================================================================

    public function render()
    {
        $this->isLoading = true;

        // Determinar si podemos usar caché
        $useCache = $this->cacheAvailable();

        if ($useCache) {
            try {
                // Intentar con caché
                $registros = Cache::tags([$this->getCacheTag()])->remember(
                    $this->getCacheKeyRegistros(),
                    now()->addMinutes(5),
                    function () {
                        return $this->fetchRegistros();
                    }
                );
                $totalesPorMedio = Cache::tags([$this->getCacheTag()])->remember(
                    $this->getCacheKeyTotales(),
                    now()->addMinutes(5),
                    function () {
                        return $this->calcularTotalesMediosPago();
                    }
                );
            } catch (\Exception $e) {
                // Si falla el caché, usar consultas directas
                $registros = $this->fetchRegistros();
                $totalesPorMedio = $this->calcularTotalesMediosPago();
            }
        } else {
            // Sin caché disponible - ejecutar consultas directamente
            $registros = $this->fetchRegistros();
            $totalesPorMedio = $this->calcularTotalesMediosPago();
        }

        $this->isLoading = false;

        return view('livewire.tesoreria.multas-cobradas.multas-cobradas', [
            'registros' => $registros,
            'totalesPorMedio' => $totalesPorMedio
        ]);
    }

    /**
     * Obtiene los registros de la base de datos
     */
    protected function fetchRegistros()
    {
        $query = TesMultasCobradas::with('items')
            ->whereYear('fecha', $this->anio)
            ->whereMonth('fecha', $this->mes);

        // Búsqueda mejorada en múltiples campos
        if (!empty($this->search)) {
            $searchTerm = '%' . $this->search . '%';

            $query->where(function ($q) use ($searchTerm) {
                // Campos del header
                $q->where('nombre', 'like', $searchTerm)
                  ->orWhere('recibo', 'like', $searchTerm)
                  ->orWhere('cedula', 'like', $searchTerm)
                  ->orWhere('adenda', 'like', $searchTerm)
                  ->orWhere('forma_pago', 'like', $searchTerm)
                  ->orWhere('referencias', 'like', $searchTerm)
                  // Búsqueda en items relacionados (detalle y descripción)
                  ->orWhereHas('items', function ($subq) use ($searchTerm) {
                      $subq->where('detalle', 'like', $searchTerm)
                           ->orWhere('descripcion', 'like', $searchTerm);
                  });
            });
        }

        return $query->orderBy('fecha', 'desc')
            ->orderByRaw('LENGTH(recibo) DESC, recibo DESC')
            ->paginate(25);
    }

    /**
     * Calcula totales por medio de pago (lógica extraída del render)
     */
    protected function calcularTotalesMediosPago()
    {
        $registros_pago = TesMultasCobradas::query()
            ->whereDate('fecha', '>=', $this->resumenFechaDesde)
            ->whereDate('fecha', '<=', $this->resumenFechaHasta)
            ->select('forma_pago', 'monto')
            ->get();

        // Procesar medios de pago y crear subtotales
        $subtotales = [];
        $combinaciones = [];
        $subtotales_combinados = [];

        foreach ($registros_pago as $item) {
            $forma_pago = $item->forma_pago ?: 'SIN DATOS';
            $partes = explode('/', $forma_pago);

            // Si solo hay un medio de pago
            if (count($partes) == 1) {
                $medio = mb_strtoupper(trim(explode(':', $partes[0])[0]), 'UTF-8');
                if (!isset($subtotales[$medio])) {
                    $subtotales[$medio] = 0;
                }
                $subtotales[$medio] += $item->monto;
            } else {
                // Si hay múltiples medios de pago combinados
                $medios_con_valores = [];
                $nombre_medios = [];

                foreach ($partes as $parte) {
                    $datos = explode(':', trim($parte));
                    $nombre_medio = mb_strtoupper(trim($datos[0]), 'UTF-8');
                    $nombre_medios[] = $nombre_medio;

                    // Extraer el valor específico si existe
                    if (isset($datos[1])) {
                        $valor_str = trim($datos[1]);
                        $valor_limpio = str_replace('.', '', $valor_str);
                        $valor_limpio = str_replace(',', '.', $valor_limpio);

                        if (is_numeric($valor_limpio)) {
                            $valor = floatval($valor_limpio);
                        } else {
                            $valor = $item->monto / count($partes);
                        }
                    } else {
                        $valor = $item->monto / count($partes);
                    }

                    $medios_con_valores[$nombre_medio] = $valor;
                }

                $nombre_combinado = implode(' / ', $nombre_medios);

                foreach ($medios_con_valores as $medio => $valor) {
                    if (!isset($subtotales[$medio])) {
                        $subtotales[$medio] = 0;
                    }
                    $subtotales[$medio] += $valor;

                    if (!isset($subtotales_combinados[$nombre_combinado])) {
                        $subtotales_combinados[$nombre_combinado] = [];
                    }
                    if (!isset($subtotales_combinados[$nombre_combinado][$medio])) {
                        $subtotales_combinados[$nombre_combinado][$medio] = 0;
                    }
                    $subtotales_combinados[$nombre_combinado][$medio] += $valor;
                }

                if (!isset($combinaciones[$nombre_combinado])) {
                    $combinaciones[$nombre_combinado] = 0;
                }
                $combinaciones[$nombre_combinado] += array_sum($medios_con_valores);
            }
        }

        // Construir el resultado final
        $totalesPorMedio = collect();

        foreach ($subtotales as $medio => $total) {
            $totalesPorMedio->push((object)[
                'forma_pago' => $medio,
                'total' => $total,
                'es_subtotal' => true,
                'es_combinacion' => false,
                'es_subtotal_combinado' => false
            ]);
        }

        foreach ($combinaciones as $combinacion => $total) {
            if (isset($subtotales_combinados[$combinacion])) {
                foreach ($subtotales_combinados[$combinacion] as $medio => $subtotal) {
                    $totalesPorMedio->push((object)[
                        'forma_pago' => $medio,
                        'total' => $subtotal,
                        'es_subtotal' => false,
                        'es_combinacion' => false,
                        'es_subtotal_combinado' => true,
                        'combinacion_padre' => $combinacion
                    ]);
                }
            }

            $totalesPorMedio->push((object)[
                'forma_pago' => $combinacion,
                'total' => $total,
                'es_subtotal' => false,
                'es_combinacion' => true,
                'es_subtotal_combinado' => false
            ]);
        }

        return $totalesPorMedio;
    }

    public function openPrintModal()
    {
        // Establecer fechas por defecto en la fecha actual
        $this->fechaDesde = date('Y-m-d');
        $this->fechaHasta = date('Y-m-d');
        $this->showPrintModal = true;
    }

    public function generarReporte()
    {
        $this->validate([
            'fechaDesde' => 'required|date',
            'fechaHasta' => 'required|date|after_or_equal:fechaDesde',
        ]);

        // Emitir evento para abrir en nueva pestaña
        $url = route('tesoreria.multas-cobradas.imprimir-detalles', [
            'fechaDesde' => $this->fechaDesde,
            'fechaHasta' => $this->fechaHasta
        ]);

        $this->emit('openInNewTab', $url);
        $this->showPrintModal = false;
    }

    public function generarReporteResumen()
    {
        $this->validate([
            'fechaDesde' => 'required|date',
            'fechaHasta' => 'required|date|after_or_equal:fechaDesde',
        ]);

        $url = route('tesoreria.multas-cobradas.imprimir-resumen', [
            'fechaDesde' => $this->fechaDesde,
            'fechaHasta' => $this->fechaHasta
        ]);

        $this->emit('openInNewTab', $url);
        $this->showPrintModal = false;
    }

    public function generarPdfDetallado()
    {
        $this->validate([
            'fechaDesde' => 'required|date',
            'fechaHasta' => 'required|date|after_or_equal:fechaDesde',
        ]);

        $url = route('tesoreria.multas-cobradas.imprimir-detalles', [
            'fechaDesde' => $this->fechaDesde,
            'fechaHasta' => $this->fechaHasta,
            'pdf' => 1
        ]);

        $this->emit('openInNewTab', $url);
        $this->showPrintModal = false;
    }

    public function generarPdfResumen()
    {
        $this->validate([
            'fechaDesde' => 'required|date',
            'fechaHasta' => 'required|date|after_or_equal:fechaDesde',
        ]);

        $url = route('tesoreria.multas-cobradas.imprimir-resumen', [
            'fechaDesde' => $this->fechaDesde,
            'fechaHasta' => $this->fechaHasta,
            'pdf' => 1
        ]);

        $this->emit('openInNewTab', $url);
        $this->showPrintModal = false;
    }

    public function resetItemsForm()
    {
        $this->items_form = [
            ['detalle' => '', 'descripcion' => '', 'importe' => '']
        ];
    }

    public function addItem()
    {
        $this->items_form[] = ['detalle' => '', 'descripcion' => '', 'importe' => ''];
    }

    public function removeItem($index)
    {
        unset($this->items_form[$index]);
        $this->items_form = array_values($this->items_form);
        if (empty($this->items_form)) {
            $this->addItem();
        }
    }

    public function create()
    {
        $this->resetForm();
        $this->editMode = false;
        $this->showModal = true;
    }

    public function edit($id)
    {
        $registro = TesMultasCobradas::with('items')->findOrFail($id);
        $this->registro_id = $registro->id;
        $this->recibo = $registro->recibo;
        $this->cedula = $registro->cedula;
        $this->nombre = $registro->nombre;
        $this->domicilio = $registro->domicilio;
        $this->adicional = $registro->adicional;
        $this->fecha = $registro->fecha->format('Y-m-d');
        $this->monto = $registro->monto;
        $this->referencias = $registro->referencias;
        $this->adenda = $registro->adenda;
        $this->forma_pago = $registro->forma_pago;

        $this->items_form = $registro->items->map(function ($item) {
            return [
                'id' => $item->id,
                'detalle' => $item->detalle,
                'descripcion' => $item->descripcion,
                'importe' => $item->importe,
            ];
        })->toArray();

        if (empty($this->items_form)) {
            $this->resetItemsForm();
        }

        $this->editMode = true;
        $this->showModal = true;
    }

    public function save()
    {
        $this->isSaving = true;
        $this->validate();

        // Validación de consistencia de montos
        $this->validarConsistenciaMontos();

        try {
            DB::beginTransaction();

            $data = $this->convertirCamposAMayusculas(
                ['nombre', 'domicilio', 'adicional', 'referencias', 'adenda'],
                [
                    'recibo' => $this->recibo,
                    'cedula' => $this->cedula,
                    'nombre' => $this->nombre,
                    'domicilio' => $this->domicilio,
                    'adicional' => $this->adicional,
                    'fecha' => $this->fecha,
                    'monto' => $this->monto,
                    'referencias' => $this->referencias,
                    'adenda' => $this->adenda,
                    'forma_pago' => $this->forma_pago ?: 'SIN DATOS',
                ]
            );

            if ($this->editMode) {
                $cobro = TesMultasCobradas::find($this->registro_id);
                $data['updated_by'] = auth()->id();
                $cobro->update($data);

                // Actualizar Items
                $cobro->items()->delete();
                foreach ($this->items_form as $itemData) {
                    $cobro->items()->create([
                        'detalle' => mb_strtoupper($itemData['detalle'], 'UTF-8'),
                        'descripcion' => mb_strtoupper($itemData['descripcion'], 'UTF-8'),
                        'importe' => $itemData['importe'],
                        'created_by' => auth()->id(),
                    ]);
                }

                session()->flash('message', 'Multa cobrada actualizada exitosamente.');
            } else {
                $data['created_by'] = auth()->id();
                $cobro = TesMultasCobradas::create($data);

                foreach ($this->items_form as $itemData) {
                    $cobro->items()->create([
                        'detalle' => mb_strtoupper($itemData['detalle'], 'UTF-8'),
                        'descripcion' => mb_strtoupper($itemData['descripcion'], 'UTF-8'),
                        'importe' => $itemData['importe'],
                        'created_by' => auth()->id(),
                    ]);
                }
                session()->flash('message', 'Multa cobrada registrada exitosamente.');
            }

            DB::commit();
            $this->showModal = false;
            $this->resetForm();
            // Invalidar caché después de operaciones de escritura
            $this->invalidateCache();
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Error al guardar: ' . $e->getMessage());
        } finally {
            $this->isSaving = false;
        }
    }

    /**
     * Valida que el monto total coincida con la suma de los items
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function validarConsistenciaMontos(): void
    {
        $sumaItems = collect($this->items_form)->sum(function ($item) {
            return (float) ($item['importe'] ?: 0);
        });

        if (abs($this->monto - $sumaItems) > 0.01) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'monto' => 'El monto total ($' . number_format($this->monto, 2, ',', '.') . ') ' .
                          'no coincide con la suma de los ítems ($' . number_format($sumaItems, 2, ',', '.') . ').'
            ]);
        }
    }

    public function confirmDelete($id)
    {
        $this->deleteId = $id;
        $this->registroAEliminar = TesMultasCobradas::with('items')->find($id);
        $this->showDeleteModal = true;
    }

    public function delete()
    {
        $registro = TesMultasCobradas::find($this->deleteId);
        if ($registro) {
            $registro->deleted_by = auth()->id();
            $registro->save();
            $registro->delete();
            session()->flash('message', 'Registro eliminado correctamente.');
        }
        $this->showDeleteModal = false;
        $this->registroAEliminar = null;
        // Invalidar caché después de eliminación
        $this->invalidateCache();
    }

    public function showDetails($id)
    {
        $this->selectedRegistro = TesMultasCobradas::with('items', 'creator')->findOrFail($id);
        $this->showDetailModal = true;
    }

    private function resetForm()
    {
        $this->registro_id = null;
        $this->recibo = '';
        $this->cedula = '';
        $this->nombre = '';
        $this->domicilio = '';
        $this->adicional = '';
        $this->fecha = date('Y-m-d');
        $this->monto = '';
        $this->referencias = '';
        $this->adenda = '';
        $this->forma_pago = 'SIN DATOS';
        $this->resetItemsForm();
        $this->resetErrorBag();
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    // Calcular el total de los items para sugerir el monto
    public function updatedItemsForm()
    {
        $total = 0;
        foreach ($this->items_form as $item) {
            $total += (float) ($item['importe'] ?: 0);
        }
        $this->monto = round($total, 2);
    }
}
