<?php

namespace App\Models\Tesoreria;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Tesoreria\Planilla;
use App\Traits\LogsActivityTrait;

class Arrendamiento extends Model
{
    use HasFactory, SoftDeletes, Auditable, LogsActivityTrait;

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
        'medio_pago_id',
        'confirmado',
        'planilla_id'
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

    public function medioPago()
    {
        return $this->belongsTo(MedioDePago::class, 'medio_pago_id');
    }

    public function scopeSearch($query, $term)
    {
        return $query->where(function ($query) use ($term) {
            $query->where('ingreso', 'like', '%' . $term . '%')
                ->orWhere('nombre', 'like', '%' . $term . '%')
                ->orWhere('cedula', 'like', '%' . $term . '%')
                ->orWhere('monto', 'like', '%' . $term . '%')
                ->orWhere('orden_cobro', 'like', '%' . $term . '%')
                ->orWhere('recibo', 'like', '%' . $term . '%');
        });
    }

    public function scopeConfirmedAndNotInPlanilla($query)
    {
        return $query->where('confirmado', true)
            ->whereNull('planilla_id');
    }

    public function getMontoFormateadoAttribute()
    {
        return '$ ' . number_format($this->monto, 2, ',', '.');
    }
}
