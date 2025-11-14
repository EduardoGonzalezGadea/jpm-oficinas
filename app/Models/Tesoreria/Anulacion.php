<?php

namespace App\Models\Tesoreria;

use Illuminate\Database\Eloquent\Model;

class Anulacion extends Model
{
    protected $table = 'tes_anulaciones';
    protected $fillable = ['anulable_id', 'anulable_type', 'datos_originales', 'motivo', 'anulado_por', 'fecha_anulacion'];
    protected $casts = ['datos_originales' => 'array', 'fecha_anulacion' => 'datetime'];
}
