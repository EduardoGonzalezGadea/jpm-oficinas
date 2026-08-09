<?php

namespace App\Models\Tesoreria\Cajas;

use App\Models\User;
use App\Models\Tesoreria\LibroDiario;
use App\Models\Tesoreria\MedioDePago;
use App\Traits\LogsActivityTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CajaApertura extends Model
{
    use HasFactory, SoftDeletes, LogsActivityTrait;

    protected $table = 'tes_cajas_aperturas';

    protected $fillable = [
        'cajero_id', 'fecha_apertura', 'hora_apertura',
        'saldo_inicial', 'saldo_cierre', 'fecha_cierre',
        'estado', 'observaciones',
        'created_by', 'updated_by', 'deleted_by',
    ];

    protected $casts = [
        'fecha_apertura' => 'date',
        'fecha_cierre' => 'datetime',
        'saldo_inicial' => 'decimal:2',
        'saldo_cierre' => 'decimal:2',
    ];

    public function getHoraAperturaFormateadaAttribute()
    {
        if (empty($this->attributes['hora_apertura'])) {
            return null;
        }
        return substr($this->attributes['hora_apertura'], 0, 5);
    }

    // Relaciones
    public function cajero()
    {
        return $this->belongsTo(User::class, 'cajero_id');
    }

    public function desgloses()
    {
        return $this->hasMany(CajaDesglose::class, 'caja_apertura_id');
    }

    public function arqueos()
    {
        return $this->hasMany(CajaArqueo::class, 'caja_apertura_id');
    }

    public function movimientos()
    {
        return $this->hasMany(CajaMovimiento::class, 'caja_apertura_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    // Scopes
    public function scopeAbiertas($query)
    {
        return $query->where('estado', 'abierta');
    }

    public function scopeCerradas($query)
    {
        return $query->where('estado', 'cerrada');
    }

    public function scopePorCajero($query, $cajeroId)
    {
        return $query->where('cajero_id', $cajeroId);
    }

    // Métodos — los totales se calculan desde el Libro Diario usando los asientos
    // vinculados a los movimientos de esta caja.

    /**
     * IDs de asientos del libro diario vinculados a esta caja.
     */
    protected function libroIds(): array
    {
        return $this->movimientos()
            ->whereNotNull('libro_diario_id')
            ->pluck('libro_diario_id')
            ->all();
    }

    /**
     * Query base sobre el libro diario de esta caja, opcionalmente filtrado por medio.
     * Entradas pendientes no contabilizan, salidas siempre sí.
     */
    protected function queryLibro(?int $medioId = null)
    {
        $ids = $this->libroIds();
        $q = LibroDiario::whereIn('id', $ids)
            ->whereNull('deleted_at')
            ->where(function ($q) {
                $q->whereNotNull('fecha_confirmacion')
                  ->orWhere('signo_efectivo', -1);
            });
        if ($medioId !== null) {
            $q->where('medio_id', $medioId);
        }
        return $q;
    }

    public function obtenerSaldoActual(): float
    {
        $efectivoId = MedioDePago::where('nombre', MedioDePago::EFECTIVO)->value('id');
        $entradas = (float) $this->queryLibro($efectivoId)->where('signo_efectivo', 1)->sum('monto');
        $salidas  = (float) $this->queryLibro($efectivoId)->where('signo_efectivo', -1)->sum('monto');
        return (float) $this->saldo_inicial + $entradas - $salidas;
    }

    public function totalIngresos(): float
    {
        $efectivoId = MedioDePago::where('nombre', MedioDePago::EFECTIVO)->value('id');
        return (float) $this->queryLibro($efectivoId)->where('signo_efectivo', 1)->sum('monto');
    }

    public function totalEgresos(): float
    {
        $efectivoId = MedioDePago::where('nombre', MedioDePago::EFECTIVO)->value('id');
        return (float) $this->queryLibro($efectivoId)->where('signo_efectivo', -1)->sum('monto');
    }

    /**
     * Totales por medio de pago (todos los medios, agrupados).
     * Retorna colección de objetos con medio_id, nombre, entradas, salidas.
     */
    public function totalesPorMedio(): \Illuminate\Support\Collection
    {
        $ids = $this->libroIds();
        if (empty($ids)) {
            return collect();
        }

        return LibroDiario::whereIn('tes_libro_diario.id', $ids)
            ->whereNull('tes_libro_diario.deleted_at')
            ->join('tes_medio_de_pagos', 'tes_libro_diario.medio_id', '=', 'tes_medio_de_pagos.id')
            ->whereNull('tes_medio_de_pagos.deleted_at')
            ->selectRaw('
                tes_libro_diario.medio_id,
                tes_medio_de_pagos.nombre as medio_nombre,
                SUM(CASE WHEN tes_libro_diario.signo_efectivo = 1  THEN tes_libro_diario.monto ELSE 0 END) as entradas,
                SUM(CASE WHEN tes_libro_diario.signo_efectivo = -1 THEN tes_libro_diario.monto ELSE 0 END) as salidas
            ')
            ->groupBy('tes_libro_diario.medio_id', 'tes_medio_de_pagos.nombre')
            ->get();
    }

    // Compatibilidad con código existente que usa totalIngresosOtros / totalEgresosOtros
    public function totalIngresosOtros(): float
    {
        $efectivoId = MedioDePago::where('nombre', MedioDePago::EFECTIVO)->value('id');
        return (float) $this->queryLibro()
            ->where('medio_id', '!=', $efectivoId)
            ->where('signo_efectivo', 1)
            ->sum('monto');
    }

    public function totalEgresosOtros(): float
    {
        $efectivoId = MedioDePago::where('nombre', MedioDePago::EFECTIVO)->value('id');
        return (float) $this->queryLibro()
            ->where('medio_id', '!=', $efectivoId)
            ->where('signo_efectivo', -1)
            ->sum('monto');
    }

    public function cerrar(array $data = []): void
    {
        $this->update([
            'saldo_cierre' => $data['saldo_cierre'] ?? $this->obtenerSaldoActual(),
            'fecha_cierre' => now(),
            'estado' => 'cerrada',
            'observaciones' => $data['observaciones'] ?? $this->observaciones,
        ]);
    }

    /**
     * Obtiene el saldo de cierre de la caja anterior del mismo cajero
     * (día hábil anterior con caja cerrada).
     */
    public static function saldoCierreAnterior(int $cajeroId, ?string $fechaReferencia = null): ?float
    {
        $cajaAnterior = self::cerradas()
            ->porCajero($cajeroId)
            ->latest('id')
            ->first();

        return $cajaAnterior?->saldo_cierre;
    }
}