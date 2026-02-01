<?php

namespace App\Models\Tesoreria;

use Illuminate\Database\Eloquent\Model;

use App\Traits\LogsActivityTrait;

class Anulacion extends Model
{
    use LogsActivityTrait;
    protected $table = 'tes_anulaciones';
    protected $fillable = ['anulable_id', 'anulable_type', 'datos_originales', 'motivo', 'anulado_por', 'fecha_anulacion'];
    protected $casts = ['datos_originales' => 'array', 'fecha_anulacion' => 'datetime'];
}
