<?php

namespace App\Models\Tesoreria\Cajas;

use Illuminate\Database\Eloquent\Model;

class DesgloseEfectivo extends Model
{
    protected $table = 'tes_desglose_efectivo';
    protected $primaryKey = 'idDesglose';

    protected $fillable = [
        'relArqueo',
        'relDenominacion',
        'cantidad',
        'subtotal'
    ];

    public $timestamps = true;

    // Relaciones
    public function arqueo()
    {
        return $this->belongsTo(ArqueoCaja::class, 'relArqueo', 'idArqueo');
    }

    public function denominacion()
    {
        return $this->belongsTo(Denominacion::class, 'relDenominacion', 'idDenominacion');
    }
}
