<?php

namespace App\Models\Tesoreria;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use App\Traits\LogsActivityTrait;

class Anulacion extends Model
{
    use SoftDeletes, Auditable, LogsActivityTrait;
    protected $table = 'tes_anulaciones';
    protected $fillable = ['anulable_id', 'anulable_type', 'datos_originales', 'motivo', 'anulado_por', 'fecha_anulacion', 'created_by', 'updated_by', 'deleted_by'];
    protected $casts = ['datos_originales' => 'array', 'fecha_anulacion' => 'datetime'];
}