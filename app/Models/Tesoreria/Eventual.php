<?php

namespace App\Models\Tesoreria;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsActivityTrait;

class Eventual extends Model
{
    use HasFactory, SoftDeletes, LogsActivityTrait;

    protected $table = 'tes_eventuales';

    protected $fillable = [
        'fecha',
        'ingreso',
        'institucion',
        'titular',
        'monto',
        'medio_de_pago',
        'detalle',
        'orden_cobro',
        'recibo',
        'confirmado',
        'planilla_id'
    ];

    protected $dates = ['fecha'];

    public function planilla()
    {
        return $this->belongsTo(EventualPlanilla::class, 'planilla_id');
    }

    public function getMontoFormateadoAttribute()
    {
        return '$ ' . number_format($this->monto, 2, ',', '.');
    }

    public function scopeSearch($query, $search)
    {
        if ($search) {
            return $query->where('fecha', 'like', '%' . $search . '%')
                ->orWhere('ingreso', 'like', '%' . $search . '%')
                ->orWhere('institucion', 'like', '%' . $search . '%')
                ->orWhere('titular', 'like', '%' . $search . '%')
                ->orWhere('monto', 'like', '%' . $search . '%')
                ->orWhere('orden_cobro', 'like', '%' . $search . '%')
                ->orWhere('recibo', 'like', '%' . $search . '%');
        }
    }

    public function scopeConfirmedAndNotInPlanilla($query)
    {
        return $query->where('confirmado', true)->whereNull('planilla_id');
    }
}
