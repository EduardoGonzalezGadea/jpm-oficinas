<?php

namespace App\Models\Tesoreria;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Tesoreria\TesTenenciaArmas;
use App\Traits\LogsActivityTrait;

class TesTenenciaArmasPlanilla extends Model
{
    use HasFactory, SoftDeletes, LogsActivityTrait;

    protected $table = 'tes_tenencia_armas_planillas';

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

    public static function generarNumero($fecha)
    {
        $fechaStr = \Carbon\Carbon::parse($fecha)->format('Y-m-d');

        $ultimaPlanilla = static::where('numero', 'like', 'T' . $fechaStr . '-%')
            ->orderBy('numero', 'desc')
            ->first();

        if ($ultimaPlanilla) {
            $parts = explode('-', $ultimaPlanilla->numero);
            $lastSequential = (int) end($parts);
            $nextSequential = $lastSequential + 1;
        } else {
            $nextSequential = 1;
        }

        return 'T' . $fechaStr . '-' . $nextSequential;
    }

    public function tenenciaArmas()
    {
        return $this->hasMany(TesTenenciaArmas::class, 'planilla_id');
    }

    public function anuladaPor()
    {
        return $this->belongsTo(User::class, 'anulada_user_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function isAnulada()
    {
        return !is_null($this->anulada_fecha);
    }

    public function anular()
    {
        DB::transaction(function () {
            $this->anulada_fecha = now();
            $this->anulada_user_id = Auth::id();
            $this->save();

            // Liberar los registros asociados usando DB directo para mayor seguridad
            DB::table('tes_tenencia_armas')
                ->where('planilla_id', $this->id)
                ->update(['planilla_id' => null]);
        });
    }
}
