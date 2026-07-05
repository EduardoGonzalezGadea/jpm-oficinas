<?php

namespace App\Models\Tesoreria;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsActivityTrait;

class TesMultasItems extends Model
{
    use HasFactory, SoftDeletes, Auditable, LogsActivityTrait;

    protected $table = 'tes_multas_items';

    protected $fillable = [
        'tes_multas_cobradas_id',
        'codigo',
        'descripcion',
        'detalle',
        'importe',
        'monto_ur',
        'monto_pesos',
        'subtotal',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'importe' => 'decimal:2',
        'monto_ur' => 'decimal:4',
        'monto_pesos' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    public function cobrada()
    {
        return $this->belongsTo(TesMultasCobradas::class, 'tes_multas_cobradas_id');
    }

    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }
}
