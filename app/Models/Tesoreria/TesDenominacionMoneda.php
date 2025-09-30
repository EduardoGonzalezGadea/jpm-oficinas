<?php

namespace App\Models\Tesoreria;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TesDenominacionMoneda extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tes_denominaciones_monedas';

    protected $fillable = [
        'tipo_moneda',
        'denominacion',
        'descripcion',
        'activo',
        'created_by',
        'updated_by',
        'deleted_by'
    ];

    protected $casts = [
        'activo' => 'boolean',
        'denominacion' => 'decimal:2'
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

    // Scope para obtener solo denominaciones activas
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    // Scope para ordenar por tipo y valor
    public function scopeOrdenado($query)
    {
        return $query->orderBy('tipo_moneda')->orderBy('valor', 'desc');
    }

    // Scope para filtrar por tipo de moneda
    public function scopePorTipo($query, $tipo)
    {
        return $query->where('tipo_moneda', $tipo);
    }

    // Scope para búsqueda
    public function scopeSearch($query, $term)
    {
        if (empty($term)) {
            return $query;
        }

        return $query->where(function ($query) use ($term) {
            $query->where('tipo_moneda', 'like', '%' . $term . '%')
                ->orWhere('descripcion', 'like', '%' . $term . '%')
                ->orWhere('denominacion', 'like', '%' . $term . '%');
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

    // Accessor para mostrar la denominación formateada
    public function getDenominacionFormateadaAttribute()
    {
        return '$' . number_format($this->denominacion, 0, ',', '.');
    }

    // Accessor para mostrar el tipo con la primera letra en mayúscula
    public function getTipoMonedaFormateadoAttribute()
    {
        return ucfirst(strtolower($this->tipo_moneda));
    }
}
