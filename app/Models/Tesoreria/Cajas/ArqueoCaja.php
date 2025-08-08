<?php

namespace App\Models\Tesoreria\Cajas;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;

class ArqueoCaja extends Model
{
    use SoftDeletes;

    protected $table = 'tes_caja_arqueos';
    protected $primaryKey = 'idArqueo';

    protected $fillable = [
        'relCaja',
        'fecha',
        'hora',
        'total_efectivo',
        'total_transferencias',
        'total_cheques',
        'diferencia',
        'desglose',
        'observaciones',
        'usuario_registro'
    ];

    protected $casts = [
        'fecha' => 'date',
        'total_efectivo' => 'decimal:2',
        'total_transferencias' => 'decimal:2',
        'total_cheques' => 'decimal:2',
        'diferencia' => 'decimal:2',
        'desglose' => 'array'
    ];

    public function caja()
    {
        return $this->belongsTo(Caja::class, 'relCaja', 'idCaja');
    }

    public function usuarioRegistro()
    {
        return $this->belongsTo(User::class, 'usuario_registro');
    }
}
