<?php

namespace App\Models\Tesoreria;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TesMultaMedioPago extends Model
{
    use HasFactory;

    protected $table = 'tes_multa_medios_pago';

    protected $fillable = [
        'multa_id',
        'medio_pago_id',
        'monto',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
    ];

    public function multa()
    {
        return $this->belongsTo(TesMultasCobradas::class, 'multa_id');
    }

    public function medioPago()
    {
        return $this->belongsTo(MedioDePago::class, 'medio_pago_id');
    }
}
