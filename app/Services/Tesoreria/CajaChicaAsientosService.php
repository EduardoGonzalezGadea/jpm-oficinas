<?php

namespace App\Services\Tesoreria;

use App\Models\Tesoreria\CajaChica;
use App\Models\Tesoreria\Pendiente;
use App\Models\Tesoreria\Pago;
use App\Models\Tesoreria\Movimiento;
use App\Models\Tesoreria\LbConcepto;
use App\Models\Tesoreria\LbDetalle;
use App\Models\Tesoreria\LbTipo;
use App\Models\Tesoreria\LibroDiario;
use App\Models\Tesoreria\MedioDePago;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CajaChicaAsientosService
{
    private const DENOMINACION_TESORERIA = 'TESORERÍA DE LA JPM';
    private const ORIGEN_CAJA_CHICA = 'caja_chica';
    private const ORIGEN_PENDIENTE = 'pendiente';
    private const ORIGEN_MOVIMIENTO = 'movimiento';
    private const ORIGEN_PAGO = 'pago';

    private ?int $conceptoId = null;
    private ?int $detalleFondoFijoId = null;
    private ?int $detallePendienteId = null;
    private ?int $detallePagosId = null;
    private ?int $medioEfectivoId = null;
    private ?int $tipoEntradaId = null;
    private ?int $tipoSalidaId = null;

    public function __construct(
        private readonly LibroDiarioService $libroDiarioService,
    ) {}

    public function registrarAjusteFondoFijo(CajaChica $cajaChica, float $montoAnterior, float $montoNuevo, string $fecha): ?LibroDiario
    {
        $diferencia = $montoNuevo - $montoAnterior;

        if (abs($diferencia) < 0.01) {
            return null;
        }

        $tipoId = $diferencia > 0 ? $this->getTipoEntradaId() : $this->getTipoSalidaId();
        $signo = $diferencia > 0 ? 1 : -1;

        return DB::transaction(function () use ($cajaChica, $montoAnterior, $montoNuevo, $fecha, $tipoId, $signo, $diferencia) {
            $asiento = $this->libroDiarioService->registrarAsiento([
                'fecha' => $fecha,
                'tipo_id' => $tipoId,
                'signo_efectivo' => $signo,
                'identidad' => null,
                'denominacion' => self::DENOMINACION_TESORERIA,
                'descripcion' => "Ajuste de Fondo Fijo Caja Chica - {$cajaChica->mes} {$cajaChica->anio}",
                'concepto_id' => $this->getConceptoId(),
                'detalle_id' => $this->getDetalleFondoFijoId(),
                'medio_id' => $this->getMedioEfectivoId(),
                'monto' => abs($diferencia),
            ]);

            if ($asiento) {
                $this->asignarOrigen($asiento, self::ORIGEN_CAJA_CHICA, $cajaChica->idCajaChica);
            }

            Log::info('CajaChicaAsientosService: asiento de ajuste de fondo fijo registrado', [
                'caja_chica_id' => $cajaChica->idCajaChica,
                'mes' => $cajaChica->mes,
                'anio' => $cajaChica->anio,
                'monto_anterior' => $montoAnterior,
                'monto_nuevo' => $montoNuevo,
                'diferencia' => $diferencia,
                'asiento_id' => $asiento?->id,
            ]);

            return $asiento;
        });
    }

    /**
     * Ajusta el fondo fijo de la caja chica comparando el monto del fondo
     * objetivo contra el saldo neto anual del concepto Caja Chica en el
     * Libro Diario (suma de entradas − suma de salidas del año en curso).
     *
     * Reglas:
     *  a) saldoLibroDiario > nuevoFondo  -> salida por la diferencia (dinero sobrante en el libro)
     *  b) saldoLibroDiario == nuevoFondo -> no se crea ningún asiento
     *  c) saldoLibroDiario < nuevoFondo  -> entrada por la diferencia (faltante para alcanzar el fondo)
     *
     * Antes de crear el nuevo asiento, elimina cualquier ajuste de fondo fijo
     * previo asociado a esta caja chica para evitar duplicados.
     *
     * @param CajaChica $cajaChica      Caja chica cuyo fondo se ajusta.
     * @param float     $nuevoFondo     Monto objetivo del fondo fijo.
     * @param string    $fecha          Fecha del asiento a registrar.
     */
    public function registrarAjusteFondoFijoPorSaldoLibroDiario(
        CajaChica $cajaChica,
        float $nuevoFondo,
        string $fecha
    ): ?LibroDiario {
        $nuevoFondo = round($nuevoFondo, 2);
        $anio = (int) $cajaChica->anio;

        // Saldo neto del concepto Caja Chica en el año (entradas - salidas)
        $saldoLibroDiario = round($this->calcularSaldoAnualConceptoCajaChica($anio), 2);

        // Rendiciones no recuperadas: salidas del detalle Fondo Fijo que no son
        // redistribuciones, menos entradas del detalle Fondo Fijo que no son
        // redistribuciones ni ajustes del fondo (la constitución/ajuste se
        // identifica por tener origen caja_chica). Representa dinero rendido
        // que aún está pendiente de recuperar y que por lo tanto sigue siendo
        // parte del fondo fijo.
        $rendicionesNoRecuperadas = round($this->calcularRendicionesNoRecuperadas($anio), 2);

        // Total ocupado = saldo del libro + rendiciones pendientes de recuperar
        $totalOcupado = round($saldoLibroDiario + $rendicionesNoRecuperadas, 2);
        $diferencia = round($nuevoFondo - $totalOcupado, 2);

        // Caso b): total ocupado coincide con el fondo -> no se hace nada
        if (abs($diferencia) < 0.01) {
            Log::info('CajaChicaAsientosService: ajuste de fondo fijo sin cambios (saldo del libro + rendiciones coincide con el fondo)', [
                'caja_chica_id' => $cajaChica->idCajaChica,
                'mes' => $cajaChica->mes,
                'anio' => $cajaChica->anio,
                'fondo_nuevo' => $nuevoFondo,
                'saldo_libro_diario' => $saldoLibroDiario,
                'rendiciones_no_recuperadas' => $rendicionesNoRecuperadas,
                'total_ocupado' => $totalOcupado,
            ]);
            return null;
        }

        // Caso a): totalOcupado > nuevoFondo -> salida (signo_efectivo -1, tipo Salida)
        // Caso c): totalOcupado < nuevoFondo -> entrada (signo_efectivo +1, tipo Entrada)
        $esEntrada = $diferencia > 0;
        $tipoId = $esEntrada ? $this->getTipoEntradaId() : $this->getTipoSalidaId();
        $signo = $esEntrada ? 1 : -1;
        $montoAsiento = abs($diferencia);

        return DB::transaction(function () use ($cajaChica, $nuevoFondo, $saldoLibroDiario, $rendicionesNoRecuperadas, $totalOcupado, $diferencia, $fecha, $tipoId, $signo, $montoAsiento, $esEntrada) {
            // Cada ajuste del fondo fijo se registra como un asiento
            // independiente, conservando el historial completo de entradas
            // y salidas. No se eliminan los ajustes previos.
            $descripcion = $esEntrada
                ? "Entrada por Ajuste de Fondo Fijo Caja Chica - {$cajaChica->mes} {$cajaChica->anio}"
                : "Salida por Ajuste de Fondo Fijo Caja Chica - {$cajaChica->mes} {$cajaChica->anio}";

            $asiento = $this->libroDiarioService->registrarAsiento([
                'fecha' => $fecha,
                'tipo_id' => $tipoId,
                'signo_efectivo' => $signo,
                'identidad' => null,
                'denominacion' => self::DENOMINACION_TESORERIA,
                'descripcion' => $descripcion,
                'concepto_id' => $this->getConceptoId(),
                'detalle_id' => $this->getDetalleFondoFijoId(),
                'medio_id' => $this->getMedioEfectivoId(),
                'monto' => $montoAsiento,
            ]);

            if ($asiento) {
                $this->asignarOrigen($asiento, self::ORIGEN_CAJA_CHICA, $cajaChica->idCajaChica);
            }

            Log::info('CajaChicaAsientosService: asiento de ajuste de fondo fijo registrado según saldo del libro diario', [
                'caja_chica_id' => $cajaChica->idCajaChica,
                'mes' => $cajaChica->mes,
                'anio' => $cajaChica->anio,
                'fondo_nuevo' => $nuevoFondo,
                'saldo_libro_diario' => $saldoLibroDiario,
                'rendiciones_no_recuperadas' => $rendicionesNoRecuperadas,
                'total_ocupado' => $totalOcupado,
                'diferencia' => $diferencia,
                'tipo_asiento' => $esEntrada ? 'Entrada' : 'Salida',
                'monto_asiento' => $montoAsiento,
                'asiento_id' => $asiento?->id,
            ]);

            return $asiento;
        });
    }

    /**
     * Calcula el saldo neto anual del concepto Caja Chica en el Libro
     * Diario: suma de montos de entradas (signo_efectivo = +1) menos suma de
     * montos de salidas (signo_efectivo = -1) para todos los asientos del año
     * indicado que pertenezcan al concepto Caja Chica y no estén soft-deleted.
     */
    public function calcularSaldoAnualConceptoCajaChica(int $anio): float
    {
        $conceptoId = $this->getConceptoId();

        $resultado = LibroDiario::where('concepto_id', $conceptoId)
            ->whereYear('fecha', $anio)
            ->whereNull('deleted_at')
            ->selectRaw('COALESCE(SUM(monto * signo_efectivo), 0) as saldo_neto')
            ->value('saldo_neto');

        return (float) $resultado;
    }

    /**
     * Calcula el monto total de rendiciones no recuperadas del año para el
     * concepto Caja Chica. Se define como:
     *
     *   SUM(salidas del detalle Fondo Fijo que no son redistribuciones)
     * - SUM(entradas del detalle Fondo Fijo que no son redistribuciones
     *          ni ajustes del propio fondo, identificados por origen caja_chica)
     *
     * Las redistribuciones se excluyen porque están compensadas (una salida y
     * una entrada por el mismo monto, netas = 0). Las entradas que son
     * ajustes del fondo (constitución, reajustes) se excluyen porque
     * representan la creación/aumento del propio fondo, no recuperaciones de
     * rendiciones. El resultado representa dinero rendido que está pendiente
     * de recuperar y que sigue siendo parte del fondo fijo.
     */
    public function calcularRendicionesNoRecuperadas(int $anio): float
    {
        $conceptoId = $this->getConceptoId();
        $detalleFondoFijoId = $this->getDetalleFondoFijoId();

        // Salidas del detalle Fondo Fijo que NO son redistribuciones
        // (no tienen grupo_redistribucion_id ni asociar apuntando a una entrada).
        $salidasRendiciones = (float) LibroDiario::where('concepto_id', $conceptoId)
            ->where('detalle_id', $detalleFondoFijoId)
            ->where('signo_efectivo', -1)
            ->whereYear('fecha', $anio)
            ->whereNull('deleted_at')
            ->whereNull('grupo_redistribucion_id')
            ->whereNull('asociar')
            ->sum('monto');

        // Entradas del detalle Fondo Fijo que NO son redistribuciones ni
        // ajustes del propio fondo. Los ajustes del fondo (constitución,
        // reajustes) se identifican por tener cch_origen_type = 'caja_chica'.
        // El resto (origen pago/movimiento) son recuperaciones de rendiciones
        // y deben restarse de las salidas por rendición.
        $entradasRecuperaciones = (float) LibroDiario::where('concepto_id', $conceptoId)
            ->where('detalle_id', $detalleFondoFijoId)
            ->where('signo_efectivo', 1)
            ->whereYear('fecha', $anio)
            ->whereNull('deleted_at')
            ->whereNull('grupo_redistribucion_id')
            ->whereNull('asociar')
            ->where(function ($q) {
                $q->whereNull('cch_origen_type')
                  ->orWhere('cch_origen_type', '!=', self::ORIGEN_CAJA_CHICA);
            })
            ->sum('monto');

        return round($salidasRendiciones - $entradasRecuperaciones, 2);
    }

    /**
     * Elimina los asientos de ajuste de fondo fijo previos asociados a una
     * caja chica (asientos con origen caja_chica y detalle fondo_fijo), para
     * evitar duplicados al reajustar el fondo.
     */
    public function eliminarAjustesFondoFijoPrevios(int $idCajaChica): void
    {
        $detalleFondoFijoId = $this->getDetalleFondoFijoId();
        $conceptoId = $this->getConceptoId();

        $asientos = LibroDiario::where('cch_origen_type', self::ORIGEN_CAJA_CHICA)
            ->where('cch_origen_id', $idCajaChica)
            ->where('concepto_id', $conceptoId)
            ->where('detalle_id', $detalleFondoFijoId)
            ->get();

        if ($asientos->isEmpty()) {
            return;
        }

        $idsEliminar = collect();
        foreach ($asientos as $asiento) {
            $idsEliminar->push($asiento->id);
            if ($asiento->grupo_redistribucion_id) {
                $grupales = LibroDiario::where('grupo_redistribucion_id', $asiento->grupo_redistribucion_id)
                    ->where('id', '!=', $asiento->id)
                    ->pluck('id');
                $idsEliminar = $idsEliminar->merge($grupales);
            }
            if ($asiento->asociar) {
                $idsEliminar->push($asiento->asociar);
            }
            $children = LibroDiario::where('asociar', $asiento->id)->pluck('id');
            $idsEliminar = $idsEliminar->merge($children);
        }

        $idsEliminar = $idsEliminar->unique();

        $subcuentas = LibroDiario::whereIn('id', $idsEliminar)->get()->map(fn($e) => [
            'medio_id' => $e->medio_id,
            'concepto_id' => $e->concepto_id,
            'detalle_id' => $e->detalle_id,
        ])->unique(fn($i) => implode('-', $i));

        LibroDiario::whereIn('id', $idsEliminar)->delete();

        foreach ($subcuentas as $subcuenta) {
            $this->libroDiarioService->recalcularSaldosSubcuenta(
                $subcuenta['medio_id'],
                $subcuenta['concepto_id'],
                $subcuenta['detalle_id']
            );
        }

        Log::info('CajaChicaAsientosService: ajustes de fondo fijo previos eliminados', [
            'caja_chica_id' => $idCajaChica,
            'asientos_eliminados' => $idsEliminar->count(),
        ]);
    }

    public function registrarRedistribucionPendiente(Pendiente $pendiente): array
    {
        $fecha = $pendiente->fechaPendientes->format('Y-m-d');
        $monto = (float) $pendiente->montoPendientes;
        $identidad = $pendiente->dependencia ? $pendiente->dependencia->dependencia : null;

        return DB::transaction(function () use ($pendiente, $fecha, $monto, $identidad) {
            $asientos = $this->libroDiarioService->registrarRedistribucion(
                [
                    'fecha' => $fecha,
                    'concepto_id' => $this->getConceptoId(),
                    'detalle_id' => $this->getDetalleFondoFijoId(),
                    'medio_id' => $this->getMedioEfectivoId(),
                    'monto' => $monto,
                    'identidad' => null,
                    'denominacion' => self::DENOMINACION_TESORERIA,
                ],
                [
                    'fecha' => $fecha,
                    'concepto_id' => $this->getConceptoId(),
                    'detalle_id' => $this->getDetallePendienteId(),
                    'medio_id' => $this->getMedioEfectivoId(),
                    'monto' => $monto,
                    'identidad' => $identidad,
                    'denominacion' => self::DENOMINACION_TESORERIA,
                ]
            );

            if (!empty($asientos[0])) {
                $this->asignarOrigen($asientos[0], self::ORIGEN_PENDIENTE, $pendiente->idPendientes);
            }
            if (!empty($asientos[1])) {
                $this->asignarOrigen($asientos[1], self::ORIGEN_PENDIENTE, $pendiente->idPendientes);
            }

            Log::info('CajaChicaAsientosService: redistribución de fondo fijo a pendiente registrada', [
                'pendiente_id' => $pendiente->idPendientes,
                'numero_pendiente' => $pendiente->pendiente,
                'monto' => $monto,
                'fecha' => $fecha,
                'asiento_salida_id' => $asientos[0]?->id,
                'asiento_entrada_id' => $asientos[1]?->id,
            ]);

            return $asientos;
        });
    }

    public function registrarAsientosRendicionPendiente(Movimiento $movimiento): ?LibroDiario
    {
        $rendido = (float) ($movimiento->rendido ?? 0);

        if ($rendido <= 0) {
            return null;
        }

        $fecha = $movimiento->fechaMovimientos->format('Y-m-d');
        $pendiente = $movimiento->pendiente;
        $montoPendiente = $pendiente ? (float) $pendiente->montoPendientes : 0;
        $identidad = $pendiente && $pendiente->dependencia ? $pendiente->dependencia->dependencia : null;
        $numeroPendiente = $pendiente ? $pendiente->pendiente : $movimiento->relPendiente;

        return DB::transaction(function () use ($movimiento, $fecha, $rendido, $montoPendiente, $identidad, $numeroPendiente) {
            if ($montoPendiente > 0) {
                $redistribucionAsientos = $this->libroDiarioService->registrarRedistribucion(
                    [
                        'fecha' => $fecha,
                        'concepto_id' => $this->getConceptoId(),
                        'detalle_id' => $this->getDetallePendienteId(),
                        'medio_id' => $this->getMedioEfectivoId(),
                        'monto' => $montoPendiente,
                        'identidad' => $identidad,
                        'denominacion' => self::DENOMINACION_TESORERIA,
                    ],
                    [
                        'fecha' => $fecha,
                        'concepto_id' => $this->getConceptoId(),
                        'detalle_id' => $this->getDetalleFondoFijoId(),
                        'medio_id' => $this->getMedioEfectivoId(),
                        'monto' => $montoPendiente,
                        'identidad' => null,
                        'denominacion' => self::DENOMINACION_TESORERIA,
                    ]
                );
                if (!empty($redistribucionAsientos[0])) {
                    $this->asignarOrigen($redistribucionAsientos[0], self::ORIGEN_MOVIMIENTO, $movimiento->idMovimientos);
                }
                if (!empty($redistribucionAsientos[1])) {
                    $this->asignarOrigen($redistribucionAsientos[1], self::ORIGEN_MOVIMIENTO, $movimiento->idMovimientos);
                }
            }

            $asiento = $this->libroDiarioService->registrarAsiento([
                'fecha' => $fecha,
                'tipo_id' => $this->getTipoSalidaId(),
                'signo_efectivo' => -1,
                'identidad' => $identidad,
                'denominacion' => self::DENOMINACION_TESORERIA,
                'descripcion' => "Rendición de Pendiente N° {$numeroPendiente} - {$movimiento->documentos}",
                'concepto_id' => $this->getConceptoId(),
                'detalle_id' => $this->getDetalleFondoFijoId(),
                'medio_id' => $this->getMedioEfectivoId(),
                'monto' => $rendido,
            ]);
            if ($asiento) {
                $this->asignarOrigen($asiento, self::ORIGEN_MOVIMIENTO, $movimiento->idMovimientos);
            }

            Log::info('CajaChicaAsientosService: asientos de rendición de pendiente registrados', [
                'movimiento_id' => $movimiento->idMovimientos,
                'pendiente_id' => $movimiento->relPendiente,
                'rendido' => $rendido,
                'fecha' => $fecha,
            ]);

            return $asiento;
        });
    }

    public function registrarAsientosRecuperacion(Movimiento $movimiento): ?LibroDiario
    {
        $recuperado = (float) ($movimiento->recuperado ?? 0);

        if ($recuperado <= 0) {
            return null;
        }

        $fecha = $movimiento->fechaMovimientos->format('Y-m-d');
        $pendiente = $movimiento->pendiente;
        $identidad = $pendiente && $pendiente->dependencia ? $pendiente->dependencia->dependencia : null;
        $numeroPendiente = $pendiente ? $pendiente->pendiente : $movimiento->relPendiente;

        return DB::transaction(function () use ($movimiento, $fecha, $recuperado, $identidad, $numeroPendiente) {
            $asiento = $this->libroDiarioService->registrarAsiento([
                'fecha' => $fecha,
                'tipo_id' => $this->getTipoEntradaId(),
                'signo_efectivo' => 1,
                'identidad' => $identidad,
                'denominacion' => self::DENOMINACION_TESORERIA,
                'descripcion' => "Recuperación de Pendiente N° {$numeroPendiente} - {$movimiento->documentos}",
                'concepto_id' => $this->getConceptoId(),
                'detalle_id' => $this->getDetalleFondoFijoId(),
                'medio_id' => $this->getMedioEfectivoId(),
                'monto' => $recuperado,
            ]);
            if ($asiento) {
                $this->asignarOrigen($asiento, self::ORIGEN_MOVIMIENTO, $movimiento->idMovimientos);
            }

            Log::info('CajaChicaAsientosService: asiento de recuperación de pendiente registrado', [
                'movimiento_id' => $movimiento->idMovimientos,
                'pendiente_id' => $movimiento->relPendiente,
                'recuperado' => $recuperado,
                'fecha' => $fecha,
                'asiento_id' => $asiento->id,
            ]);

            return $asiento;
        });
    }

    public function registrarRedistribucionPago(Pago $pago): array
    {
        $fecha = $pago->fechaEgresoPagos->format('Y-m-d');
        $monto = (float) $pago->montoPagos;
        $identidad = $pago->acreedor ? $pago->acreedor->acreedor : null;

        return DB::transaction(function () use ($pago, $fecha, $monto, $identidad) {
            $asientos = $this->libroDiarioService->registrarRedistribucion(
                [
                    'fecha' => $fecha,
                    'concepto_id' => $this->getConceptoId(),
                    'detalle_id' => $this->getDetalleFondoFijoId(),
                    'medio_id' => $this->getMedioEfectivoId(),
                    'monto' => $monto,
                    'identidad' => null,
                    'denominacion' => self::DENOMINACION_TESORERIA,
                ],
                [
                    'fecha' => $fecha,
                    'concepto_id' => $this->getConceptoId(),
                    'detalle_id' => $this->getDetallePagosId(),
                    'medio_id' => $this->getMedioEfectivoId(),
                    'monto' => $monto,
                    'identidad' => $identidad,
                    'denominacion' => self::DENOMINACION_TESORERIA,
                ]
            );

            if (!empty($asientos[0])) {
                $this->asignarOrigen($asientos[0], self::ORIGEN_PAGO, $pago->idPagos);
            }
            if (!empty($asientos[1])) {
                $this->asignarOrigen($asientos[1], self::ORIGEN_PAGO, $pago->idPagos);
            }

            Log::info('CajaChicaAsientosService: redistribución de fondo fijo a pago registrada', [
                'pago_id' => $pago->idPagos,
                'monto' => $monto,
                'fecha' => $fecha,
                'asiento_salida_id' => $asientos[0]?->id,
                'asiento_entrada_id' => $asientos[1]?->id,
            ]);

            return $asientos;
        });
    }

    public function registrarAsientosRendicionPago(Pago $pago): ?LibroDiario
    {
        $rendido = (float) ($pago->rendidoPagos ?? 0);

        if ($rendido <= 0) {
            return null;
        }

        $fecha = $pago->fechaRendicionPagos
            ? $pago->fechaRendicionPagos->format('Y-m-d')
            : now()->format('Y-m-d');

        $montoPago = (float) $pago->montoPagos;
        $identidad = $pago->acreedor ? $pago->acreedor->acreedor : null;
        $fechaPago = $pago->fechaEgresoPagos->format('Y-m-d');

        return DB::transaction(function () use ($pago, $fecha, $rendido, $montoPago, $identidad, $fechaPago) {
            if ($montoPago > 0) {
                $redistribucionAsientos = $this->libroDiarioService->registrarRedistribucion(
                    [
                        'fecha' => $fecha,
                        'concepto_id' => $this->getConceptoId(),
                        'detalle_id' => $this->getDetallePagosId(),
                        'medio_id' => $this->getMedioEfectivoId(),
                        'monto' => $montoPago,
                        'identidad' => $identidad,
                        'denominacion' => self::DENOMINACION_TESORERIA,
                    ],
                    [
                        'fecha' => $fecha,
                        'concepto_id' => $this->getConceptoId(),
                        'detalle_id' => $this->getDetalleFondoFijoId(),
                        'medio_id' => $this->getMedioEfectivoId(),
                        'monto' => $montoPago,
                        'identidad' => null,
                        'denominacion' => self::DENOMINACION_TESORERIA,
                    ]
                );
                if (!empty($redistribucionAsientos[0])) {
                    $this->asignarOrigen($redistribucionAsientos[0], self::ORIGEN_PAGO, $pago->idPagos);
                }
                if (!empty($redistribucionAsientos[1])) {
                    $this->asignarOrigen($redistribucionAsientos[1], self::ORIGEN_PAGO, $pago->idPagos);
                }
            }

            $asiento = $this->libroDiarioService->registrarAsiento([
                'fecha' => $fecha,
                'tipo_id' => $this->getTipoSalidaId(),
                'signo_efectivo' => -1,
                'identidad' => $identidad,
                'denominacion' => self::DENOMINACION_TESORERIA,
                'descripcion' => "Rendición de Pago Directo del {$fechaPago} - \${$pago->montoPagos}",
                'concepto_id' => $this->getConceptoId(),
                'detalle_id' => $this->getDetalleFondoFijoId(),
                'medio_id' => $this->getMedioEfectivoId(),
                'monto' => $rendido,
            ]);
            if ($asiento) {
                $this->asignarOrigen($asiento, self::ORIGEN_PAGO, $pago->idPagos);
            }

            Log::info('CajaChicaAsientosService: asientos de rendición de pago registrados', [
                'pago_id' => $pago->idPagos,
                'rendido' => $rendido,
                'fecha' => $fecha,
            ]);

            return $asiento;
        });
    }

    public function registrarAsientosRecuperacionPago(Pago $pago, float $montoRecuperado): ?LibroDiario
    {
        if ($montoRecuperado <= 0) {
            return null;
        }

        $fecha = $pago->fechaIngresoPagos
            ? $pago->fechaIngresoPagos->format('Y-m-d')
            : now()->format('Y-m-d');

        $identidad = $pago->acreedor ? $pago->acreedor->acreedor : null;
        $fechaPago = $pago->fechaEgresoPagos->format('Y-m-d');

        return DB::transaction(function () use ($pago, $fecha, $montoRecuperado, $identidad, $fechaPago) {
            $asiento = $this->libroDiarioService->registrarAsiento([
                'fecha' => $fecha,
                'tipo_id' => $this->getTipoEntradaId(),
                'signo_efectivo' => 1,
                'identidad' => $identidad,
                'denominacion' => self::DENOMINACION_TESORERIA,
                'descripcion' => "Recuperación de Pago Directo del {$fechaPago} - \${$pago->montoPagos}",
                'concepto_id' => $this->getConceptoId(),
                'detalle_id' => $this->getDetalleFondoFijoId(),
                'medio_id' => $this->getMedioEfectivoId(),
                'monto' => $montoRecuperado,
            ]);
            if ($asiento) {
                $this->asignarOrigen($asiento, self::ORIGEN_PAGO, $pago->idPagos);
            }

            Log::info('CajaChicaAsientosService: asiento de recuperación de pago registrado', [
                'pago_id' => $pago->idPagos,
                'monto_recuperado' => $montoRecuperado,
                'fecha' => $fecha,
                'asiento_id' => $asiento->id,
            ]);

            return $asiento;
        });
    }

    public function reemplazarRedistribucionPendiente(Pendiente $pendiente, float $montoAnterior): array
    {
        $montoNuevo = (float) $pendiente->montoPendientes;

        if (abs($montoNuevo - $montoAnterior) < 0.01) {
            return [];
        }

        $this->eliminarAsientosPorOrigen(self::ORIGEN_PENDIENTE, $pendiente->idPendientes);

        Log::info('CajaChicaAsientosService: reemplazando redistribución de pendiente', [
            'pendiente_id' => $pendiente->idPendientes,
            'monto_anterior' => $montoAnterior,
            'monto_nuevo' => $montoNuevo,
        ]);

        return $this->registrarRedistribucionPendiente($pendiente);
    }

    public function reemplazarRedistribucionPago(Pago $pago, float $montoAnterior): array
    {
        $montoNuevo = (float) $pago->montoPagos;

        if (abs($montoNuevo - $montoAnterior) < 0.01) {
            return [];
        }

        $this->eliminarAsientosPorOrigen(self::ORIGEN_PAGO, $pago->idPagos);

        Log::info('CajaChicaAsientosService: reemplazando redistribución de pago', [
            'pago_id' => $pago->idPagos,
            'monto_anterior' => $montoAnterior,
            'monto_nuevo' => $montoNuevo,
        ]);

        return $this->registrarRedistribucionPago($pago);
    }

    private function getConceptoId(): int
    {
        if ($this->conceptoId === null) {
            $this->conceptoId = LbConcepto::cajaChica()->id;
        }

        return $this->conceptoId;
    }

    private function getDetalleFondoFijoId(): int
    {
        if ($this->detalleFondoFijoId === null) {
            $this->detalleFondoFijoId = LbDetalle::fondoFijo()->id;
        }

        return $this->detalleFondoFijoId;
    }

    private function getDetallePendienteId(): int
    {
        if ($this->detallePendienteId === null) {
            $this->detallePendienteId = LbDetalle::pendiente()->id;
        }

        return $this->detallePendienteId;
    }

    private function getDetallePagosId(): int
    {
        if ($this->detallePagosId === null) {
            $this->detallePagosId = LbDetalle::pagos()->id;
        }

        return $this->detallePagosId;
    }

    private function getMedioEfectivoId(): int
    {
        if ($this->medioEfectivoId === null) {
            $this->medioEfectivoId = MedioDePago::efectivo()->id;
        }

        return $this->medioEfectivoId;
    }

    private function getTipoEntradaId(): int
    {
        if ($this->tipoEntradaId === null) {
            $tipo = LbTipo::where('nombre', 'Entrada')->firstOrFail();
            $this->tipoEntradaId = $tipo->id;
        }

        return $this->tipoEntradaId;
    }

    private function getTipoSalidaId(): int
    {
        if ($this->tipoSalidaId === null) {
            $tipo = LbTipo::where('nombre', 'Salida')->firstOrFail();
            $this->tipoSalidaId = $tipo->id;
        }

        return $this->tipoSalidaId;
    }

    private function asignarOrigen(LibroDiario $asiento, string $type, int $id): void
    {
        $asiento->update([
            'cch_origen_type' => $type,
            'cch_origen_id' => $id,
        ]);
    }

    public function eliminarAsientosPorOrigen(string $type, int $id): void
    {
        DB::transaction(function () use ($type, $id) {
            $entries = LibroDiario::where('cch_origen_type', $type)
                ->where('cch_origen_id', $id)
                ->get();

            if ($entries->isEmpty()) return;

            $allIds = collect();
            foreach ($entries as $entry) {
                $allIds->push($entry->id);
                if ($entry->grupo_redistribucion_id) {
                    $grupales = LibroDiario::where('grupo_redistribucion_id', $entry->grupo_redistribucion_id)
                        ->where('id', '!=', $entry->id)
                        ->pluck('id');
                    $allIds = $allIds->merge($grupales);
                }
                if ($entry->asociar) {
                    $allIds->push($entry->asociar);
                }
                $children = LibroDiario::where('asociar', $entry->id)->pluck('id');
                $allIds = $allIds->merge($children);
            }

            $allIds = $allIds->unique();
            $subcuentas = LibroDiario::whereIn('id', $allIds)->get()->map(fn($e) => [
                'medio_id' => $e->medio_id,
                'concepto_id' => $e->concepto_id,
                'detalle_id' => $e->detalle_id,
            ])->unique(fn($i) => implode('-', $i));

            LibroDiario::whereIn('id', $allIds)->delete();

            foreach ($subcuentas as $subcuenta) {
                $this->libroDiarioService->recalcularSaldosSubcuenta(
                    $subcuenta['medio_id'],
                    $subcuenta['concepto_id'],
                    $subcuenta['detalle_id']
                );
            }

            Log::info('CajaChicaAsientosService: asientos eliminados por origen', [
                'type' => $type,
                'id' => $id,
                'asientos_eliminados' => $allIds->count(),
            ]);
        });
    }
}
