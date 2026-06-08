<?php

namespace App\Models\Tesoreria;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;
use App\Traits\LogsActivityTrait;

class Multa303 extends Model
{
    use HasFactory, SoftDeletes, Auditable, LogsActivityTrait;

    protected $table = 'tes_multas_303_2023';

    protected $fillable = [
        'grupo',
        'codigo',
        'descripcion',
        'valor_ur',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $dates = ['deleted_at'];
    public $timestamps = true;

    /**
     * Scope para filtrar por grupo
     */
    public function scopePorGrupo($query, $grupo)
    {
        return $query->where('grupo', $grupo);
    }

    /**
     * Scope para filtrar por codigo
     */
    public function scopePorCodigo($query, $codigo)
    {
        return $query->where('codigo', 'like', $codigo . '%');
    }

    /**
     * Scope para buscar en la descripcion
     */
    public function scopeBuscarDescripcion($query, $termino)
    {
        return $query->where('descripcion', 'LIKE', '%' . $termino . '%');
    }
}
