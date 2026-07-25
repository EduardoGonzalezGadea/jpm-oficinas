<?php

namespace App\Models\Tesoreria;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsActivityTrait;

class TesMultasCobradas extends Model
{
    use HasFactory, SoftDeletes, Auditable, LogsActivityTrait;

    protected $table = 'tes_multas_cobradas';

    protected $fillable = [
        'recibo',
        'cedula',
        'nombre',
        'domicilio',
        'adicional',
        'fecha',
        'monto',
        'forma_pago',
        'medio_pago_id',
        'referencias',
        'adenda',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = ['fecha' => 'datetime'];

    public function items()
    {
        return $this->hasMany(TesMultasItems::class, 'tes_multas_cobradas_id');
    }

    public function medioPago()
    {
        return $this->belongsTo(MedioDePago::class, 'medio_pago_id');
    }

    public function mediosPago()
    {
        return $this->hasMany(TesMultaMedioPago::class, 'multa_id');
    }

    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function getMontoFormateadoAttribute()
    {
        return '$' . "\u{00A0}" . number_format($this->monto, 2, ',', '.');
    }
}
