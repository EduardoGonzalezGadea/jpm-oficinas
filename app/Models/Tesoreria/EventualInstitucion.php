<?php

namespace App\Models\Tesoreria;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventualInstitucion extends Model
{
    use HasFactory;

    protected $table = 'tes_eventuales_instituciones';

    protected $fillable = [
        'nombre',
        'descripcion',
        'activa',
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