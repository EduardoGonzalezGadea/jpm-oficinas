<?php

namespace App\Modules;

class ModuleRegistry
{
    const MODULES = [
        'tesoreria' => [
            'nombre' => 'Tesorería',
            'clave' => 'tesoreria',
            'niveles' => ['gerente', 'supervisor', 'operador'],
            'recursos' => [
                'pagos',
                'conceptos',
                'multas',
                'caja-chica',
                'valores',
                'arrendamientos',
                'eventuales',
                'certificados',
                'prendas',
                'armas',
            ],
        ],
        'asesoria_contable' => [
            'nombre' => 'Asesoría Contable',
            'clave' => 'asesoria_contable',
            'niveles' => ['gerente', 'supervisor', 'operador'],
            'recursos' => [
                'eventuales',
                'arrendamientos',
                'certificados',
            ],
        ],
    ];

    const JERARQUIA = [
        'operador' => 1,
        'supervisor' => 2,
        'gerente' => 3,
    ];

    public static function claves(): array
    {
        return array_keys(self::MODULES);
    }

    public static function existe(string $clave): bool
    {
        return isset(self::MODULES[$clave]);
    }

    public static function nombre(string $clave): string
    {
        return self::MODULES[$clave]['nombre'] ?? 'Desconocido';
    }

    public static function niveles(string $clave): array
    {
        return self::MODULES[$clave]['niveles'] ?? [];
    }

    public static function recursos(string $clave): array
    {
        return self::MODULES[$clave]['recursos'] ?? [];
    }

    public static function nivelJerarquia(string $nivel): int
    {
        return self::JERARQUIA[$nivel] ?? 0;
    }

    public static function permiso(string $modulo, string $recurso, string $accion): string
    {
        return "{$modulo}.{$recurso}.{$accion}";
    }

    public static function rolName(string $modulo, string $nivel): string
    {
        return "{$modulo}_{$nivel}";
    }

    public static function nivelDesdeRol(string $roleName): ?string
    {
        foreach (self::claves() as $clave) {
            foreach (self::niveles($clave) as $nivel) {
                if ($roleName === self::rolName($clave, $nivel)) {
                    return $nivel;
                }
            }
        }
        return null;
    }

    public static function moduloDesdeRol(string $roleName): ?string
    {
        foreach (self::claves() as $clave) {
            foreach (self::niveles($clave) as $nivel) {
                if ($roleName === self::rolName($clave, $nivel)) {
                    return $clave;
                }
            }
        }
        return null;
    }

    public static function rolesDelModulo(string $clave): array
    {
        return array_map(fn($nivel) => self::rolName($clave, $nivel), self::niveles($clave));
    }

    public static function todosLosRoles(): array
    {
        $roles = [];
        foreach (self::claves() as $clave) {
            $roles = array_merge($roles, self::rolesDelModulo($clave));
        }
        return $roles;
    }

    public static function opcionesSelect(): array
    {
        $opciones = [];
        foreach (self::MODULES as $clave => $config) {
            $group = [];
            foreach ($config['niveles'] as $nivel) {
                $rolName = self::rolName($clave, $nivel);
                $label = ucfirst($nivel) . ' - ' . $config['nombre'];
                $group[$rolName] = $label;
            }
            $opciones[$config['nombre']] = $group;
        }
        return $opciones;
    }
}
