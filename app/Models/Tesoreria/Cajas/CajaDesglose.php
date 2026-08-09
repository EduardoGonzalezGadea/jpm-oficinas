<?php

namespace App\Models\Tesoreria\Cajas;

use App\Models\Tesoreria\TesDiscriminacionMonetaria;
use Illuminate\Database\Eloquent\Model;

class CajaDesglose extends Model
{
    protected $table = 'tes_cajas_desgloses';

    protected $fillable = [
        'caja_apertura_id', 'arqueo_id', 'tes_discriminacion_monetaria_id',
        'cantidad', 'subtotal', 'tipo_referencia',
    ];

    public function cajaApertura()
    {
        return $this->belongsTo(CajaApertura::class, 'caja_apertura_id');
    }

    public function arqueo()
    {
        return $this->belongsTo(CajaArqueo::class, 'arqueo_id');
    }

    public function discriminacion()
    {
        return $this->belongsTo(TesDiscriminacionMonetaria::class, 'tes_discriminacion_monetaria_id');
    }
}