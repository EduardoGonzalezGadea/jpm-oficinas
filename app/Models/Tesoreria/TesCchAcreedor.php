<?php

namespace App\Models\Tesoreria;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TesCchAcreedor extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tes_cch_acreedores';
    protected $primaryKey = 'idAcreedores';

    protected $fillable = [
        'acreedor',
        'created_by',
        'updated_by',
        'deleted_by',
    ];
}
