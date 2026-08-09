<?php

namespace App\Models\Tesoreria\Cajas;

use App\Models\User;
use App\Models\Tesoreria\LibroDiario;
use App\Models\Tesoreria\MedioDePago;
use App\Models\Tesoreria\TesCfe;
use Illuminate\Database\Eloquent\Model;

class CajaMovimiento extends Model
{
    protected $table = 'tes_cajas_movimientos';

    protected $fillable = [
        'caja_apertura_id', 'tipo_movimiento', 'monto', 'medio_pago_id',
        'cfe_id', 'libro_diario_id', 'concepto', 'descripcion', 'created_by',
    ];

    public function cajaApertura()
    {
        return $this->belongsTo(CajaApertura::class, 'caja_apertura_id');
    }

    public function medioPago()
    {
        return $this->belongsTo(MedioDePago::class, 'medio_pago_id');
    }

    public function cfe()
    {
        return $this->belongsTo(TesCfe::class, 'cfe_id');
    }

    public function libroDiario()
    {
        return $this->belongsTo(LibroDiario::class, 'libro_diario_id');
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scopes por medio de pago
    public function scopeEfectivo($query)
    {
        return $query->whereHas('medioPago', function ($q) {
            $q->where('nombre', MedioDePago::EFECTIVO);
        });
    }

    // Movimientos cuyo medio de pago NO es Efectivo (incluye los sin especificar)
    public function scopeOtrosMedios($query)
    {
        return $query->whereDoesntHave('medioPago', function ($q) {
            $q->where('nombre', MedioDePago::EFECTIVO);
        });
    }

    /**
     * Excluye movimientos huérfanos: los que apuntan a un asiento del libro diario
     * que fue eliminado (soft-delete) o ya no existe. Mantiene los movimientos que
     * no generan asiento (libro_diario_id null) para no romper otros flujos.
     */
    public function scopeConLibroVigente($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('libro_diario_id')
              ->orWhereHas('libroDiario', function ($ld) {
                  $ld->whereNull('deleted_at');
              });
        });
    }
}