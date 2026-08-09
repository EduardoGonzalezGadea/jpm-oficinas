<?php

namespace App\Models\Tesoreria\Cajas;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class CajaArqueo extends Model
{
    protected $table = 'tes_cajas_arqueos';

    protected $fillable = [
        'caja_apertura_id', 'total_efectivo', 'total_transferencias',
        'total_cheques', 'diferencia', 'observaciones', 'usuario_id',
    ];

    public function cajaApertura()
    {
        return $this->belongsTo(CajaApertura::class, 'caja_apertura_id');
    }

    public function desgloses()
    {
        return $this->hasMany(CajaDesglose::class, 'arqueo_id');
    }

    public function usuarioRegistro()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}