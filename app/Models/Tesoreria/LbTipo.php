<?php

namespace App\Models\Tesoreria;

use App\Models\User;
use App\Traits\Auditable;
use App\Traits\LogsActivityTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LbTipo extends Model
{
    use HasFactory, SoftDeletes, Auditable, LogsActivityTrait;

    protected $table = 'tes_lb_tipos';

    protected $fillable = [
        'nombre',
        'signo',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'signo' => 'integer',
    ];

    public function libroDiario()
    {
        return $this->hasMany(LibroDiario::class, 'tipo_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public function scopeActivos($query)
    {
        return $query->whereNull('deleted_at');
    }

    public function scopeOrdenado($query)
    {
        return $query->orderBy('nombre');
    }

    public function scopeSearch($query, $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('nombre', 'like', "%{$term}%");
        });
    }
}
