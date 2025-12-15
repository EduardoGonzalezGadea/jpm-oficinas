<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable, HasRoles, SoftDeletes;

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
}
