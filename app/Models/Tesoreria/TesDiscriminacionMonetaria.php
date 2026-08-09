<?php

namespace App\Models\Tesoreria;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\LogsActivityTrait;

class TesDiscriminacionMonetaria extends Model
{
    use HasFactory, SoftDeletes, LogsActivityTrait;

    protected $table = 'tes_discriminaciones_monetarias';

    protected $fillable = [
        'tipo',
        'valor',
        'texto',
        'activo',
        'created_by',
        'updated_by',
        'deleted_by'
    ];

    protected $casts = [
        'activo' => 'boolean',
        'valor' => 'decimal:2'
    ];

    // Relationships
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function deleter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    // Scope para obtener solo discriminaciones activas
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    // Scope para obtener solo billetes
    public function scopeBilletes($query)
    {
        return $query->where('tipo', 'Billetes');
    }

    // Scope para obtener solo monedas
    public function scopeMonedas($query)
    {
        return $query->where('tipo', 'Monedas');
    }

    // Scope para ordenar por tipo y valor descendente
    public function scopeOrdenado($query)
    {
        return $query->orderBy('tipo', 'asc')->orderBy('valor', 'desc');
    }

    // Scope para búsqueda
    public function scopeSearch($query, $term)
    {
        if (empty($term)) {
            return $query;
        }

        return $query->where(function ($query) use ($term) {
            $query->where('tipo', 'like', '%' . $term . '%')
                ->orWhere('texto', 'like', '%' . $term . '%')
                ->orWhere('valor', 'like', '%' . $term . '%');
        });
    }

    // Boot method to handle user tracking
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (auth()->check()) {
                $model->created_by = auth()->id();
            }
        });

        static::updating(function ($model) {
            if (auth()->check()) {
                $model->updated_by = auth()->id();
            }
        });

        static::deleting(function ($model) {
            if (auth()->check()) {
                $model->deleted_by = auth()->id();
                $model->save();
            }
        });
    }

    // Accessor para mostrar el valor formateado
    public function getValorFormateadoAttribute()
    {
        return number_format($this->valor, 2, ',', '.');
    }

    // Accessor para mostrar descripción completa
    public function getDescripcionCompletaAttribute()
    {
        return "{$this->tipo} de {$this->valor_formateado} ({$this->texto})";
    }
}
