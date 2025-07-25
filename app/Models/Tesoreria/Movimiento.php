<?php

namespace App\Models\Tesoreria;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes; // <-- Importado

class Movimiento extends Model
{
    use HasFactory, SoftDeletes; // <-- Agregado SoftDeletes

    protected $table = 'tes_cch_movimientos';
    protected $primaryKey = 'idMovimientos';
    public $timestamps = true; // <-- Cambiado a true

    protected $fillable = [
        'relPendiente',
        'fechaMovimientos',
        'documentos',
        'rendido',
        'reintegrado',
        'recuperado',
    ];

    protected $casts = [
        'fechaMovimientos' => 'date',
    ];

    protected $dates = ['deleted_at']; // <-- Especificar la columna deleted_at

    // Relaciones
    public function pendiente()
    {
        return $this->belongsTo(Pendiente::class, 'relPendiente', 'idPendientes');
    }
}
