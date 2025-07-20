<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'nombre',
        'apellido',
        'email',
        'telefono',
        'cedula',
        'password',
        'activo',
        'modulo_id'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'activo' => 'boolean',
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

    // Scopes
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    // Helper methods
    public function esAdministrador()
    {
        return $this->hasRole('administrador');
    }

    public function esSupervisor()
    {
        return $this->hasRole('supervisor');
    }

    public function puedeGestionarUsuarios()
    {
        return $this->esAdministrador() || $this->esSupervisor();
    }
}
