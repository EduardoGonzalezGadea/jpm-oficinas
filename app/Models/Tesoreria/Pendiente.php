<?php

namespace App\Models\Tesoreria;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pendiente extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tes_cch_pendientes';
    protected $primaryKey = 'idPendientes';
    public $timestamps = true;

    protected $fillable = [
        'relCajaChica',
        'pendiente',
        'fechaPendientes',
        'relDependencia',
        'montoPendientes',
    ];

    protected $casts = [
        'fechaPendientes' => 'date:Y-m-d',
    ];

    protected $dates = ['deleted_at'];

    // Relaciones
    public function cajaChica()
    {
        return $this->belongsTo(CajaChica::class, 'relCajaChica', 'idCajaChica');
    }

    public function dependencia()
    {
        return $this->belongsTo(Dependencia::class, 'relDependencia', 'idDependencias');
    }

    public function movimientos()
    {
        return $this->hasMany(Movimiento::class, 'relPendiente', 'idPendientes');
    }
}