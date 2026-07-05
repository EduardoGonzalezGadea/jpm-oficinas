<?php

namespace App\Models\Tesoreria;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsActivityTrait;

class Multa extends Model
{
    use HasFactory, SoftDeletes, Auditable, LogsActivityTrait;

    protected $table = 'tes_multas';

    protected $fillable = [
        'codigo',
        'articulo',
        'literal',
        'apartado',
        'articulo_completo',
        'descripcion',
        'moneda',
        'importe_original',
        'importe_unificado',
        'decreto',
        'monto_ur',
        'monto_ui',
        'monto_pesos',
        'inciso_legal',
        'visible',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'importe_original' => 'decimal:2',
        'importe_unificado' => 'decimal:2',
        'monto_ur' => 'decimal:4',
        'monto_ui' => 'decimal:4',
        'monto_pesos' => 'decimal:2',
        'visible' => 'boolean',
    ];

    protected $dates = ['deleted_at'];
    public $timestamps = true;

    /**
     * Boot method para actualizar articulo_completo automáticamente
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            // Actualizar articulo_completo antes de guardar
            // Formato: "103.2A" si hay apartado, "103" si no hay
            $model->articulo_completo = $model->articulo . ($model->apartado ? '.' . $model->apartado : '');
        });
    }

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
        $valor = number_format($this->importe_original, 2, ',', '.');
        return ($this->moneda === 'UYU') ? '$&nbsp;' . $valor : $valor . '&nbsp;' . $this->moneda;
    }

    /**
     * Accessor para formatear importe unificado
     */
    public function getImporteUnificadoFormateadoAttribute()
    {
        if (is_null($this->importe_unificado)) {
            return '';
        }
        $valor = number_format($this->importe_unificado, 2, ',', '.');
        return ($this->moneda === 'UYU') ? '$&nbsp;' . $valor : $valor . '&nbsp;' . $this->moneda;
    }
    // -------------------------
}
