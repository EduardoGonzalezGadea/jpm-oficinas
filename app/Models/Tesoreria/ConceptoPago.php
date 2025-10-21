<?php

namespace App\Models\Tesoreria;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ConceptoPago extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tes_cd_conceptos_pago';

    protected $fillable = [
        'nombre',
        'created_at',
        'updated_at',
        'created_by',
        'updated_by',
        'deleted_by'
    ];
}
