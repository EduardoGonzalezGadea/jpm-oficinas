<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsActivityTrait;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable, HasRoles, SoftDeletes, LogsActivityTrait;

    protected $guard_name = 'api';



    protected $fillable = [
        'nombre',
        'apellido',
        'email',
        'telefono',
        'direccion',
        'cedula',
        'password',
        'activo',
        'modulo_id',
        'created_by',
        'updated_by',
        'deleted_by',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_confirmed_at',
        'theme',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'activo' => 'boolean',
        'two_factor_confirmed_at' => 'datetime',
        'two_factor_recovery_codes' => 'encrypted:array',
    ];

    // JWT Methods
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    // Relationships
    public function modulo()
    {
        return $this->belongsTo(Modulo::class);
    }

    // Helper methods
    public function esAdministrador()
    {
        return $this->hasPermissionTo('acceso_administrador');
    }

    public function esGerente()
    {
        return $this->hasPermissionTo('acceso_gerente');
    }

    public function esSupervisor()
    {
        return $this->hasPermissionTo('acceso_supervisor');
    }

    public function puedeGestionarUsuarios()
    {
        return $this->esAdministrador() || $this->esGerente() || $this->esSupervisor();
    }

    // Accessors
    public function getNombreCompletoAttribute()
    {
        return $this->nombre . ' ' . $this->apellido;
    }

    // Scopes
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function scopeInactivos($query)
    {
        return $query->where('activo', false);
    }

    public function getThemePathAttribute()
    {
        $themes = [
            'cerulean' => 'libs/bootswatch@4.6.2/dist/cerulean/bootstrap.min.css',
            'cosmo'    => 'libs/bootswatch@4.6.2/dist/cosmo/bootstrap.min.css',
            'litera'   => 'libs/bootswatch@4.6.2/dist/litera/bootstrap.min.css',
            'cyborg'   => 'libs/bootswatch@4.6.2/dist/cyborg/bootstrap.min.css',
            'darkly'   => 'libs/bootswatch@4.6.2/dist/darkly/bootstrap.min.css',
            'material' => 'libs/bootswatch@4.6.2/dist/materia/bootstrap.min.css',
            'default'  => 'libs/bootstrap-4.6.2-dist/css/bootstrap.min.css',
        ];

        $path = $themes[$this->theme] ?? $themes['cosmo'];
        return asset($path);
    }
}
