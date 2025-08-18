<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InfraccionTransito extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'infracciones_transito';

    protected $fillable = [
        'articulo',
        'apartado',
        'descripcion',
        'importe_ur',
        'decreto',
        'activo'
    ];

    protected $casts = [
        'importe_ur' => 'decimal:1',
        'activo' => 'boolean'
    ];

    protected $dates = ['deleted_at']; // <-- Especificar la columna deleted_at
    public $timestamps = true; // <-- Habilitar timestamps
    
    /**
     * Scope para infracciones activas
     */
    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Scope para buscar por artículo
     */
    public function scopePorArticulo($query, $articulo)
    {
        return $query->where('articulo', $articulo);
    }

    /**
     * Scope para buscar en descripción
     */
    public function scopeBuscarDescripcion($query, $termino)
    {
        return $query->where('descripcion', 'LIKE', '%' . $termino . '%');
    }

    /**
     * Accessor para mostrar artículo completo
     */
    public function getArticuloCompletoAttribute()
    {
        return $this->articulo . ($this->apartado ? '.' . $this->apartado : '');
    }

    /**
     * Accessor para formatear importe
     */
    public function getImporteFormateadoAttribute()
    {
        return number_format($this->importe_ur, 1, ',', '.') . ' UR';
    }
}
