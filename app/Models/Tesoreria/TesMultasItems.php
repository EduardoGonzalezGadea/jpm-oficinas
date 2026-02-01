<?php

namespace App\Models\Tesoreria;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsActivityTrait;

class TesMultasItems extends Model
{
    use HasFactory, SoftDeletes, LogsActivityTrait;

    protected $table = 'tes_multas_items';

    protected $fillable = [
        'tes_multas_cobradas_id',
        'detalle',
        'descripcion',
        'importe',
        'created_by',
        'updated_by',
        'deleted_by',
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
