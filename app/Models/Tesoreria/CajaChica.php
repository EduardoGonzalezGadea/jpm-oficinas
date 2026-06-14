<?php

namespace App\Models\Tesoreria;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsActivityTrait;

class CajaChica extends Model
{
    use HasFactory, SoftDeletes, Auditable, LogsActivityTrait;

    protected $table = 'tes_caja_chica';
    protected $primaryKey = 'idCajaChica';
    public $timestamps = true; // <-- Cambiado a true para usar timestamps

    protected $fillable = [
        'mes',
        'anio',
        'montoCajaChica',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $dates = ['deleted_at']; // <-- Especificar la columna deleted_at si no sigue la convención

    // Relaciones (excluyendo registros eliminados por defecto)
    public function pendientes()
    {
        return $this->hasMany(Pendiente::class, 'relCajaChica', 'idCajaChica');
    }

    public function pagos()
    {
        return $this->hasMany(Pago::class, 'relCajaChica_Pagos', 'idCajaChica');
    }
}
