<?php

namespace App\Models\Tesoreria;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EntregaLibretaValor extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $table = 'tes_entregas_libretas_valores';

    protected $fillable = [
        'libreta_valor_id',
        'servicio_id',
        'numero_recibo_entrega',
        'fecha_entrega',
        'observaciones',
        'estado',
    ];

    protected $casts = [
        'fecha_entrega' => 'date',
    ];

    public function libretaValor()
    {
        return $this->belongsTo(LibretaValor::class, 'libreta_valor_id');
    }

    public function servicio()
    {
        return $this->belongsTo(Servicio::class, 'servicio_id');
    }
}
