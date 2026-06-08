<?php

namespace Tests\Feature\Tesoreria;

use App\Http\Livewire\Tesoreria\MultasCobradas\MultasCobradas;
use App\Models\User;
use App\Models\Tesoreria\TesMultasCobradas;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;
use Spatie\Permission\Models\Permission;

class MultasCobradasTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Crea todos los permisos necesarios para el guard 'api'
     * (guard_name del modelo User en este proyecto).
     */
    protected function setUp(): void
    {
        parent::setUp();

        $permisos = [
            'operador_tesoreria', 'acceso_administrador', 'ver_auditoria',
            'administrar_sistema', 'gerente_tesoreria', 'supervisor_tesoreria',
            'ver_usuarios', 'ver_roles', 'ver_permisos', 'ver_modulos',
            'acceso_tesoreria', 'ver_bancos', 'ver_cuentas_bancarias',
        ];

        foreach ($permisos as $permiso) {
            Permission::firstOrCreate(['name' => $permiso, 'guard_name' => 'api']);
        }
    }

    /**
     * Helper: crea un usuario con permisos en el guard 'api'.
     */
    private function crearUsuarioConPermiso(string $permiso): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo(
            Permission::where('name', $permiso)->where('guard_name', 'api')->first()
        );
        return $user;
    }

    // =========================================================================
    // TESTS DE COMPONENTE (Fase 4)
    // =========================================================================

    /** @test */
    public function usuario_puede_crear_multa_manualmente(): void
    {
        $this->withoutExceptionHandling();
        
        $user = $this->crearUsuarioConPermiso('operador_tesoreria');
        $this->actingAs($user, 'api');

        Livewire::test(MultasCobradas::class)
            // Abrir modal de creación
            ->call('create')
            ->assertSet('editMode', false)
            ->assertSet('showModal', true)
            
            // Llenar formulario (cabecera)
            ->set('recibo', 'A-12345')
            ->set('cedula', '12345678')
            ->set('nombre', 'Juan Perez')
            ->set('fecha', '2026-03-19')
            ->set('monto', 1500.00)
            ->set('forma_pago', 'Efectivo')
            
            // Llenar formulario (items)
            ->set('items_form', [
                [
                    '_uid' => 'test-uid-1',
                    'detalle' => 'Multa por exceso de velocidad',
                    'descripcion' => 'Infracción comprobada',
                    'importe' => 1500.00
                ]
            ])
            
            // Guardar
            ->call('save')
            
            // Validar que se cerró el modal y no hay errores de validación
            ->assertHasNoErrors()
            ->assertSet('showModal', false);

        // Verificar en Base de Datos (Cabecera)
        $this->assertDatabaseHas('tes_multas_cobradas', [
            'recibo' => 'A-12345',
            'monto' => 1500.00,
            'nombre' => 'JUAN PEREZ' // El trait ConvertirMayusculas debería pasarlo a mayúsculas
        ]);

        // Verificar en Base de Datos (Items)
        $this->assertDatabaseHas('tes_multas_items', [
            'detalle' => 'Multa por exceso de velocidad',
            'importe' => 1500.00
        ]);
    }

    // =========================================================================
    // TESTS REALES
    // =========================================================================

    /** @test */
    public function usuario_sin_permiso_no_puede_acceder_a_multas_cobradas()
    {
        $user = User::factory()->create();
        $this->assertFalse($user->hasPermissionTo('operador_tesoreria'));
    }

    /** @test */
    public function usuario_con_permiso_tiene_acceso_a_multas_cobradas()
    {
        $user = $this->crearUsuarioConPermiso('operador_tesoreria');
        $this->assertTrue($user->hasPermissionTo('operador_tesoreria'));
    }
}
