<?php

namespace App\Models\Tesoreria;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PlanillaCheque extends Model
{
    use SoftDeletes;

    protected $table = 'tes_planillas_cheques';
    protected $fillable = [
        'numero_planilla', 'fecha_generacion', 'estado',
        'motivo_anulacion', 'fecha_anulacion', 'generada_por', 'anulada_por', 'created_by', 'updated_by'
    ];

    protected $dates = ['fecha_generacion', 'fecha_anulacion'];

    public function cheques()
    {
        return $this->hasMany(Cheque::class, 'planilla_id');
    }

    public function anulacion()
    {
        return $this->morphOne(Anulacion::class, 'anulable');
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(fn($m) => $m->created_by = auth()->id());
        static::updating(fn($m) => $m->updated_by = auth()->id());
    }
}
