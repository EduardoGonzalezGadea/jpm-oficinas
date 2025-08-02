<?php

namespace App\Models\Tesoreria\Valores;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ValorUso extends Model
{
    use HasFactory;

    protected $table = 'tes_val_usos';

    protected $fillable = [
        'conceptos_id',
        'desde',
        'hasta',
        'recibos_disponibles',
        'interno',
        'fecha_asignacion',
        'activo'
    ];

    protected $casts = [
        'desde' => 'integer',
        'hasta' => 'integer',
        'recibos_disponibles' => 'integer',
        'fecha_asignacion' => 'date',
        'activo' => 'boolean'
    ];

    // Relaciones
    public function concepto(): BelongsTo
    {
        return $this->belongsTo(ValorConcepto::class, 'conceptos_id');
    }

    // Scopes
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function scopePorConcepto($query, int $conceptoId)
    {
        return $query->where('conceptos_id', $conceptoId);
    }

    public function scopeConRecibosDisponibles($query)
    {
        return $query->where('recibos_disponibles', '>', 0);
    }

    public function scopeOrdenadoPorFecha($query, string $direccion = 'asc')
    {
        return $query->orderBy('fecha_asignacion', $direccion);
    }

    // Métodos auxiliares
    public function getRangoRecibosAttribute(): string
    {
        $primerDisponible = $this->hasta - $this->recibos_disponibles + 1;
        return $primerDisponible . ' - ' . $this->hasta;
    }

    public function getRangoOriginalAttribute(): string
    {
        return $this->desde . ' - ' . $this->hasta;
    }

    public function getRecibosUtilizadosAttribute(): int
    {
        return ($this->hasta - $this->desde + 1) - $this->recibos_disponibles;
    }

    public function getPorcentajeUsoAttribute(): float
    {
        $totalRecibos = $this->hasta - $this->desde + 1;
        if ($totalRecibos == 0) return 0;

        return round((($totalRecibos - $this->recibos_disponibles) / $totalRecibos) * 100, 2);
    }

    public function validarRangoRecibos(): bool
    {
        return $this->desde <= $this->hasta &&
            $this->recibos_disponibles >= 0 &&
            $this->recibos_disponibles <= ($this->hasta - $this->desde + 1);
    }

    public function usarRecibos(int $cantidad): bool
    {
        if ($cantidad <= 0 || $cantidad > $this->recibos_disponibles) {
            return false;
        }

        $this->recibos_disponibles -= $cantidad;

        if ($this->recibos_disponibles == 0) {
            $this->activo = false;
        }

        return $this->save();
    }

    public function marcarComoAgotado(): bool
    {
        $this->recibos_disponibles = 0;
        $this->activo = false;
        return $this->save();
    }

    public function getProximoReciboDisponible(): int
    {
        return $this->hasta - $this->recibos_disponibles + 1;
    }
}