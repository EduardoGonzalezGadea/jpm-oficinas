<?php

namespace App\Models\Tesoreria\Valores;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Valor extends Model
{
    use HasFactory;

    protected $table = 'tes_valores';

    protected $fillable = [
        'nombre',
        'recibos',
        'tipo_valor',
        'valor',
        'descripcion',
        'activo'
    ];

    protected $casts = [
        'valor' => 'decimal:2',
        'activo' => 'boolean',
        'recibos' => 'integer'
    ];

    // Relaciones
    public function conceptos(): HasMany
    {
        return $this->hasMany(ValorConcepto::class, 'valores_id');
    }

    public function conceptosActivos(): HasMany
    {
        return $this->hasMany(ValorConcepto::class, 'valores_id')->where('activo', true);
    }

    public function entradas(): HasMany
    {
        return $this->hasMany(ValorEntrada::class, 'valores_id');
    }

    public function salidas(): HasMany
    {
        return $this->hasMany(ValorSalida::class, 'valores_id');
    }

    // Scopes
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function scopePorTipo($query, string $tipo)
    {
        return $query->where('tipo_valor', $tipo);
    }

    // Métodos auxiliares
    public function getTipoValorTextoAttribute(): string
    {
        return match ($this->tipo_valor) {
            'pesos' => 'Pesos',
            'UR' => 'Unidad Reajustable',
            'SVE' => 'Sin Valor Escrito',
            default => $this->tipo_valor
        };
    }

    public function getStockDisponible(): int
    {
        $entradas = $this->entradas()->sum('total_recibos');
        $salidas = $this->salidas()->sum('total_recibos');

        return $entradas - $salidas;
    }

    public function getLibretasCompletas(): int
    {
        $stockDisponible = $this->getStockDisponible();
        $recibosEnUso = $this->getRecibosEnUso();

        $stockLibretasCompletas = $stockDisponible - $recibosEnUso;

        return intval($stockLibretasCompletas / $this->recibos);
    }

    public function getRecibosEnUso(): int
    {
        return $this->conceptos()
            ->join('tes_val_uso', 'tes_val_conceptos.id', '=', 'tes_val_uso.conceptos_id')
            ->where('tes_val_uso.activo', true)
            ->sum('tes_val_uso.recibos_disponibles');
    }

    public function getResumenStock(): array
    {
        $stockTotal = $this->getStockDisponible();
        $recibosEnUso = $this->getRecibosEnUso();
        $libretasCompletas = $this->getLibretasCompletas();
        $recibosLibretasCompletas = $libretasCompletas * $this->recibos;
        $recibosDisponibles = $stockTotal - $recibosEnUso;

        return [
            'stock_total' => $stockTotal,
            'libretas_completas' => $libretasCompletas,
            'recibos_libretas_completas' => $recibosLibretasCompletas,
            'recibos_en_uso' => $recibosEnUso,
            'recibos_disponibles' => $recibosDisponibles
        ];
    }
}