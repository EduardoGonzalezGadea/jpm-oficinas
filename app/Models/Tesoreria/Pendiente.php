<?php

namespace App\Models\Tesoreria;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes; // <-- Importado

class Pendiente extends Model
{
    use HasFactory, SoftDeletes; // <-- Agregado SoftDeletes

    protected $table = 'tes_cch_pendientes';
    protected $primaryKey = 'idPendientes';
    public $timestamps = true; // <-- Cambiado a true

    protected $fillable = [
        'relCajaChica',
        'pendiente',
        'fechaPendientes',
        'relDependencia',
        'montoPendientes',
    ];


    /**
     * The attributes that should be cast.
     *
     * Esto le indica a Laravel cómo convertir los atributos.
     * Al usar 'date:Y-m-d', nos aseguramos de que siempre que se acceda a esta
     * propiedad, se formatee correctamente para un <input type="date">.
     *
     * @var array
     */
    protected $casts = [
        'fechaPendientes' => 'date:Y-m-d',
    ];

    /**
     * Las columnas que deben ser tratadas como fechas.
     * Esto es necesario para que Laravel maneje correctamente las fechas
     * y las convierta a objetos Carbon al acceder a ellas.
     */
    protected $dates = ['deleted_at']; // <-- Especificar la columna deleted_at

    // Relaciones
    public function cajaChica()
    {
        return $this->belongsTo(CajaChica::class, 'relCajaChica', 'idCajaChica');
    }

    public function dependencia()
    {
        return $this->belongsTo(Dependencia::class, 'relDependencia', 'idDependencias');
    }

    public function movimientos()
    {
        return $this->hasMany(Movimiento::class, 'relPendiente', 'idPendientes');
    }

    // Accesor para calcular totales (similar a la lógica del JS)
    // Nota: Estos accesorios ahora solo consideran movimientos no eliminados
    public function getTotRendidoAttribute()
    {
        return $this->movimientos()->sum('rendido'); // Usar relación para incluir soft deletes si es necesario
    }

    public function getTotReintegradoAttribute()
    {
        return $this->movimientos()->sum('reintegrado');
    }

    public function getTotRecuperadoAttribute()
    {
        return $this->movimientos()->sum('recuperado');
    }

    public function getExtraAttribute()
    {
        $totalRendidoReintegrado = $this->tot_rendido + $this->tot_reintegrado;
        if ($totalRendidoReintegrado > $this->montoPendientes) {
            return $totalRendidoReintegrado - $this->montoPendientes;
        }
        return 0;
    }

    public function getSaldoAttribute()
    {
        // Lógica original del JS
        $totalRendidoReintegrado = $this->tot_rendido + $this->tot_reintegrado;
        if ($totalRendidoReintegrado > $this->montoPendientes) {
            return $this->tot_rendido - $this->tot_recuperado;
        } else {
            return $this->montoPendientes - ($this->tot_reintegrado + $this->tot_recuperado);
        }
    }
}
