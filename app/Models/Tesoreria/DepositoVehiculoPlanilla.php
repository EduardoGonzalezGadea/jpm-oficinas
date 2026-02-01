<?php

namespace App\Models\Tesoreria;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivityTrait;

class DepositoVehiculoPlanilla extends Model
{
    use HasFactory, LogsActivityTrait;

    protected $table = 'tes_deposito_vehiculo_planillas';

    protected $fillable = [
        'numero',
        'fecha',
        'anulada',
        'anulada_fecha',
        'anulada_by',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'fecha' => 'date',
        'anulada' => 'boolean',
        'anulada_fecha' => 'datetime',
    ];

    /**
     * Boot del modelo
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($planilla) {
            if (empty($planilla->numero)) {
                $planilla->numero = static::generateNumero();
            }
        });
    }

    /**
     * Generar número de planilla
     */
    private static function generateNumero()
    {
        $year = date('Y');
        $lastPlanilla = static::whereYear('fecha', $year)->orderBy('id', 'desc')->first();

        if ($lastPlanilla) {
            $parts = explode('/', $lastPlanilla->numero);
            $lastNumber = isset($parts[0]) ? (int)$parts[0] : 0;
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return sprintf('%04d/%s', $newNumber, $year);
    }

    /**
     * Verificar si la planilla está anulada
     */
    public function isAnulada()
    {
        return $this->anulada === true;
    }

    /**
     * Anular la planilla
     */
    public function anular()
    {
        $this->anulada = true;
        $this->anulada_fecha = now();
        $this->anulada_by = auth()->id();
        $this->save();

        // Liberar los depósitos asociados
        $this->depositos()->update(['planilla_id' => null]);
    }

    /**
     * Relación con DepositoVehiculo
     */
    public function depositos()
    {
        return $this->hasMany(DepositoVehiculo::class, 'planilla_id');
    }

    /**
     * Relación con el usuario que anuló
     */
    public function anuladaPor()
    {
        return $this->belongsTo(\App\Models\User::class, 'anulada_by');
    }

    /**
     * Relación con el usuario creador
     */
    public function createdBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    /**
     * Relación con el usuario que actualizó
     */
    public function updatedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'updated_by');
    }
}
