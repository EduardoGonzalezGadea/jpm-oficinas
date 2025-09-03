<?php

namespace App\Models\Tesoreria;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Planilla extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tes_arr_planillas';

    protected $fillable = [
        'numero',
        'fecha_creacion',
        'user_id',
    ];

    protected $dates = ['fecha_creacion'];

    public function arrendamientos()
    {
        return $this->hasMany(Arrendamiento::class, 'planilla_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
