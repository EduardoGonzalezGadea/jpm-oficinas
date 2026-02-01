<?php

namespace App\Models\Tesoreria;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use App\Traits\LogsActivityTrait;

class PrendaPlanilla extends Model
{
    use HasFactory, SoftDeletes, LogsActivityTrait;

    protected $table = 'tes_prendas_planillas';

    protected $fillable = [
        'fecha',
        'numero',
        'anulada_fecha',
        'anulada_user_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'fecha' => 'date',
        'anulada_fecha' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->fecha) {
                $model->fecha = now();
            }
            if (!$model->numero) {
                $model->numero = static::generarNumero($model->fecha);
            }
            $model->created_by = Auth::id();
        });

        static::updating(function ($model) {
            $model->updated_by = Auth::id();
        });
    }

    /**
     * Generate next numero for the given date
     * Format: YYYY-MM-DD-N (where N is the daily sequential number)
     */
    public static function generarNumero($fecha)
    {
        $fechaStr = \Carbon\Carbon::parse($fecha)->format('Y-m-d');

        // Get the highest numero for this date
        $ultimaPlanilla = static::where('numero', 'like', $fechaStr . '-%')
            ->orderBy('numero', 'desc')
            ->first();

        if ($ultimaPlanilla) {
            // Extract the sequential number from the last numero
            $parts = explode('-', $ultimaPlanilla->numero);
            $lastSequential = (int) end($parts);
            $nextSequential = $lastSequential + 1;
        } else {
            $nextSequential = 1;
        }

        return $fechaStr . '-' . $nextSequential;
    }

    /**
     * Relationship: Prendas in this planilla
     */
    public function prendas()
    {
        return $this->hasMany(Prenda::class, 'planilla_id');
    }

    /**
     * Relationship: User who annulled the planilla
     */
    public function anuladaPor()
    {
        return $this->belongsTo(User::class, 'anulada_user_id');
    }

    /**
     * Relationship: User who created the record
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relationship: User who last updated the record
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Check if planilla is annulled
     */
    public function isAnulada()
    {
        return !is_null($this->anulada_fecha);
    }

    /**
     * Anular the planilla
     */
    public function anular()
    {
        $this->anulada_fecha = now();
        $this->anulada_user_id = Auth::id();
        $this->save();

        // Set planilla_id to null for all prendas in this planilla
        $this->prendas()->update(['planilla_id' => null]);
    }
    public function getTotalAttribute()
    {
        return $this->prendas->sum('monto');
    }

    public function getTotalFormateadoAttribute()
    {
        return number_format($this->total, 2, ',', '.');
    }
}
