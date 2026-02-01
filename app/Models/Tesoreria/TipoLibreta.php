<?php

namespace App\Models\Tesoreria;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsActivityTrait;

class TipoLibreta extends Model
{
    use HasFactory, SoftDeletes, Auditable, LogsActivityTrait;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tes_tipos_libretas';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nombre',
        'cantidad_recibos',
        'stock_minimo_recibos',
    ];

    /**
     * The services that belong to the TipoLibreta.
     */
    public function servicios()
    {
        return $this->belongsToMany(Servicio::class, 'tes_servicio_tipo_libreta', 'tipo_libreta_id', 'servicio_id');
    }
}
