<?php

namespace App\Models\Tesoreria\Cajas;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Concepto extends Model
{
    use HasFactory;

    protected $table = 'tes_caja_conceptos';
    protected $primaryKey = 'idConcepto';

    protected $fillable = [
        'nombre',
        'tipo',
        'descripcion',
        'activo',
    ];
}
