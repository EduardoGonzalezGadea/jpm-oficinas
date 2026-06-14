<?php

namespace App\Models\Tesoreria;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;
use App\Traits\LogsActivityTrait;

class SiifDistribucionDependencia extends Model
{
    use HasFactory, SoftDeletes, Auditable, LogsActivityTrait;

    protected $table = 'siif_distribucion_dependencias';

    protected $fillable = [
        'dependencia',
        'abreviatura',
        'created_by',
        'updated_by',
        'deleted_by'
    ];

    public function scopeSearch($query, $term)
    {
        if (empty($term)) {
            return $query;
        }

        return $query->where(function($q) use ($term) {
            $q->where('dependencia', 'like', '%' . $term . '%')
              ->orWhere('abreviatura', 'like', '%' . $term . '%');
        });
    }

    public function scopeOrdenado($query)
    {
        return $query->orderBy('dependencia');
    }
}
