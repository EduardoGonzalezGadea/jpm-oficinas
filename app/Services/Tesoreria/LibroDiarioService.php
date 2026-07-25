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

        if (!empty($filtros['fecha_desde'])) {
            $query->where('fecha', '>=', $filtros['fecha_desde']);
        }
        if (!empty($filtros['fecha_hasta'])) {
            $query->where('fecha', '<=', $filtros['fecha_hasta']);
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

        $anio = $filtros['anio'] ?? now()->year;
        $query->whereYear('fecha', $anio);

        return $query->orderByDesc('fecha')->orderByDesc('id')->get();
    }

    public function registrarAsiento(array $data): LibroDiario
    {
        return DB::transaction(function () use ($data) {
            $fecha = $data['fecha'];
            $anio = date('Y', strtotime($fecha));
            $tipo = LbTipo::findOrFail($data['tipo_id']);
            $signoEfectivo = $data['signo_efectivo'] ?? $tipo->signo;
            $numero = LibroDiario::generarNumero($anio, $signoEfectivo);

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
            ]);

            $this->recalcularSaldosSubcuenta(
                $data['medio_id'],
                $data['concepto_id'],
                $data['detalle_id']
            );

            return $registro->fresh();
        });
    }

    public function saldosActualesPorFlujo(array $filtros = []): Collection
    {
        $query = LibroDiario::with(['concepto', 'detalle', 'medio'])
            ->orderBy('fecha')
            ->orderBy('id');

        foreach (['concepto_id', 'detalle_id', 'medio_id'] as $campo) {
            if (!empty($filtros[$campo])) {
                $query->where($campo, $filtros[$campo]);
            }
        }

        if (!empty($filtros['anio'])) {
            $query->whereYear('fecha', $filtros['anio']);
        }

        if (!empty($filtros['hasta'])) {
            $query->whereDate('fecha', '<=', $filtros['hasta']);
        }

        if (!empty($filtros['desde'])) {
            $query->whereDate('fecha', '>=', $filtros['desde']);
        }

        return $query->get()
            ->groupBy(fn (LibroDiario $asiento) => implode('-', [
                $asiento->medio_id,
                $asiento->concepto_id,
                $asiento->detalle_id,
            ]))
            ->map(function (Collection $asientos) {
                $ultimo = $asientos->last();
                $ultimo->saldo_actual = $asientos->sum(
                    fn (LibroDiario $asiento) => $asiento->monto * $asiento->signo_efectivo
                );

                return $ultimo;
            })
            ->values();
    }

    public function listarAsientosBaseDisponibles(int $conceptoId, int $detalleId): Collection
    {
        return $this->saldosActualesPorFlujo([
            'concepto_id' => $conceptoId,
            'detalle_id' => $detalleId,
        ])->filter(fn (LibroDiario $asiento) => $asiento->saldo_actual > 0)->values();
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
        return DB::transaction(function () use ($origen, $destino) {
            $fecha = $origen['fecha'];
            $anio = date('Y', strtotime($fecha));

            $tipoSalida = LbTipo::where('nombre', 'Salida')->firstOrFail();
            $tipoEntrada = LbTipo::where('nombre', 'Entrada')->firstOrFail();

            $grupoId = LibroDiario::generarGrupoRedistribucionId();
            
            $saldoOrigen = $this->saldoActualFlujo(
                $origen['medio_id'], $origen['concepto_id'], $origen['detalle_id']
            );

            if ((float) $origen['monto'] > $saldoOrigen) {
                throw new \DomainException('El monto a redistribuir supera el saldo disponible del flujo de origen.');
            }

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

    public function saldoActualFlujo(int $medioId, int $conceptoId, int $detalleId): float
    {
        return (float) $this->saldosActualesPorFlujo([
            'medio_id' => $medioId,
            'concepto_id' => $conceptoId,
            'detalle_id' => $detalleId,
        ])->sum('saldo_actual');
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

        return $entry->fresh();
    }

    public function eliminarAsiento(int $id): void
    {
        DB::transaction(function () use ($id) {
            $entry = LibroDiario::findOrFail($id);

            $this->assertCfeNotInPlanilla($entry);

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

    public function recalcularSaldosSubcuenta(int $medioId, int $conceptoId, int $detalleId): void
    {
        $registros = LibroDiario::where('medio_id', $medioId)
            ->where('concepto_id', $conceptoId)
            ->where('detalle_id', $detalleId)
            ->orderBy('fecha')
            ->orderBy('id')
            ->get();

        $saldo = 0;
        foreach ($registros as $registro) {
            $saldo += $registro->monto * $registro->signo_efectivo;
            $registro->update(['saldo' => $saldo]);
        }
    }
}
