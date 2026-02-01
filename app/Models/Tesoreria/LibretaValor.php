<?php

namespace App\Models\Tesoreria;

use App\Traits\Auditable;
use App\Traits\LogsActivityTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LibretaValor extends Model
{
    use HasFactory, SoftDeletes, Auditable, LogsActivityTrait;

    protected $table = 'tes_libretas_valores';

    protected $fillable = [
        'tipo_libreta_id',
        'serie',
        'numero_inicial',
        'numero_final',
        'fecha_recepcion',
        'estado',
        'proximo_recibo_disponible',
        'servicio_asignado_id',
    ];

    protected $casts = [
        'fecha_recepcion' => 'date',
    ];

    public function tipoLibreta()
    {
        return $this->belongsTo(TipoLibreta::class, 'tipo_libreta_id');
    }

    public function servicioAsignado()
    {
        return $this->belongsTo(Servicio::class, 'servicio_asignado_id');
    }

    public function entregas()
    {
        return $this->hasMany(EntregaLibretaValor::class, 'libreta_valor_id');
    }

    public function entregaActiva()
    {
        return $this->hasOne(EntregaLibretaValor::class, 'libreta_valor_id')->where('estado', 'activo');
    }
}
