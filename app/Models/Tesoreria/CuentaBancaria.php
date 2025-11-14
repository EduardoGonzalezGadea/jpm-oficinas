<?php
// app/Models/Tesoreria/CuentaBancaria.php
namespace App\Models\Tesoreria;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CuentaBancaria extends Model
{
    use SoftDeletes;

    protected $table = 'tes_cuentas_bancarias';
    protected $fillable = ['banco_id', 'numero_cuenta', 'tipo', 'activa', 'observaciones', 'created_by', 'updated_by'];

    public function banco()
    {
        return $this->belongsTo(Banco::class, 'banco_id');
    }

    public function cheques()
    {
        return $this->hasMany(Cheque::class, 'cuenta_bancaria_id');
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(fn($m) => $m->created_by = auth()->id());
        static::updating(fn($m) => $m->updated_by = auth()->id());
        static::deleting(fn($m) => $m->deleted_by = auth()->id());
    }
}
