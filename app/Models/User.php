<?php

namespace App\Models;

use App\Modules\ModuleRegistry;
use Illuminate\Database\Eloquent\Builder;
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

    protected $guard_name = 'web';

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

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    public function modulo()
    {
        return $this->belongsTo(Modulo::class);
    }

    public function moduloClave(): ?string
    {
        return $this->modulo?->clave;
    }

    public function nivelActual(): ?string
    {
        foreach ($this->getRoleNames() as $rol) {
            $nivel = ModuleRegistry::nivelDesdeRol($rol);
            if ($nivel) return $nivel;
        }
        return null;
    }

    public function moduloActual(): ?string
    {
        foreach ($this->getRoleNames() as $rol) {
            $modulo = ModuleRegistry::moduloDesdeRol($rol);
            if ($modulo) return $modulo;
        }
        return null;
    }

    public function esAdministrador(): bool
    {
        return $this->hasRole('administrador');
    }

    public function esGerente(): bool
    {
        return $this->getRoleNames()->contains(fn($rol) =>
            ModuleRegistry::nivelDesdeRol($rol) === 'gerente'
        );
    }

    public function esSupervisor(): bool
    {
        return $this->getRoleNames()->contains(fn($rol) =>
            ModuleRegistry::nivelDesdeRol($rol) === 'supervisor'
        );
    }

    public function esOperador(): bool
    {
        return $this->getRoleNames()->contains(fn($rol) =>
            ModuleRegistry::nivelDesdeRol($rol) === 'operador'
        );
    }

    public function puedeGestionarUsuarios(): bool
    {
        return $this->esAdministrador() || $this->esGerente() || $this->esSupervisor();
    }

    public function nivelJerarquia(): int
    {
        if ($this->esAdministrador()) return 99;
        $nivel = $this->nivelActual();
        return $nivel ? ModuleRegistry::nivelJerarquia($nivel) : 0;
    }

    public function getNombreCompletoAttribute()
    {
        return $this->nombre . ' ' . $this->apellido;
    }

    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('activo', true);
    }

    public function scopeInactivos(Builder $query): Builder
    {
        return $query->where('activo', false);
    }

    public function scopeDelModulo(Builder $query, string $clave): Builder
    {
        return $query->whereHas('modulo', fn($q) => $q->where('clave', $clave));
    }

    public function scopeGestionables(Builder $query): Builder
    {
        if (!$this->esAdministrador()) {
            $clave = $this->moduloClave();
            if ($clave) {
                $query->delModulo($clave);
            }
        }
        return $query;
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
