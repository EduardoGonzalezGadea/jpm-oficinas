<?php

namespace App\Models\Tesoreria;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes; // <-- Importado

class Dependencia extends Model
{
    use HasFactory, SoftDeletes; // <-- Agregado SoftDeletes

    protected $table = 'tes_cch_dependencias';
    protected $primaryKey = 'idDependencias';
    public $timestamps = true; // <-- Cambiado a true

    protected $fillable = [
        'dependencia',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $dates = ['deleted_at']; // <-- Especificar la columna deleted_at

    // Relaciones
    public function pendientes()
    {
        return $this->hasMany(Pendiente::class, 'relDependencia', 'idDependencias');
    }
}
