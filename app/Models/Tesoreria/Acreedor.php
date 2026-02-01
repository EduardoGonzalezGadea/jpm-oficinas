<?php

namespace App\Models\Tesoreria;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes; // <-- Importado
use App\Traits\LogsActivityTrait;

class Acreedor extends Model
{
    use HasFactory, SoftDeletes, LogsActivityTrait; // <-- Agregado SoftDeletes

    protected $table = 'tes_cch_acreedores';
    protected $primaryKey = 'idAcreedores';
    public $timestamps = true; // <-- Cambiado a true

    protected $fillable = [
        'acreedor',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $dates = ['deleted_at']; // <-- Especificar la columna deleted_at

    // Relaciones
    public function pagos()
    {
        return $this->hasMany(Pago::class, 'relAcreedores', 'idAcreedores');
    }
}
