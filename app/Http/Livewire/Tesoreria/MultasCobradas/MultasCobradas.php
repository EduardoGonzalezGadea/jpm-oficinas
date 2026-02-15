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

    // Campos temporales para "Otros Datos"
    public $temp_tel;
    public $temp_periodo_desde;
    public $temp_periodo_hasta;

    public $sugerenciasDetalle = [];
    public $mediosDisponibles = [];

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
        'cedula' => 'nullable|string|max:20',
        'nombre' => 'nullable|string|max:255',
        'items_form.*.detalle' => 'required|string|max:255',
        'items_form.*.importe' => 'required|numeric|min:0',
    ];

    /**
     * Propiedades calculadas para la vista
     */
    public function getSumaItemsProperty(): float
    {
        return collect($this->items_form)->sum(function ($item) {
            return (float) ($item['importe'] ?: 0);
        });
    }

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

        $this->cargarSugerenciasDetalle();
        $this->cargarMediosPago();
        $this->resetItemsForm();
    }

    public function cargarMediosPago()
    {
        $medioPagoService = new \App\Services\Tesoreria\MedioPagoService();
        $this->mediosDisponibles = $medioPagoService->obtenerMediosDisponibles();
    }

    public function cargarSugerenciasDetalle()
    {
        $anios = [date('Y'), date('Y') - 1];

        $this->sugerenciasDetalle = TesMultasItems::whereHas('cobrada', function ($query) use ($anios) {
            $query->whereIn(DB::raw('YEAR(fecha)'), $anios);
        })
            ->whereNotNull('detalle')
            ->where('detalle', '!=', '')
            ->select(DB::raw('TRIM(detalle) as detalle_normalizado'))
            ->groupBy(DB::raw('TRIM(detalle)'))
            ->orderBy('detalle_normalizado')
            ->pluck('detalle_normalizado')
            ->toArray();
    }

    // =========================================================================
    // MÉTODOS DE CACHÉ INTELIGENTE
    // =========================================================================

    /**
     * Genera clave de caché basada en los filtros actuales para registros
     */
    protected function getCacheKeyRegistros(): string
    {
        $page = $this->page ?? 1;
        return sprintf(
            'multas_cobradas.registros.%s.%s.%s.%d',
            $this->anio,
            $this->mes,
            md5($this->search),
            $page
        );
    }

    /**
     * Genera clave de caché para totales por medio de pago
     */
    protected function getCacheKeyTotales(): string
    {
        return sprintf(
            'multas_cobradas.totales.%s.%s',
            $this->resumenFechaDesde,
            $this->resumenFechaHasta
        );
    }

    /**
     * Genera clave de caché para reportes avanzados
     */
    protected function getCacheKeyReporteAvanzado(array $filters): string
    {
        return 'multas_cobradas.reporte.' . md5(serialize($filters));
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
            // Verificar que el driver no sea 'null'
            if (config('cache.default') === 'null') {
                return false;
            }
            return true;
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
            // Invalidar claves de caché específicas para multas cobradas
            $this->invalidateSpecificCacheKeys();
        } catch (\Exception $e) {
            // Silenciar errores de caché - no debe interrumpir la operación principal
        }
    }

    /**
     * Invalida claves de caché específicas para multas cobradas
     */
    protected function invalidateSpecificCacheKeys(): void
    {
        try {
            $cacheDriver = config('cache.default');

            // Para drivers que soportan patrones (Redis, Memcached)
            if (in_array($cacheDriver, ['redis', 'memcached'])) {
                $this->invalidateCacheByPattern(Cache::getPrefix() . 'multas_cobradas.*');
            } elseif ($cacheDriver === 'file') {
                // Para driver file, no podemos invalidar por patrón fácilmente
                // Simplemente limpiamos el caché de la aplicación
                Cache::flush();
            }
        } catch (\Exception $e) {
            // Silenciar errores - no es crítico
        }
    }

    /**
     * Invalida claves de caché por patrón (solo para drivers que lo soportan)
     */
    protected function invalidateCacheByPattern(string $pattern): void
    {
        try {
            $store = Cache::getStore();

            if ($store instanceof \Illuminate\Cache\RedisStore) {
                $redis = $store->connection();
                $keys = $redis->keys($pattern);

                if (!empty($keys)) {
                    $redis->del($keys);
                }
            } elseif ($store instanceof \Illuminate\Cache\MemcachedStore) {
                // Memcached no soporta búsqueda por patrones directamente
                // Se debe implementar una estrategia diferente (ej: almacenar claves en una lista)
            }
        } catch (\Exception $e) {
            // Silenciar errores - no es crítico
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
                // Intentar con caché (sin tags)
                $registros = Cache::remember(
                    $this->getCacheKeyRegistros(),
                    now()->addMinutes(5),
                    function () {
                        return $this->fetchRegistros();
                    }
                );
                $totalesPorMedio = Cache::remember(
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
     * Calcula totales por medio de pago usando el servicio de medios de pago
     */
    protected function calcularTotalesMediosPago()
    {
        $registros_pago = TesMultasCobradas::query()
            ->whereDate('fecha', '>=', $this->resumenFechaDesde)
            ->whereDate('fecha', '<=', $this->resumenFechaHasta)
            ->select('forma_pago', 'monto')
            ->get();

        // Crear instancia del servicio
        $medioPagoService = new \App\Services\Tesoreria\MedioPagoService();

        // Procesar medios de pago y crear subtotales
        $subtotales = [];
        $combinaciones = [];
        $subtotales_combinados = [];

        foreach ($registros_pago as $item) {
            $forma_pago = $item->forma_pago ?: 'SIN DATOS';
            $partes = $medioPagoService->parsearMedioPago($forma_pago);

            // Si solo hay un medio de pago
            if (count($partes) == 1) {
                $nombreOriginal = $partes[0]['nombre'];
                $medio = $medioPagoService->obtenerNombreReal($nombreOriginal);
                if (!isset($subtotales[$medio])) {
                    $subtotales[$medio] = 0;
                }
                $subtotales[$medio] += $item->monto;
            } else {
                // Si hay múltiples medios de pago combinados
                $medios_con_valores = $medioPagoService->calcularValoresMedios($forma_pago, $item->monto);

                // Normalizar nombres con acentos y ordenar
                $nombresReal = array_map(fn($m) => $medioPagoService->obtenerNombreReal($m['nombre']), $medios_con_valores);
                sort($nombresReal);
                $nombre_combinado = implode(' / ', $nombresReal);

                foreach ($medios_con_valores as $medio_con_valor) {
                    $medio = $medioPagoService->obtenerNombreReal($medio_con_valor['nombre']);
                    $valor = $medio_con_valor['valor'];

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
                $combinaciones[$nombre_combinado] += array_sum(array_column($medios_con_valores, 'valor'));
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
            ['_uid' => uniqid(), 'detalle' => '', 'descripcion' => '', 'importe' => '']
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

    public function create()
    {
        $this->resetForm();
        $this->editMode = false;
        $this->showModal = true;
    }

    public function edit($id)
    {
        // Cerrar el modal de detalle si está abierto
        $this->showDetailModal = false;
        $this->selectedRegistro = null;

        $registro = TesMultasCobradas::findOrFail($id);
        $registro->load('items');
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

        // Intentar separar TEL y PERÍODO si existen en adicional
        if ($registro->adicional) {
            $adicional = trim($registro->adicional);

            // Buscar TEL. (opcional) - captura todo hasta PERÍODO o fin del string
            // El teléfono puede tener espacios, pero se detiene antes de "PERÍODO"
            if (preg_match('/TEL\.\s*(.+?)(?=\s+PER[ÍI]ODO\s+|$)/ui', $adicional, $matchesTel)) {
                $telExtraido = trim($matchesTel[1]);
                // Verificar que no sea vacío y no contenga fechas
                if (!empty($telExtraido) && !preg_match('/\d{2}\/\d{2}\/\d{4}/', $telExtraido)) {
                    $this->temp_tel = $telExtraido;
                }
            }

            // Buscar PERÍODO (opcional) - formato: PERÍODO DD/MM/YYYY - DD/MM/YYYY
            if (preg_match('/PER[ÍI]ODO\s+(\d{2}\/\d{2}\/\d{4})\s*-\s*(\d{2}\/\d{2}\/\d{4})/ui', $adicional, $matchesPeriodo)) {
                $this->temp_periodo_desde = trim($matchesPeriodo[1]);
                $this->temp_periodo_hasta = trim($matchesPeriodo[2]);
            }

            // Si no se encontró ningún patrón reconocido, todo va a teléfono
            if (empty($this->temp_tel) && empty($this->temp_periodo_desde)) {
                $this->temp_tel = $adicional;
            }
        }

        $this->items_form = $registro->items->map(function ($item) {
            return [
                'id' => $item->id,
                'detalle' => $item->detalle,
                'descripcion' => $item->descripcion,
                'importe' => $item->importe,
                '_uid' => uniqid(),
            ];
        })->toArray();

        // Si no hay item, crear uno por defecto (esto asegura que siempre haya al menos uno al abrir)
        if (empty($this->items_form)) {
            $this->resetItemsForm();
        }

        $this->editMode = true;
        $this->showModal = true;
    }

    public function save($force = false)
    {
        $this->isSaving = true;

        // Construir campo adicional (Otros Datos)
        $partesAdicional = [];
        if ($this->temp_tel) {
            $partesAdicional[] = "TEL. " . trim($this->temp_tel);
        }
        if ($this->temp_periodo_desde || $this->temp_periodo_hasta) {
            $desde = $this->temp_periodo_desde ?: '...';
            $hasta = $this->temp_periodo_hasta ?: '...';
            // Asegurar formato uruguayo si vienen como Y-m-d desde el componente (aunque usen datepicker-uy)
            try {
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $desde)) $desde = \Carbon\Carbon::parse($desde)->format('d/m/Y');
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $hasta)) $hasta = \Carbon\Carbon::parse($hasta)->format('d/m/Y');
            } catch (\Exception $e) {
            }

            $partesAdicional[] = "PERÍODO " . $desde . " - " . $hasta;
        }
        $this->adicional = implode(' ', $partesAdicional);

        $this->validate();

        // Validación de consistencia de montos
        $this->validarConsistenciaMontos();

        // Validación de medio de pago
        $this->validarMedioPago($force);

        try {
            DB::beginTransaction();

            $medioPagoService = new \App\Services\Tesoreria\MedioPagoService();
            $medioPagoStr = $this->forma_pago ?: 'SIN DATOS';
            $formaPagoNormalizada = $medioPagoService->validarYNormalizar(
                $medioPagoStr,
                $force ? null : $this->monto
            );

            $data = $this->convertirCamposAMayusculas(
                ['nombre', 'domicilio', 'adicional', 'referencias', 'adenda'],
                [
                    'recibo' => trim($this->recibo),
                    'cedula' => preg_replace('/[^0-9KkRUTrut-]/', '', $this->cedula),
                    'nombre' => $this->nombre,
                    'domicilio' => $this->domicilio,
                    'adicional' => $this->adicional,
                    'fecha' => $this->fecha,
                    'monto' => $this->monto,
                    'referencias' => $this->referencias,
                    'adenda' => $this->adenda,
                    'forma_pago' => $formaPagoNormalizada,
                ]
            );

            if ($this->editMode) {
                $cobro = TesMultasCobradas::findOrFail($this->registro_id);
                $data['updated_by'] = auth()->id();
                $cobro->update($data);

                // Actualizar Items - Borrar y crear (simplificado)
                $cobro->items()->delete();
                foreach ($this->items_form as $itemData) {
                    $cobro->items()->create([
                        'detalle' => trim($itemData['detalle']),
                        'descripcion' => trim($itemData['descripcion']),
                        'importe' => (float) $itemData['importe'],
                        'created_by' => auth()->id(),
                    ]);
                }

                session()->flash('message', 'Multa cobrada actualizada exitosamente.');
            } else {
                $data['created_by'] = auth()->id();
                $cobro = TesMultasCobradas::create($data);

                foreach ($this->items_form as $itemData) {
                    $cobro->items()->create([
                        'detalle' => trim($itemData['detalle']),
                        'descripcion' => trim($itemData['descripcion']),
                        'importe' => (float) $itemData['importe'],
                        'created_by' => auth()->id(),
                    ]);
                }
                session()->flash('message', 'Multa cobrada registrada exitosamente.');
            }

            DB::commit();
            $this->showModal = false;
            $this->resetForm();
            $this->cargarSugerenciasDetalle();
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

    /**
     * Valida el medio de pago usando el servicio de medios de pago
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function validarMedioPago($ignoreConsistency = false): void
    {
        $medioPagoService = new \App\Services\Tesoreria\MedioPagoService();
        $medioPagoService->validarYNormalizar(
            $this->forma_pago ?: 'SIN DATOS',
            $ignoreConsistency ? null : $this->monto
        );
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
        $this->cargarSugerenciasDetalle();
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
        $this->forma_pago = '';
        $this->temp_tel = '';
        $this->temp_periodo_desde = '';
        $this->temp_periodo_hasta = '';
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
