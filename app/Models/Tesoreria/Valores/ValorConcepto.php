<?php

namespace App\Models\Tesoreria\Valores;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ValorConcepto extends Model
{
    use HasFactory;

    protected $table = 'tes_val_conceptos';

    protected $fillable = [
        'valores_id',
        'concepto',
        'monto',
        'tipo_monto',
        'descripcion',
        'activo'
    ];

    protected $casts = [
        'monto' => 'decimal:2',
        'activo' => 'boolean'
    ];

    // Relaciones
    public function valor(): BelongsTo
    {
        return $this->belongsTo(Valor::class, 'valores_id');
    }

    public function salidas(): HasMany
    {
        return $this->hasMany(ValorSalida::class, 'conceptos_id');
    }

    public function usos(): HasMany
    {
        return $this->hasMany(ValorUso::class, 'conceptos_id');
    }

    public function usosActivos(): HasMany
    {
        return $this->hasMany(ValorUso::class, 'conceptos_id')->where('activo', true);
    }

    // Scopes
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function scopePorValor($query, int $valorId)
    {
        return $query->where('valores_id', $valorId);
    }

    // Métodos auxiliares
    public function getTipoMontoTextoAttribute(): string
    {
        return match ($this->tipo_monto) {
            'pesos' => 'Pesos',
            'UR' => 'Unidad Reajustable',
            'porcentaje' => 'Porcentaje',
            default => $this->tipo_monto
        };
    }

    public function getRecibosDisponibles(): int
    {
        return $this->usosActivos()->sum('recibos_disponibles');
    }

    public function getTotalRecibosAsignados(): int
    {
        return $this->salidas()->sum('total_recibos');
    }

    public function getResumenUso(): array
    {
        $usosActivos = $this->usosActivos()->get();
        $totalDisponibles = $usosActivos->sum('recibos_disponibles');
        $totalAsignados = $this->getTotalRecibosAsignados();

        return [
            'total_asignados' => $totalAsignados,
            'total_disponibles' => $totalDisponibles,
            'total_utilizados' => $totalAsignados - $totalDisponibles,
            'libretas_en_uso' => $usosActivos->count(),
            'detalle_libretas' => $usosActivos->map(function ($uso) {
                return [
                    'desde' => $uso->desde,
                    'hasta' => $uso->hasta,
                    'disponibles' => $uso->recibos_disponibles,
                    'interno' => $uso->interno
                ];
            })->toArray()
        ];
    }
}