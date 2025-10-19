<?php

namespace App\Models\Tesoreria\CajaDiaria;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TesCdCierre extends Model
{
    use HasFactory;

    protected $table = 'tes_cd_cierres';

    protected $fillable = [
        'tes_caja_diarias_id',
        'monto_cierre',
    ];

    public function denominaciones()
    {
        return $this->hasMany(TesCdCierreDenominacion::class, 'tes_cd_cierres_id');
    }
}
