<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TesCfePendiente extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tipo_cfe',
        'serie',
        'numero',
        'fecha',
        'monto',
        'moneda',
        'datos_extraidos',
        'pdf_path',
        'source_url',
        'estado',
        'motivo_rechazo',
        'user_id',
        'procesado_por',
        'procesado_at',
    ];

    protected $casts = [
        'fecha' => 'date',
        'procesado_at' => 'datetime',
        'monto' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function procesadoPor()
    {
        return $this->belongsTo(User::class, 'procesado_por');
    }
}
