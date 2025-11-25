<?php

namespace App\Models\Tesoreria;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;

class Servicio extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tes_servicios';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nombre',
        'valor_ui',
        'activo',
    ];

    /**
     * The tipos de libreta that belong to the Servicio.
     */
    public function tiposLibreta()
    {
        return $this->belongsToMany(TipoLibreta::class, 'tes_servicio_tipo_libreta', 'servicio_id', 'tipo_libreta_id');
    }
}
