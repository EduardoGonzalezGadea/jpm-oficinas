<?php

namespace App\Models\Tesoreria;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Multa extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tes_multas';

    // --- REFACTORIZADO ---
    protected $fillable = [
        'articulo',
        'apartado',
        'descripcion',
        'moneda',
        'importe_original',
        'importe_unificado',
        'decreto',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'importe_original' => 'decimal:2',
        'importe_unificado' => 'decimal:2',
    ];
    // ---------------------

    protected $dates = ['deleted_at'];
    public $timestamps = true;

    public function scopePorArticulo($query, $articulo)
    {
        return $query->where('articulo', $articulo);
    }

    public function scopeBuscarDescripcion($query, $termino)
    {
        return $query->where('descripcion', 'LIKE', '%' . $termino . '%');
    }

    public function getArticuloCompletoAttribute()
    {
        return $this->articulo . ($this->apartado ? '.' . $this->apartado : '');
    }

    // --- NUEVOS ACCESSORS ---
    /**
     * Accessor para formatear importe original
     */
    public function getImporteOriginalFormateadoAttribute()
    {
        return number_format($this->importe_original, 2, ',', '.') . '&nbsp;' . $this->moneda;
    }

    /**
     * Accessor para formatear importe unificado
     */
    public function getImporteUnificadoFormateadoAttribute()
    {
        if (is_null($this->importe_unificado)) {
            return '';
        }
        return number_format($this->importe_unificado, 2, ',', '.') . '&nbsp;' . $this->moneda;
    }
    // -------------------------
}
