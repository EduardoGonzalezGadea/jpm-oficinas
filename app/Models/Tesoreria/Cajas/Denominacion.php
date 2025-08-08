<?php

namespace App\Models\Tesoreria\Cajas;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Denominacion extends Model
{
    use SoftDeletes;

    protected $table = 'tes_denominaciones';
    protected $primaryKey = 'idDenominacion';

    protected $fillable = [
        'valor',
        'tipo',
        'activo',
        'orden'
    ];

    protected $casts = [
        'valor' => 'decimal:2',
        'activo' => 'boolean',
        'orden' => 'integer'
    ];

    public static function obtenerDenominacionesActivas()
    {
        return static::where('activo', true)
            ->orderBy('orden')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->valor => 0];
            });
    }

    public static function obtenerPorTipo($tipo)
    {
        return static::where('activo', true)
            ->where('tipo', $tipo)
            ->orderBy('orden')
            ->get();
    }
}
