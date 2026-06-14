<?php


namespace App\Models\Tesoreria;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsActivityTrait;

class TesCchAcreedor extends Model
{
    use HasFactory, SoftDeletes, Auditable, LogsActivityTrait;

    protected $table = 'tes_cch_acreedores';
    protected $primaryKey = 'idAcreedores';

    protected $fillable = [
        'acreedor',
        'created_by',
        'updated_by',
        'deleted_by',
    ];
}
