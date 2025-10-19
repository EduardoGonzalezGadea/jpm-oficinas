<?php

namespace App\Models\Tesoreria\CajaDiaria;

use App\Traits\ConvertirMayusculas;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CobroCampoValor extends Model
{
    use HasFactory, ConvertirMayusculas;

    protected $table = 'tes_cd_cobros_campos_valores';

    protected $fillable = [
        'cobro_id',
        'campo_id',
        'valor'
    ];

    public function setValorAttribute($value)
    {
        $this->attributes['valor'] = $this->toUpper($value);
    }

    // Relación con cobro
    public function cobro()
    {
        return $this->belongsTo(Cobro::class, 'cobro_id');
    }

    // Relación con campo
    public function campo()
    {
        return $this->belongsTo(ConceptoCobroCampo::class, 'campo_id');
    }
}
