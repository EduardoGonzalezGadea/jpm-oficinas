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

    protected function setUp(): void
    {
        parent::setUp();

        $permisos = [
            'tesoreria.acceso', 'sistema.acceso.administrador', 'sistema.auditoria',
            'sistema.backups', 'usuarios.ver', 'tesoreria.supervisar',
        ];

        foreach ($permisos as $permiso) {
            Permission::firstOrCreate(['name' => $permiso, 'guard_name' => 'web']);
        }
    }

    private function crearUsuarioConPermiso(string $permiso): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo(
            Permission::where('name', $permiso)->where('guard_name', 'web')->first()
        );
        return $user;
    }

    /** @test */
    public function usuario_puede_crear_multa_manualmente(): void
    {
        $this->withoutExceptionHandling();

        $user = $this->crearUsuarioConPermiso('tesoreria.acceso');
        $this->actingAs($user, 'web');

        Livewire::test(MultasCobradas::class)
            ->call('create')
            ->assertSet('editMode', false)
            ->assertSet('showModal', true)
            ->set('recibo', 'A-12345')
            ->set('cedula', '12345678')
            ->set('nombre', 'Juan Perez')
            ->set('fecha', '2026-03-19')
            ->set('monto', 1500.00)
            ->set('forma_pago', 'Efectivo')
            ->set('items_form', [
                [
                    '_uid' => 'test-uid-1',
                    'detalle' => 'Multa por exceso de velocidad',
                    'descripcion' => 'Infracción comprobada',
                    'importe' => 1500.00
                ]
            ])
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('showModal', false);

        $this->assertDatabaseHas('tes_multas_cobradas', [
            'recibo' => 'A-12345',
            'monto' => 1500.00,
            'nombre' => 'JUAN PEREZ'
        ]);

        $this->assertDatabaseHas('tes_multas_items', [
            'detalle' => 'Multa por exceso de velocidad',
            'importe' => 1500.00
        ]);
    }

    /** @test */
    public function usuario_sin_permiso_no_puede_acceder_a_multas_cobradas()
    {
        $user = User::factory()->create();
        $this->assertFalse($user->hasPermissionTo('tesoreria.acceso'));
    }

    /** @test */
    public function usuario_con_permiso_tiene_acceso_a_multas_cobradas()
    {
        $user = $this->crearUsuarioConPermiso('tesoreria.acceso');
        $this->assertTrue($user->hasPermissionTo('tesoreria.acceso'));
    }
}
