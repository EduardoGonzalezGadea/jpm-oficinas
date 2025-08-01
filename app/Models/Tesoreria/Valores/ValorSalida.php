<?php

namespace App\Models\Tesoreria\Valores;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ValorSalida extends Model
{
    use HasFactory;

    protected $table = 'tes_val_salidas';

    protected $fillable = [
        'valores_id',
        'conceptos_id',
        'fecha',
        'comprobante',
        'desde',
        'hasta',
        'interno',
        'cantidad_libretas',
        'total_recibos',
        'responsable',
        'observaciones'
    ];

    protected $casts = [
        'fecha' => 'date',
        'desde' => 'integer',
        'hasta' => 'integer',
        'cantidad_libretas' => 'integer',
        'total_recibos' => 'integer'
    ];

    // Relaciones
    public function valor(): BelongsTo
    {
        return $this->belongsTo(Valor::class, 'valores_id');
    }

    public function concepto(): BelongsTo
    {
        return $this->belongsTo(ValorConcepto::class, 'conceptos_id');
    }

    // Scopes
    public function scopePorValor($query, int $valorId)
    {
        return $query->where('valores_id', $valorId);
    }

    public function scopePorConcepto($query, int $conceptoId)
    {
        return $query->where('conceptos_id', $conceptoId);
    }

    public function scopeEntreFechas($query, $fechaInicio, $fechaFin)
    {
        return $query->whereBetween('fecha', [$fechaInicio, $fechaFin]);
    }

    public function scopeOrdenadoPorFecha($query, string $direccion = 'desc')
    {
        return $query->orderBy('fecha', $direccion);
    }

    // Métodos auxiliares
    public function getRangoRecibosAttribute(): string
    {
        return $this->desde . ' - ' . $this->hasta;
    }

    public function validarRangoRecibos(): bool
    {
        return $this->desde <= $this->hasta;
    }

    public function calcularTotalRecibos(): int
    {
        if (!$this->validarRangoRecibos()) {
            return 0;
        }

        return ($this->hasta - $this->desde) + 1;
    }

    public function calcularCantidadLibretas(): int
    {
        if (!$this->valor || !$this->validarRangoRecibos()) {
            return 0;
        }

        $totalRecibos = $this->calcularTotalRecibos();
        return intval($totalRecibos / $this->valor->recibos);
    }

    // Eventos del modelo
    protected static function booted()
    {
        static::saving(function ($salida) {
            if (!$salida->total_recibos) {
                $salida->total_recibos = $salida->calcularTotalRecibos();
            }

            if (!$salida->cantidad_libretas) {
                $salida->cantidad_libretas = $salida->calcularCantidadLibretas();
            }
        });

        static::created(function ($salida) {
            // Crear registro de uso automáticamente
            ValorUso::create([
                'conceptos_id' => $salida->conceptos_id,
                'desde' => $salida->desde,
                'hasta' => $salida->hasta,
                'recibos_disponibles' => $salida->total_recibos,
                'interno' => $salida->interno,
                'fecha_asignacion' => $salida->fecha,
                'activo' => true
            ]);
        });
    }
}