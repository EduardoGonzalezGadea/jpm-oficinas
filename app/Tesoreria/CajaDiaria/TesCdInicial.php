<?php

namespace App\Tesoreria\CajaDiaria;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TesCdInicial extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tes_cd_inicials';

    protected $fillable = [
        'tes_caja_diarias_id',
        'tes_denominaciones_monedas_id',
        'monto',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public function cajaDiaria()
    {
        return $this->belongsTo(TesCajaDiarias::class, 'tes_caja_diarias_id');
    }

    public function denominacionMoneda()
    {
        // Assuming you have a TesDenominacionesMoneda model
        // return $this->belongsTo(TesDenominacionesMoneda::class, 'tes_denominaciones_monedas_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'updated_by');
    }

    public function deletedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'deleted_by');
    }
}
