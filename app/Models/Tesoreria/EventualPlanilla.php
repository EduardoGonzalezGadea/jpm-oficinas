<?php

namespace App\Models\Tesoreria;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EventualPlanilla extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tes_eventuales_planillas';

    protected $fillable = [
        'numero',
        'fecha_creacion',
        'user_id',
    ];

    protected $dates = ['fecha_creacion'];

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function eventuales()
    {
        return $this->hasMany(Eventual::class, 'planilla_id');
    }
}
