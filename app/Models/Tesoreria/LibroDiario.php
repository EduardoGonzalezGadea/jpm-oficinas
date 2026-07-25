<?php

namespace App\Models\Tesoreria;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Traits\LogsActivityTrait;

class LibroDiario extends Model
{
    use HasFactory, SoftDeletes, Auditable, LogsActivityTrait;

    protected $table = 'tes_libro_diario';

    protected $fillable = [
        'fecha',
        'tipo_id',
        'numero',
        'signo_efectivo',
        'identidad',
        'denominacion',
        'descripcion',
        'concepto_id',
        'detalle_id',
        'medio_id',
        'monto',
        'saldo',
        'asociar',
        'grupo_redistribucion_id',
        'cfe_id',
        'es_contra_asiento',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'fecha' => 'date',
        'monto' => 'decimal:2',
        'saldo' => 'decimal:2',
        'signo_efectivo' => 'integer',
        'numero' => 'integer',
        'es_contra_asiento' => 'boolean',
    ];

    protected $editableCampos = ['identidad', 'denominacion', 'descripcion'];

    public static function getEditableCampos(): array
    {
        return (new static)->editableCampos;
    }

    public function tipo()
    {
        return $this->belongsTo(LbTipo::class, 'tipo_id');
    }

    public function concepto()
    {
        return $this->belongsTo(LbConcepto::class, 'concepto_id');
    }

    public function detalle()
    {
        return $this->belongsTo(LbDetalle::class, 'detalle_id');
    }

    public function medio()
    {
        return $this->belongsTo(MedioDePago::class, 'medio_id');
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'asociar');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'asociar');
    }

    public function parRedistribucion()
    {
        return $this->hasMany(self::class, 'grupo_redistribucion_id', 'grupo_redistribucion_id');
    }

    public function getEsRedistribucionAttribute(): bool
    {
        return $this->grupo_redistribucion_id !== null;
    }

    public function cfe(): BelongsTo
    {
        return $this->belongsTo(TesCfe::class, 'cfe_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'updated_by');
    }

    public function deletedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'deleted_by');
    }

    public function scopeDelAnio($query, $anio)
    {
        return $query->whereYear('fecha', $anio);
    }

    public function scopeEntreFechas($query, $desde, $hasta)
    {
        return $query->whereBetween('fecha', [$desde, $hasta]);
    }

    public function scopePorTipo($query, $tipoId)
    {
        return $query->where('tipo_id', $tipoId);
    }

    public function scopeOrdenado($query)
    {
        return $query->orderBy('fecha')->orderBy('numero');
    }

    public function scopeEsRedistribucion($query)
    {
        return $query->whereNotNull('grupo_redistribucion_id');
    }

    public static function generarGrupoRedistribucionId(): int
    {
        return (self::max('grupo_redistribucion_id') ?? 0) + 1;
    }

    public static function generarNumero($anio, $signoOsequence): int
    {
        $query = self::whereYear('fecha', $anio);
        $query->where('signo_efectivo', $signoOsequence);

        $max = $query->max('numero');
        return ($max ?? 0) + 1;
    }

    public static function ultimoSaldoSubcuenta($medioId, $conceptoId, $detalleId, $fecha, $numero = null)
    {
        return self::where('medio_id', $medioId)
            ->where('concepto_id', $conceptoId)
            ->where('detalle_id', $detalleId)
            ->where('fecha', '<=', $fecha)
            ->orderBy('fecha', 'desc')
            ->orderBy('id', 'desc')
            ->value('saldo') ?? 0;
    }
}
