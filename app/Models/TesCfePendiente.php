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
        'datos_modificados',
        'pdf_path',
        'source_url',
        'pdf_hash',
        'extractor_version',
        'estado',
        'motivo_rechazo',
        'user_id',
        'procesado_por',
        'procesado_at',
    ];

    protected $casts = [
        'fecha'           => 'date',
        'procesado_at'    => 'datetime',
        'monto'           => 'decimal:2',
        'datos_extraidos' => 'array',
    ];

    public function scopePendientes($query)
    {
        return $query->where('estado', 'pendiente');
    }

    public function scopeEnRevision($query)
    {
        return $query->where('estado', 'en_revision');
    }

    public function scopeConfirmados($query)
    {
        return $query->where('estado', 'confirmado');
    }

    public function scopeRechazados($query)
    {
        return $query->where('estado', 'rechazado');
    }

    public function scopeExpirados($query)
    {
        return $query->where('estado', 'expirado');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function procesadoPor()
    {
        return $this->belongsTo(User::class, 'procesado_por');
    }
}
