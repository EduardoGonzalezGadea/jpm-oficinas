<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\ConvertirMayusculas;
use App\Traits\LogsActivityTrait;
use Illuminate\Support\Facades\Auth;

class Cobro extends Model
{
    use HasFactory, SoftDeletes, ConvertirMayusculas, LogsActivityTrait;

    protected $table = 'tes_cd_cobros';

    protected $fillable = [
        'fecha',
        'monto',
        'medio_pago',
        'descripcion',
        'recibo',
        'concepto_id',
        'created_at',
        'updated_at',
        'created_by',
        'updated_by',
        'deleted_by'
    ];

    protected $casts = [
        'fecha' => 'date',
        'monto' => 'decimal:2'
    ];

    public function setMedioPagoAttribute($value)
    {
        $this->attributes['medio_pago'] = $this->toUpper($value);
    }

    public function setDescripcionAttribute($value)
    {
        $this->attributes['descripcion'] = $this->toUpper($value);
    }

    public static function boot()
    {
        parent::boot();

        static::deleting(function ($cobro) {
            if (Auth::check()) {
                $cobro->deleted_by = Auth::id();
                $cobro->save();
            }
        });
    }

    // Relación con concepto
    public function concepto()
    {
        return $this->belongsTo(\App\Models\Tesoreria\CajaDiaria\ConceptoCobro::class, 'concepto_id');
    }

    // Relación con valores de campos
    public function campoValores()
    {
        return $this->hasMany(\App\Models\Tesoreria\CajaDiaria\CobroCampoValor::class, 'cobro_id');
    }

    // Scope para búsqueda
    public function scopeSearch($query, $term)
    {
        if (empty($term)) {
            return $query;
        }

        return $query->where(function ($query) use ($term) {
            $query->where('descripcion', 'like', '%' . $term . '%')
                ->orWhere('recibo', 'like', '%' . $term . '%')
                ->orWhereHas('concepto', function ($q) use ($term) {
                    $q->where('nombre', 'like', '%' . $term . '%');
                });
        });
    }
}
