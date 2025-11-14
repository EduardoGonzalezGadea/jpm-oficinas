<?php
// app/Models/Tesoreria/Banco.php
namespace App\Models\Tesoreria;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Banco extends Model
{
    use SoftDeletes;

    protected $table = 'tes_bancos';
    protected $fillable = ['nombre', 'codigo', 'observaciones', 'created_by', 'updated_by'];

    public function cuentas()
    {
        return $this->hasMany(CuentaBancaria::class, 'banco_id');
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(fn($m) => $m->created_by = auth()->id());
        static::updating(fn($m) => $m->updated_by = auth()->id());
        static::deleting(fn($m) => $m->deleted_by = auth()->id());
    }
}
