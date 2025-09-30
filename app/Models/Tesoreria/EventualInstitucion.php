<?php

namespace App\Models\Tesoreria;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EventualInstitucion extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tes_eventuales_instituciones';

    protected $fillable = [
        'nombre',
        'descripcion',
        'activa',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'activa' => 'boolean',
    ];

    public function eventuales()
    {
        return $this->hasMany(Eventual::class, 'institucion', 'nombre');
    }

    public function scopeActivas($query)
    {
        return $query->where('activa', true);
    }
}