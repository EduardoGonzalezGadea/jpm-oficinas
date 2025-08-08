<?php

namespace App\Models\Tesoreria\Cajas;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Caja extends Model
{
    use SoftDeletes;

    protected $table = 'tes_cajas';
    protected $primaryKey = 'idCaja';

    protected $fillable = [
        'fecha_apertura',
        'hora_apertura',
        'saldo_inicial',
        'fecha_cierre',
        'hora_cierre',
        'saldo_final',
        'estado', // ABIERTA, CERRADA
        'usuario_apertura',
        'usuario_cierre',
        'observaciones'
    ];

    protected $casts = [
        'fecha_apertura' => 'date',
        'fecha_cierre' => 'date',
        'saldo_inicial' => 'decimal:2',
        'saldo_final' => 'decimal:2'
    ];

    public function movimientos()
    {
        return $this->hasMany(MovimientoCaja::class, 'relCaja', 'idCaja');
    }

    public function usuarioApertura()
    {
        return $this->belongsTo(User::class, 'usuario_apertura');
    }

    public function usuarioCierre()
    {
        return $this->belongsTo(User::class, 'usuario_cierre');
    }

    public function obtenerSaldoActual()
    {
        $ingresos = $this->movimientos()
            ->where('tipo_movimiento', 'INGRESO')
            ->sum('monto');

        $egresos = $this->movimientos()
            ->where('tipo_movimiento', 'EGRESO')
            ->sum('monto');

        return $this->saldo_inicial + $ingresos - $egresos;
    }

    public static function obtenerCajaAbierta()
    {
        return static::where('estado', 'ABIERTA')->first();
    }
}
