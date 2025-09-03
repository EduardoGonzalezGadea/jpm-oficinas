<?php

namespace App\Models\Tesoreria;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Tesoreria\Planilla;

class Arrendamiento extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tes_arrendamientos';

    public $timestamps = true;

    protected $fillable = [
        'fecha',
        'ingreso',
        'nombre',
        'cedula',
        'telefono',
        'monto',
        'detalle',
        'orden_cobro',
        'recibo',
        'medio_de_pago',
        'confirmado',
        'planilla_id',
    ];

    protected $casts = [
        'fecha' => 'date',
        'monto' => 'decimal:2',
        'confirmado' => 'boolean',
    ];

    protected $dates = ['deleted_at'];

    public function planilla()
    {
        return $this->belongsTo(Planilla::class, 'planilla_id');
    }

    /**
     * Scope for searching
     */
    public function scopeSearch($query, $term)
    {
        return $query->where(function ($query) use ($term) {
            $query->where('ingreso', 'like', '%' . $term . '%')
                ->orWhere('monto', 'like', '%' . $term . '%')
                ->orWhere('orden_cobro', 'like', '%' . $term . '%')
                ->orWhere('recibo', 'like', '%' . $term . '%');
        });
    }

    /**
     * Scope for confirmed and not in a planilla
     */
    public function scopeConfirmedAndNotInPlanilla($query)
    {
        return $query->where('confirmado', true)
                     ->whereNull('planilla_id');
    }

    /**
     * Accessor for formatted amount
     */
    public function getMontoFormateadoAttribute()
    {
        return '$ ' . number_format($this->monto, 2, ',', '.');
    }
};
