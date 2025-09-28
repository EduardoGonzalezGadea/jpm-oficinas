<?php

namespace App\Tesoreria\CajaDiaria;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TesCajaDiarias extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tes_caja_diarias';

    protected $fillable = [
        'fecha',
        'monto_inicial',
        'observaciones',
        'estado',
        'created_by',
        'updated_by',
        'default_by',
    ];

    protected $casts = [
        'fecha' => 'date',
        'monto_inicial' => 'decimal:2',
    ];

    // Relationships
    public function createdBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'updated_by');
    }

    public function defaultBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'default_by');
    }
}
