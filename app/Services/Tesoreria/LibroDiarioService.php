<?php

namespace App\Services\Tesoreria;

use App\Models\Tesoreria\LbTipo;
use App\Models\Tesoreria\LibroDiario;
use App\Models\Tesoreria\TesCfe;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class LibroDiarioService
{
    public function listar(array $filtros = []): Collection
    {
        $query = LibroDiario::with(['tipo', 'concepto', 'detalle', 'medio']);
        $fechaEfectiva = 'COALESCE(fecha_confirmacion, fecha)';

        if (!empty($filtros['fecha_desde'])) {
            $query->whereRaw("{$fechaEfectiva} >= ?", [$filtros['fecha_desde'] . ' 00:00:00']);
        }
        if (!empty($filtros['fecha_hasta'])) {
            $query->whereRaw("{$fechaEfectiva} <= ?", [$filtros['fecha_hasta'] . ' 23:59:59']);
        }
        if (!empty($filtros['tipo_id'])) {
            $query->where('tipo_id', $filtros['tipo_id']);
        }
        if (!empty($filtros['concepto_id'])) {
            $query->where('concepto_id', $filtros['concepto_id']);
        }
        if (!empty($filtros['detalle_id'])) {
            $query->where('detalle_id', $filtros['detalle_id']);
        }
        if (!empty($filtros['medio_id'])) {
            $query->where('medio_id', $filtros['medio_id']);
        }
        if (!empty($filtros['search'])) {
            $term = '%' . $filtros['search'] . '%';
            $query->where(function (Builder $q) use ($term) {
                $q->where('identidad', 'like', $term)
                  ->orWhere('denominacion', 'like', $term)
                  ->orWhere('descripcion', 'like', $term);
            });
        }


        // Filtro por monto exacto: si se especifica un valor numérico válido
        // (> 0), se filtran los asientos cuyo monto coincide exactamente.
        $montoExacto = isset($filtros['monto']) ? (float) $filtros['monto'] : null;
        if ($montoExacto !== null && $montoExacto > 0) {
            $query->where('monto', $montoExacto);
        }

        $anio = $filtros['anio'] ?? now()->year;
        $query->whereRaw("YEAR({$fechaEfectiva}) = ?", [$anio]);

        return $query->orderByDesc(DB::raw($fechaEfectiva))->orderByDesc('id')->get();
    }

    public function registrarAsiento(array $data): LibroDiario
    {
        return DB::transaction(function () use ($data) {
            $fecha = $data['fecha'];
            $anio = date('Y', strtotime($fecha));
            $tipo = LbTipo::findOrFail($data['tipo_id']);
            $signoEfectivo = $data['signo_efectivo'] ?? $tipo->signo;
            $numero = LibroDiario::generarNumero($anio, $signoEfectivo);

            $esConfirmado = $data['confirmado'] ?? true;

            $registro = LibroDiario::create([
                'fecha' => $fecha,
                'tipo_id' => $data['tipo_id'],
                'numero' => $numero,
                'signo_efectivo' => $signoEfectivo,
                'identidad' => isset($data['identidad']) ? mb_strtoupper($data['identidad']) : null,
                'denominacion' => isset($data['denominacion']) ? mb_strtoupper($data['denominacion']) : null,
                'descripcion' => isset($data['descripcion']) ? mb_strtoupper($data['descripcion']) : null,
                'concepto_id' => $data['concepto_id'],
                'detalle_id' => $data['detalle_id'],
                'medio_id' => $data['medio_id'],
                'monto' => $data['monto'],
                'saldo' => 0,
                'asociar' => $data['asociar'] ?? null,
                'cfe_id' => $data['cfe_id'] ?? null,
                'es_contra_asiento' => $data['es_contra_asiento'] ?? false,
                'documento_referencia' => isset($data['documento_referencia']) ? mb_strtoupper($data['documento_referencia']) : null,
                'confirmado' => $esConfirmado,
                'fecha_confirmacion' => $esConfirmado ? $fecha : null,
            ]);

            $this->recalcularSaldosSubcuenta(
                $data['medio_id'],
                $data['concepto_id'],
                $data['detalle_id']
            );

            return $registro->fresh();
        });
    }

    /**
     * Registra una salida. Si el asiento base (el ítem donde está el dinero)
     * pertenece a un flujo distinto al de la salida (otra identidad y/o
     * medio/concepto/detalle), primero redistribuye el importe desde el ítem
     * de origen hacia el ítem destino y luego registra la salida sobre este.
     */
    public function registrarSalida(array $data): LibroDiario
    {
        return DB::transaction(function () use ($data) {
            $asientoBase = !empty($data['asociar'])
                ? LibroDiario::find($data['asociar'])
                : null;

            if ($asientoBase && !$this->flujosCoinciden($asientoBase, $data)) {
                $this->registrarRedistribucion(
                    [
                        'fecha' => $data['fecha'],
                        'concepto_id' => $asientoBase->concepto_id,
                        'detalle_id' => $asientoBase->detalle_id,
                        'medio_id' => $asientoBase->medio_id,
                        'monto' => $data['monto'],
                        'identidad' => $asientoBase->identidad,
                        'denominacion' => $asientoBase->denominacion,
                    ],
                    [
                        'fecha' => $data['fecha'],
                        'concepto_id' => $data['concepto_id'],
                        'detalle_id' => $data['detalle_id'],
                        'medio_id' => $data['medio_id'],
                        'monto' => $data['monto'],
                        'identidad' => $data['identidad'] ?? null,
                        'denominacion' => $data['denominacion'] ?? null,
                    ]
                );

                $data['asociar'] = null;
            }

            return $this->registrarAsiento($data);
        });
    }

    public function saldosActualesPorFlujo(array $filtros = [], bool $permitirNegativos = false): Collection
    {
        $fechaEfectiva = 'COALESCE(fecha_confirmacion, fecha)';

        $query = LibroDiario::with(['concepto', 'detalle', 'medio'])
            ->orderBy(DB::raw($fechaEfectiva))
            ->orderBy('id');

        foreach (['concepto_id', 'detalle_id', 'medio_id'] as $campo) {
            if (!empty($filtros[$campo])) {
                $query->where($campo, $filtros[$campo]);
            }
        }

        if (!empty($filtros['anio'])) {
            $query->whereRaw("YEAR({$fechaEfectiva}) = ?", [$filtros['anio']]);
        }

        if (!empty($filtros['hasta'])) {
            $query->whereRaw("{$fechaEfectiva} <= ?", [$filtros['hasta'] . ' 23:59:59']);
        }

        if (!empty($filtros['desde'])) {
            $query->whereRaw("{$fechaEfectiva} >= ?", [$filtros['desde'] . ' 00:00:00']);
        }

        // Por defecto, entradas pendientes no contabilizan, salidas siempre sí.
        // Si 'incluir_pendientes' está activado, se incluyen todos los asientos
        // (usado para listar asientos base disponibles para salidas).
        if (empty($filtros['incluir_pendientes'])) {
            $query->where(function ($q) {
                $q->whereNotNull('fecha_confirmacion')
                  ->orWhere('signo_efectivo', -1);
            });
        }

        // Si se filtra por período (desde), saldo_actual representa el movimiento
        // neto del período (puede ser negativo). En caso contrario representa el
        // saldo disponible de cada flujo+identidad, nunca negativo.
        $esPeriodo = !empty($filtros['desde']);

        $grupos = $query->get()
            ->groupBy(fn (LibroDiario $asiento) => implode('-', [
                $asiento->medio_id,
                $asiento->concepto_id,
                $asiento->detalle_id,
                $this->normalizarTexto($asiento->identidad),
                $this->normalizarTexto($asiento->denominacion),
            ]));

        if (array_key_exists('identidad', $filtros)) {
            $identidad = $this->normalizarTexto($filtros['identidad']);
            $grupos = $grupos->filter(
                fn (Collection $asientos) => $this->normalizarTexto($asientos->first()->identidad) === $identidad
            );
        }

        if (array_key_exists('denominacion', $filtros)) {
            $denominacion = $this->normalizarTexto($filtros['denominacion']);
            $grupos = $grupos->filter(
                fn (Collection $asientos) => $this->normalizarTexto($asientos->first()->denominacion) === $denominacion
            );
        }

        return $grupos
            ->map(function (Collection $asientos) use ($esPeriodo, $permitirNegativos) {
                $ultimo = $asientos->last();

                if ($esPeriodo) {
                    $ultimo->saldo_actual = $asientos->sum(
                        fn (LibroDiario $asiento) => $asiento->monto * $asiento->signo_efectivo
                    );
                } else {
                    $saldo = 0;
                    foreach ($asientos as $asiento) {
                        $saldo += $asiento->monto * $asiento->signo_efectivo;
                    }
                    $ultimo->saldo_actual = $permitirNegativos ? $saldo : max(0, $saldo);
                }

                return $ultimo;
            })
            ->values();
    }

    public function listarAsientosBaseDisponibles(int $conceptoId, int $detalleId, ?int $medioId = null): Collection
    {
        return $this->saldosActualesPorFlujo(array_filter([
            'concepto_id' => $conceptoId,
            'detalle_id' => $detalleId,
            'medio_id' => $medioId,
            'incluir_pendientes' => true,
        ]))->filter(fn (LibroDiario $asiento) => $asiento->saldo_actual > 0)->values();
    }

    public function listarAsientosBaseDisponiblesEntradas(int $conceptoId, int $detalleId): Collection
    {
        return $this->saldosActualesPorFlujo([
            'concepto_id' => $conceptoId,
            'detalle_id' => $detalleId,
        ])->filter(fn (LibroDiario $asiento) => $asiento->saldo_actual < 0)->values();
    }

    public function registrarRedistribucion(array $origen, array $destino): array
    {
        // Validación preventiva: verificar saldo ANTES de iniciar la transacción.
        // El saldo disponible se evalúa sobre la misma identidad y denominación del origen.
        $saldoOrigen = $this->saldoActualFlujo(
            $origen['medio_id'],
            $origen['concepto_id'],
            $origen['detalle_id'],
            $this->normalizarTexto($origen['identidad'] ?? null),
            $this->normalizarTexto($origen['denominacion'] ?? null)
        );

        if ((float) $origen['monto'] > $saldoOrigen) {
            throw new \DomainException('El monto a redistribuir supera el saldo disponible del flujo de origen.');
        }

        return DB::transaction(function () use ($origen, $destino) {
            $fecha = $origen['fecha'];
            $anio = date('Y', strtotime($fecha));

            $tipoSalida = LbTipo::where('nombre', 'Salida')->firstOrFail();
            $tipoEntrada = LbTipo::where('nombre', 'Entrada')->firstOrFail();

            $grupoId = LibroDiario::generarGrupoRedistribucionId();

            $registroSalida = LibroDiario::create([
                'fecha' => $fecha,
                'tipo_id' => $tipoSalida->id,
                'numero' => LibroDiario::generarNumero($anio, -1),
                'signo_efectivo' => -1,
                'identidad' => isset($origen['identidad']) ? mb_strtoupper($origen['identidad']) : null,
                'denominacion' => isset($origen['denominacion']) ? mb_strtoupper($origen['denominacion']) : null,
                'concepto_id' => $origen['concepto_id'],
                'detalle_id' => $origen['detalle_id'],
                'medio_id' => $origen['medio_id'],
                'monto' => $origen['monto'],
                'saldo' => 0,
                'grupo_redistribucion_id' => $grupoId,
                'confirmado' => true,
                'fecha_confirmacion' => $fecha,
            ]);

            $registroEntrada = LibroDiario::create([
                'fecha' => $fecha,
                'tipo_id' => $tipoEntrada->id,
                'numero' => LibroDiario::generarNumero($anio, 1),
                'signo_efectivo' => 1,
                'identidad' => isset($destino['identidad']) ? mb_strtoupper($destino['identidad']) : null,
                'denominacion' => isset($destino['denominacion']) ? mb_strtoupper($destino['denominacion']) : null,
                'concepto_id' => $destino['concepto_id'],
                'detalle_id' => $destino['detalle_id'],
                'medio_id' => $destino['medio_id'],
                'monto' => $destino['monto'],
                'saldo' => 0,
                'asociar' => $registroSalida->id,
                'grupo_redistribucion_id' => $grupoId,
                'confirmado' => true,
                'fecha_confirmacion' => $fecha,
            ]);

            $registroSalida->update(['asociar' => $registroEntrada->id]);

            $this->recalcularSaldosSubcuenta(
                $origen['medio_id'], $origen['concepto_id'], $origen['detalle_id']
            );
            $this->recalcularSaldosSubcuenta(
                $destino['medio_id'], $destino['concepto_id'], $destino['detalle_id']
            );

            return [$registroSalida->fresh(), $registroEntrada->fresh()];
        });
    }

    public function saldoActualFlujo(
        int $medioId,
        int $conceptoId,
        int $detalleId,
        ?string $identidad = null,
        ?string $denominacion = null
    ): float {
        $filtros = [
            'medio_id' => $medioId,
            'concepto_id' => $conceptoId,
            'detalle_id' => $detalleId,
        ];

        // Si se indica una identidad/denominación, se filtra por ellas; si es null
        // se suman todos los registros del flujo.
        if ($identidad !== null) {
            $filtros['identidad'] = $identidad;
        }

        if ($denominacion !== null) {
            $filtros['denominacion'] = $denominacion;
        }

        return (float) $this->saldosActualesPorFlujo($filtros)->sum('saldo_actual');
    }

    public function confirmarEntrada(int $libroDiarioId): void
    {
        $asiento = LibroDiario::findOrFail($libroDiarioId);
        $asiento->confirmar();
        $this->recalcularSaldosSubcuenta(
            $asiento->medio_id,
            $asiento->concepto_id,
            $asiento->detalle_id
        );

        // Recalcular también las subcuentas de los asientos hijos asociados,
        // ya que la confirmación de la entrada base afecta su saldo disponible.
        $this->recalcularSaldosAsientosAsociados($asiento);

        // Si forma parte de una redistribución, el asiento asociado
        // (salida/entrada) debe quedar confirmado de la misma forma.
        $this->sincronizarConfirmacionRedistribucion($asiento, true);
    }

    public function desconfirmarEntrada(int $libroDiarioId): void
    {
        $asiento = LibroDiario::findOrFail($libroDiarioId);
        $asiento->update(['confirmado' => false, 'fecha_confirmacion' => null]);
        $this->recalcularSaldosSubcuenta(
            $asiento->medio_id,
            $asiento->concepto_id,
            $asiento->detalle_id
        );

        // Recalcular también las subcuentas de los asientos hijos asociados.
        $this->recalcularSaldosAsientosAsociados($asiento);

        // Si forma parte de una redistribución, el asiento asociado
        // (salida/entrada) debe quedar desconfirmado de la misma forma.
        $this->sincronizarConfirmacionRedistribucion($asiento, false);
    }

    public function toggleConfirmacion(int $libroDiarioId, $fechaConfirmacion = null): bool
    {
        $asiento = LibroDiario::findOrFail($libroDiarioId);

        if (!is_null($asiento->fecha_confirmacion)) {
            if ($asiento->signo_efectivo === -1 && !$asiento->grupo_redistribucion_id) {
                throw new \DomainException('No se puede desconfirmar un asiento de salida (egreso).');
            }
            $asiento->update(['confirmado' => false, 'fecha_confirmacion' => null]);
        } else {
            $asiento->confirmar($fechaConfirmacion);
        }

        $this->recalcularSaldosSubcuenta(
            $asiento->medio_id,
            $asiento->concepto_id,
            $asiento->detalle_id
        );

        // Recalcular también las subcuentas de los asientos hijos asociados,
        // ya que la confirmación/desconfirmación de la entrada base afecta
        // el saldo disponible del que dependen.
        $this->recalcularSaldosAsientosAsociados($asiento);

        // Si forma parte de una redistribución, el asiento asociado
        // (salida/entrada) debe quedar en el mismo estado de confirmación.
        $confirmado = !is_null($asiento->fresh()->fecha_confirmacion);
        $this->sincronizarConfirmacionRedistribucion($asiento, $confirmado, $fechaConfirmacion);

        return $confirmado;
    }

    /**
     * Sincroniza el estado de confirmación del asiento asociado dentro del
     * mismo grupo de redistribución. Cuando se confirma o desconfirma un
     * asiento (entrada o salida) que forma parte de una redistribución, el
     * otro asiento del grupo debe quedar en el mismo estado.
     */
    private function sincronizarConfirmacionRedistribucion(LibroDiario $asiento, bool $confirmado, $fechaConfirmacion = null): void
    {
        if (!$asiento->grupo_redistribucion_id) {
            return;
        }

        $asociados = LibroDiario::where('grupo_redistribucion_id', $asiento->grupo_redistribucion_id)
            ->where('id', '!=', $asiento->id)
            ->get();

        foreach ($asociados as $asociado) {
            if ($confirmado) {
                $asociado->confirmar($fechaConfirmacion);
            } else {
                $asociado->update(['confirmado' => false, 'fecha_confirmacion' => null]);
            }

            $this->recalcularSaldosSubcuenta(
                $asociado->medio_id,
                $asociado->concepto_id,
                $asociado->detalle_id
            );
        }
    }

    public function confirmarPorDocumento(string $documentoReferencia, $fechaConfirmacion = null): int
    {
        $asientos = LibroDiario::pendientes()
            ->where('documento_referencia', $documentoReferencia)
            ->get();

        $subcuentas = [];
        foreach ($asientos as $asiento) {
            $asiento->confirmar($fechaConfirmacion);
            $subcuentas[] = [
                'medio_id' => $asiento->medio_id,
                'concepto_id' => $asiento->concepto_id,
                'detalle_id' => $asiento->detalle_id,
            ];

            // Incluir también las subcuentas de los asientos hijos asociados.
            $hijos = LibroDiario::where('asociar', $asiento->id)->get();
            foreach ($hijos as $hijo) {
                $subcuentas[] = [
                    'medio_id' => $hijo->medio_id,
                    'concepto_id' => $hijo->concepto_id,
                    'detalle_id' => $hijo->detalle_id,
                ];
            }
        }

        // Recalcular saldos de cada subcuenta afectada (incluidas las de los hijos)
        $subcuentasUnicas = collect($subcuentas)->unique(function ($item) {
            return $item['medio_id'] . '-' . $item['concepto_id'] . '-' . $item['detalle_id'];
        });

        foreach ($subcuentasUnicas as $subcuenta) {
            $this->recalcularSaldosSubcuenta(
                $subcuenta['medio_id'],
                $subcuenta['concepto_id'],
                $subcuenta['detalle_id']
            );
        }

        return $asientos->count();
    }

    public function desconfirmarPorDocumento(string $documentoReferencia): int
    {
        $asientos = LibroDiario::confirmados()
            ->where('documento_referencia', $documentoReferencia)
            ->get();

        $tieneSalidasConfirmadas = $asientos->contains(fn($a) => $a->signo_efectivo === -1);
        if ($tieneSalidasConfirmadas) {
            throw new \DomainException('El documento contiene asientos de salida (egresos) confirmados que no se pueden desconfirmar.');
        }

        $subcuentas = [];
        foreach ($asientos as $asiento) {
            $asiento->update(['confirmado' => false, 'fecha_confirmacion' => null]);
            $subcuentas[] = [
                'medio_id' => $asiento->medio_id,
                'concepto_id' => $asiento->concepto_id,
                'detalle_id' => $asiento->detalle_id,
            ];

            // Incluir también las subcuentas de los asientos hijos asociados.
            $hijos = LibroDiario::where('asociar', $asiento->id)->get();
            foreach ($hijos as $hijo) {
                $subcuentas[] = [
                    'medio_id' => $hijo->medio_id,
                    'concepto_id' => $hijo->concepto_id,
                    'detalle_id' => $hijo->detalle_id,
                ];
            }
        }

        // Recalcular saldos de cada subcuenta afectada (incluidas las de los hijos)
        $subcuentasUnicas = collect($subcuentas)->unique(function ($item) {
            return $item['medio_id'] . '-' . $item['concepto_id'] . '-' . $item['detalle_id'];
        });

        foreach ($subcuentasUnicas as $subcuenta) {
            $this->recalcularSaldosSubcuenta(
                $subcuenta['medio_id'],
                $subcuenta['concepto_id'],
                $subcuenta['detalle_id']
            );
        }

        return $asientos->count();
    }

    public function actualizarCamposNoFinancieros(int $id, array $data): LibroDiario
    {
        $entry = LibroDiario::findOrFail($id);

        $allowed = LibroDiario::getEditableCampos();
        $filtered = array_intersect_key($data, array_flip($allowed));

        foreach (['identidad', 'denominacion', 'descripcion'] as $campo) {
            if (isset($filtered[$campo])) {
                $filtered[$campo] = mb_strtoupper($filtered[$campo]);
            }
        }

        $entry->update($filtered);

        // Si cambió la identidad, el saldo corrido de esa subcuenta se recorre
        // por identidades y puede variar, por lo que se recalcula.
        if (array_key_exists('identidad', $filtered)) {
            $this->recalcularSaldosSubcuenta($entry->medio_id, $entry->concepto_id, $entry->detalle_id);
        }

        return $entry->fresh();
    }

    public function eliminarAsiento(int $id): void
    {
        DB::transaction(function () use ($id) {
            $entry = LibroDiario::findOrFail($id);

            $this->assertCfeNotInPlanilla($entry);
            $this->assertNotCajaChicaOrigen($entry);

            $entriesToDelete = collect([$entry]);

            if ($entry->grupo_redistribucion_id) {
                $grupales = LibroDiario::where('grupo_redistribucion_id', $entry->grupo_redistribucion_id)
                    ->where('id', '!=', $entry->id)
                    ->get();
                $entriesToDelete = $entriesToDelete->merge($grupales);
            } elseif ($entry->asociar) {
                $assoc = LibroDiario::find($entry->asociar);
                if ($assoc) {
                    $entriesToDelete->push($assoc);
                }
            } else {
                $children = LibroDiario::where('asociar', $entry->id)->get();
                $entriesToDelete = $entriesToDelete->merge($children);
            }

            $subcuentas = $entriesToDelete->map(fn($e) => [
                'medio_id' => $e->medio_id,
                'concepto_id' => $e->concepto_id,
                'detalle_id' => $e->detalle_id,
            ])->unique(function ($item) {
                return $item['medio_id'] . '-' . $item['concepto_id'] . '-' . $item['detalle_id'];
            });

            $ids = $entriesToDelete->pluck('id');
            LibroDiario::whereIn('id', $ids)->delete();

            foreach ($subcuentas as $subcuenta) {
                $this->recalcularSaldosSubcuenta(
                    $subcuenta['medio_id'],
                    $subcuenta['concepto_id'],
                    $subcuenta['detalle_id']
                );
            }
        });
    }

    public function eliminarAsientoConCfe(int $id): void
    {
        DB::transaction(function () use ($id) {
            $entry = LibroDiario::findOrFail($id);

            $this->assertCfeNotInPlanilla($entry);
            $this->assertNotCajaChicaOrigen($entry);

            if (!$entry->cfe_id) {
                throw new \RuntimeException('El asiento no tiene un CFE asociado.');
            }

            $cfe = TesCfe::find($entry->cfe_id);
            if ($cfe) {
                $cfe->delete();
            }

            $entriesToDelete = collect([$entry]);

            if ($entry->grupo_redistribucion_id) {
                $grupales = LibroDiario::where('grupo_redistribucion_id', $entry->grupo_redistribucion_id)
                    ->where('id', '!=', $entry->id)
                    ->get();
                $entriesToDelete = $entriesToDelete->merge($grupales);
            } elseif ($entry->asociar) {
                $assoc = LibroDiario::find($entry->asociar);
                if ($assoc) {
                    $entriesToDelete->push($assoc);
                }
            } else {
                $children = LibroDiario::where('asociar', $entry->id)->get();
                $entriesToDelete = $entriesToDelete->merge($children);
            }

            $subcuentas = $entriesToDelete->map(fn($e) => [
                'medio_id' => $e->medio_id,
                'concepto_id' => $e->concepto_id,
                'detalle_id' => $e->detalle_id,
            ])->unique(function ($item) {
                return $item['medio_id'] . '-' . $item['concepto_id'] . '-' . $item['detalle_id'];
            });

            $ids = $entriesToDelete->pluck('id');
            LibroDiario::whereIn('id', $ids)->delete();

            foreach ($subcuentas as $subcuenta) {
                $this->recalcularSaldosSubcuenta(
                    $subcuenta['medio_id'],
                    $subcuenta['concepto_id'],
                    $subcuenta['detalle_id']
                );
            }
        });
    }

    private function assertCfeNotInPlanilla(LibroDiario $entry): void
    {
        if ($entry->cfe_id) {
            $cfe = TesCfe::withCount(['items as items_en_planilla_count' => fn($q) => $q->whereNotNull('planilla_er_id')])->find($entry->cfe_id);
            if ($cfe && $cfe->items_en_planilla_count > 0) {
                throw new \RuntimeException('No se puede eliminar el asiento porque la recaudación asociada ya tiene ítems en Planilla para Estado de Recaudación.');
            }
        }
    }

    private function assertNotCajaChicaOrigen(LibroDiario $entry): void
    {
        if ($entry->cch_origen_type) {
            throw new \RuntimeException('No se puede eliminar un asiento generado por Caja Chica desde el Libro Diario. Use el módulo de Caja Chica.');
        }
    }

    /**
     * Recalcula el saldo corrido de la subcuenta (medio+concepto+detalle)
     * ordenado por fecha efectiva (COALESCE(fecha_confirmacion, fecha)).
     *
     * Regla contable (compartida con saldosActualesPorFlujo):
     *  - Saldo por identidad, truncado en 0 (nunca negativo).
     *  - Entradas NO confirmadas no contabilizan.
     *  - Salidas siempre contabilizan (pueden llevar la identidad a 0).
     *
     * Este truncamiento es el que mantiene consistente la ecuación
     * SALDO ANTERIOR + MOVIMIENTOS DEL PERÍODO = SALDO ACTUAL, siempre que
     * ningún flujo pase de negativo a positivo dentro del período (caso
     * atípico no contemplado: una identidad con egresos previos y luego
     * entradas confirmadas en el mismo período).
     */
    public function recalcularSaldosSubcuenta(int $medioId, int $conceptoId, int $detalleId): void
    {
        $registros = LibroDiario::where('medio_id', $medioId)
            ->where('concepto_id', $conceptoId)
            ->where('detalle_id', $detalleId)
            ->orderBy(DB::raw('COALESCE(fecha_confirmacion, fecha)'))
            ->orderBy('id')
            ->get();

        // Saldo corrido por identidad (nunca negativo): cada identidad lleva su
        // propio acumulado dentro de la subcuenta.
        // Entradas pendientes no contabilizan, salidas siempre sí.
        $saldosPorIdentidad = [];
        foreach ($registros as $registro) {
            // Entrada no confirmada → no suma al saldo corrido
            if ($registro->signo_efectivo === 1 && is_null($registro->fecha_confirmacion)) {
                $registro->update(['saldo' => 0]);
                continue;
            }

            $identidad = $this->normalizarIdentidad($registro->identidad);
            $saldosPorIdentidad[$identidad] = max(
                0,
                ($saldosPorIdentidad[$identidad] ?? 0) + $registro->monto * $registro->signo_efectivo
            );
            $registro->update(['saldo' => round($saldosPorIdentidad[$identidad], 2)]);
        }
    }

    /**
     * Recalcula los saldos de las subcuentas de todos los asientos hijos
     * (aquellos que referencian al asiento dado mediante el campo 'asociar').
     * Esto es necesario cuando se confirma o desconfirma una entrada que
     * actúa como asiento base para salidas vinculadas.
     */
    private function recalcularSaldosAsientosAsociados(LibroDiario $asiento): void
    {
        $hijos = LibroDiario::where('asociar', $asiento->id)->get();

        $subcuentasHijos = $hijos->map(fn($h) => [
            'medio_id'    => $h->medio_id,
            'concepto_id' => $h->concepto_id,
            'detalle_id'  => $h->detalle_id,
        ])->unique(fn($item) => $item['medio_id'] . '-' . $item['concepto_id'] . '-' . $item['detalle_id']);

        foreach ($subcuentasHijos as $subcuenta) {
            $this->recalcularSaldosSubcuenta(
                $subcuenta['medio_id'],
                $subcuenta['concepto_id'],
                $subcuenta['detalle_id']
            );
        }
    }

    /**
     * Determina si el asiento base pertenece al mismo flujo que los datos de
     * la salida (mismo medio, concepto, detalle, identidad y denominación normalizadas).
     */
    private function flujosCoinciden(LibroDiario $asientoBase, array $data): bool
    {
        return (int) $asientoBase->medio_id === (int) $data['medio_id']
            && (int) $asientoBase->concepto_id === (int) $data['concepto_id']
            && (int) $asientoBase->detalle_id === (int) $data['detalle_id']
            && $this->normalizarTexto($asientoBase->identidad)
                === $this->normalizarTexto($data['identidad'] ?? null)
            && $this->normalizarTexto($asientoBase->denominacion)
                === $this->normalizarTexto($data['denominacion'] ?? null);
    }

    /**
     * Normaliza un campo de texto (identidad, denominación) para agrupar saldos o comparar flujos:
     * NULL y vacío se tratan como "" y se eliminan espacios en blanco al inicio y final.
     */
    private function normalizarTexto(?string $texto): string
    {
        return mb_strtoupper(trim((string) ($texto ?? '')));
    }

    /**
     * @deprecated Usar normalizarTexto
     */
    private function normalizarIdentidad(?string $identidad): string
    {
        return $this->normalizarTexto($identidad);
    }
}
