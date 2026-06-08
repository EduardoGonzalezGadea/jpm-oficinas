<?php

namespace App\Services\Tesoreria;

use App\Models\Tesoreria\TesMultasCobradas;
use App\Models\Tesoreria\TesMultasItems;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Servicio de negocio para Multas Cobradas.
 *
 * Extrae la lógica de consulta, cache, CRUD y totales
 * del componente Livewire MultasCobradas.
 */
class MultasCobradasService
{
    public function __construct(
        private readonly MedioPagoService $medioPagoService
    ) {}

    // =========================================================================
    // CONSULTAS DE LISTADO
    // =========================================================================

    /**
     * Devuelve los registros paginados según filtros.
     */
    public function listar(int $anio, int $mes, string $search = '', int $porPagina = 25): LengthAwarePaginator
    {
        $query = TesMultasCobradas::with(['items:id,tes_multas_cobradas_id,detalle,descripcion,importe'])
            ->select('id', 'fecha', 'recibo', 'cedula', 'nombre', 'forma_pago', 'monto')
            ->whereYear('fecha', $anio)
            ->whereMonth('fecha', $mes);

        if (!empty($search)) {
            $term = '%' . $search . '%';
            $query->where(function ($q) use ($term) {
                $q->where('nombre',     'like', $term)
                  ->orWhere('recibo',   'like', $term)
                  ->orWhere('cedula',   'like', $term)
                  ->orWhere('adenda',   'like', $term)
                  ->orWhere('forma_pago', 'like', $term)
                  ->orWhere('referencias', 'like', $term)
                  ->orWhereHas('items', fn ($s) =>
                      $s->where('detalle',      'like', $term)
                        ->orWhere('descripcion', 'like', $term)
                  );
            });
        }

        return $query
            ->orderBy('fecha', 'desc')
            ->orderByRaw('LENGTH(recibo) DESC, recibo DESC')
            ->paginate($porPagina);
    }

    /**
     * Obtiene sugerencias de detalle de ítems del último año para autocompletado.
     */
    public function sugerenciasDetalle(): array
    {
        $anios = [date('Y'), date('Y') - 1];

        return TesMultasItems::whereHas('cobrada', fn ($q) =>
                $q->whereIn(DB::raw('YEAR(fecha)'), $anios)
            )
            ->whereNotNull('detalle')
            ->where('detalle', '!=', '')
            ->select(DB::raw('TRIM(detalle) as detalle_normalizado'))
            ->groupBy(DB::raw('TRIM(detalle)'))
            ->orderBy('detalle_normalizado')
            ->pluck('detalle_normalizado')
            ->toArray();
    }

    // =========================================================================
    // CRUD
    // =========================================================================

    /**
     * Crea una nueva multa cobrada con sus ítems dentro de una transacción.
     *
     * @throws \Exception
     */
    public function crear(array $datos, array $items, int $userId): TesMultasCobradas
    {
        return DB::transaction(function () use ($datos, $items, $userId) {
            $datos['created_by'] = $userId;
            $cobro = TesMultasCobradas::create($datos);
            $this->crearItems($cobro, $items, $userId);
            return $cobro;
        });
    }

    /**
     * Actualiza una multa existente y recrea sus ítems.
     *
     * @throws \Exception
     */
    public function actualizar(TesMultasCobradas $cobro, array $datos, array $items, int $userId): TesMultasCobradas
    {
        return DB::transaction(function () use ($cobro, $datos, $items, $userId) {
            $datos['updated_by'] = $userId;
            $cobro->update($datos);

            $cobro->items()->delete();
            $this->crearItems($cobro, $items, $userId);

            return $cobro->fresh();
        });
    }

    /**
     * Elimina una multa (soft delete con auditoría).
     */
    public function eliminar(TesMultasCobradas $cobro, int $userId): void
    {
        DB::transaction(function () use ($cobro, $userId) {
            $cobro->deleted_by = $userId;
            $cobro->save();
            $cobro->delete();
        });
    }

    // =========================================================================
    // NORMALIZACIÓN Y VALIDACIÓN
    // =========================================================================

    /**
     * Construye el campo 'adicional' (Otros Datos) a partir de tel y periodo.
     */
    public function construirAdicional(?string $tel, ?string $periodoDesde, ?string $periodoHasta): string
    {
        $partes = [];

        if ($tel) {
            $partes[] = 'TEL. ' . trim($tel);
        }

        if ($periodoDesde || $periodoHasta) {
            $desde = $this->formatearFechaUruguaya($periodoDesde ?? '...');
            $hasta = $this->formatearFechaUruguaya($periodoHasta ?? '...');
            $partes[] = 'PERÍODO ' . $desde . ' - ' . $hasta;
        }

        return implode(' ', $partes);
    }

    /**
     * Valida que el monto del header coincida aproximadamente con la suma de ítems.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function validarConsistenciaMontos(float $monto, array $items): void
    {
        $suma = collect($items)->sum(fn ($item) => (float) ($item['importe'] ?: 0));

        if (abs($monto - $suma) > 0.01) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'monto' => 'El monto total ($' . number_format($monto, 2, ',', '.') . ') ' .
                    'no coincide con la suma de los ítems ($' . number_format($suma, 2, ',', '.') . ').',
            ]);
        }
    }

    /**
     * Normaliza la forma de pago usando MedioPagoService.
     *
     * @throws \Exception
     */
    public function normalizarFormaPago(string $formaPago, ?float $monto = null): string
    {
        return $this->medioPagoService->validarYNormalizar($formaPago, $monto);
    }

    // =========================================================================
    // TOTALES POR MEDIO DE PAGO
    // =========================================================================

    /**
     * Calcula los totales por medio de pago en el rango de fechas dado.
     */
    public function calcularTotalesMediosPago(?string $desde, ?string $hasta): Collection
    {
        // Pagos simples (agrupados en DB)
        $puros = TesMultasCobradas::query()
            ->when($desde, fn ($q) => $q->whereDate('fecha', '>=', $desde))
            ->when($hasta, fn ($q) => $q->whereDate('fecha', '<=', $hasta))
            ->where('forma_pago', 'not like', '%/%')
            ->select('forma_pago', DB::raw('SUM(monto) as monto'))
            ->groupBy('forma_pago')
            ->get();

        // Pagos combinados (necesitan procesarse fila a fila)
        $combinados = TesMultasCobradas::query()
            ->when($desde, fn ($q) => $q->whereDate('fecha', '>=', $desde))
            ->when($hasta, fn ($q) => $q->whereDate('fecha', '<=', $hasta))
            ->where('forma_pago', 'like', '%/%')
            ->select('forma_pago', 'monto')
            ->get();

        $registros = $puros->concat($combinados);

        $subtotales           = [];
        $combinaciones        = [];
        $subtotalesCombinados = [];

        foreach ($registros as $item) {
            $formaPago = $item->forma_pago ?: 'SIN DATOS';
            $partes    = $this->medioPagoService->parsearMedioPago($formaPago);

            if (count($partes) === 1) {
                $medio = $this->medioPagoService->obtenerNombreReal($partes[0]['nombre']);
                $subtotales[$medio] = ($subtotales[$medio] ?? 0) + $item->monto;
            } else {
                $mediosConValores = $this->medioPagoService->calcularValoresMedios($formaPago, $item->monto);
                $nombresReal      = array_map(
                    fn ($m) => $this->medioPagoService->obtenerNombreReal($m['nombre']),
                    $mediosConValores
                );
                sort($nombresReal);
                $nombreCombinado = implode(' / ', $nombresReal);

                foreach ($mediosConValores as $mc) {
                    $medio = $this->medioPagoService->obtenerNombreReal($mc['nombre']);
                    $subtotales[$medio] = ($subtotales[$medio] ?? 0) + $mc['valor'];
                    $subtotalesCombinados[$nombreCombinado][$medio] =
                        ($subtotalesCombinados[$nombreCombinado][$medio] ?? 0) + $mc['valor'];
                }

                $combinaciones[$nombreCombinado] =
                    ($combinaciones[$nombreCombinado] ?? 0) +
                    array_sum(array_column($mediosConValores, 'valor'));
            }
        }

        return $this->construirColeccionTotales($subtotales, $combinaciones, $subtotalesCombinados);
    }

    // =========================================================================
    // CACHÉ
    // =========================================================================

    public function cacheKeyRegistros(int $anio, int $mes, string $search, int $page): string
    {
        return sprintf('multas_cobradas.registros.%s.%s.%s.%d', $anio, $mes, md5($search), $page);
    }

    public function cacheKeyTotales(?string $desde, ?string $hasta): string
    {
        return sprintf('multas_cobradas.totales.%s.%s', $desde ?? '', $hasta ?? '');
    }

    public function invalidarCache(): void
    {
        try {
            $driver = config('cache.default');

            if (in_array($driver, ['redis', 'memcached'])) {
                $store = Cache::getStore();
                if ($store instanceof \Illuminate\Cache\RedisStore) {
                    $keys = $store->connection()->keys(Cache::getPrefix() . 'multas_cobradas.*');
                    if (!empty($keys)) {
                        $store->connection()->del($keys);
                    }
                }
            } elseif ($driver === 'file') {
                Cache::flush();
            }
        } catch (\Exception) {
            // Silenciamos errores de caché — no son críticos
        }
    }

    // =========================================================================
    // PRIVADOS
    // =========================================================================

    private function crearItems(TesMultasCobradas $cobro, array $items, int $userId): void
    {
        foreach ($items as $item) {
            $cobro->items()->create([
                'detalle'     => trim($item['detalle']),
                'descripcion' => trim($item['descripcion'] ?? ''),
                'importe'     => (float) $item['importe'],
                'created_by'  => $userId,
            ]);
        }
    }

    private function formatearFechaUruguaya(string $fecha): string
    {
        try {
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
                return \Carbon\Carbon::parse($fecha)->format('d/m/Y');
            }
        } catch (\Exception) {
        }
        return $fecha;
    }

    private function construirColeccionTotales(
        array $subtotales,
        array $combinaciones,
        array $subtotalesCombinados
    ): Collection {
        $resultado = collect();

        foreach ($subtotales as $medio => $total) {
            $resultado->push((object) [
                'forma_pago'            => $medio,
                'total'                 => $total,
                'es_subtotal'           => true,
                'es_combinacion'        => false,
                'es_subtotal_combinado' => false,
            ]);
        }

        foreach ($combinaciones as $combinacion => $total) {
            if (isset($subtotalesCombinados[$combinacion])) {
                foreach ($subtotalesCombinados[$combinacion] as $medio => $subtotal) {
                    $resultado->push((object) [
                        'forma_pago'            => $medio,
                        'total'                 => $subtotal,
                        'es_subtotal'           => false,
                        'es_combinacion'        => false,
                        'es_subtotal_combinado' => true,
                        'combinacion_padre'     => $combinacion,
                    ]);
                }
            }

            $resultado->push((object) [
                'forma_pago'            => $combinacion,
                'total'                 => $total,
                'es_subtotal'           => false,
                'es_combinacion'        => true,
                'es_subtotal_combinado' => false,
            ]);
        }

        return $resultado;
    }
}
