<?php

namespace App\Models\Tesoreria;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsActivityTrait;

class TesMultasCobradas extends Model
{
    use HasFactory, SoftDeletes, LogsActivityTrait;

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
        'referencias',
        'adenda',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $dates = ['fecha'];

    public function items()
    {
        return $this->hasMany(TesMultasItems::class, 'tes_multas_cobradas_id');
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
