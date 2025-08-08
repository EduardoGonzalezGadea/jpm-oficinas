<?php

namespace App\Models\Tesoreria\Cajas;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;

class MovimientoCaja extends Model
{
    use SoftDeletes;

    protected $table = 'tes_caja_movimientos';
    protected $primaryKey = 'idMovimiento';

    protected $fillable = [
        'relCaja',
        'fecha',
        'hora',
        'tipo_movimiento', // INGRESO, EGRESO
        'concepto',
        'monto',
        'forma_pago', // EFECTIVO, TRANSFERENCIA, CHEQUE
        'referencia',
        'usuario_registro'
    ];

    protected $casts = [
        'fecha' => 'date',
        'monto' => 'decimal:2'
    ];

    public function caja()
    {
        return $this->belongsTo(Caja::class, 'relCaja', 'idCaja');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_registro');
    }
}
