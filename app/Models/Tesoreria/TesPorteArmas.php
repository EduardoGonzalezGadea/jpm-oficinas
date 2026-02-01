<?php

namespace App\Models\Tesoreria;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsActivityTrait;

class TesPorteArmas extends Model
{
    use HasFactory, SoftDeletes, LogsActivityTrait;

    protected $table = 'tes_porte_armas';

    protected $fillable = [
        'fecha',
        'orden_cobro',
        'numero_tramite',
        'ingreso_contabilidad',
        'recibo',
        'monto',
        'titular',
        'cedula',
        'telefono',
        'telefono',
        'planilla_id',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $dates = ['fecha'];

    public function getMontoFormateadoAttribute()
    {
        return '$ ' . number_format($this->monto, 2, ',', '.');
    }

    public function planilla()
    {
        return $this->belongsTo(TesPorteArmasPlanilla::class, 'planilla_id');
    }
}
