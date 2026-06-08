<?php

namespace Tests\Feature\Tesoreria;

use App\Http\Livewire\Tesoreria\Multa303;
use App\Models\User;
use App\Models\Tesoreria\Multa303 as Multa303Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;
use Spatie\Permission\Models\Permission;

class Multa303Test extends TestCase
{
    use RefreshDatabase;

    /**
     * Crea todos los permisos necesarios para el guard 'api'
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
     * Helper para crear un usuario con permisos en el guard 'api'
     */
    private function crearUsuarioConPermiso(string $permiso): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo(
            Permission::where('name', $permiso)->where('guard_name', 'api')->first()
        );
        return $user;
    }

    /** @test */
    public function usuario_puede_listar_y_buscar_multas_303(): void
    {
        $user = $this->crearUsuarioConPermiso('operador_tesoreria');
        $this->actingAs($user, 'api');

        // Crear algunas multas en la BD
        $multa1 = Multa303Model::create([
            'grupo' => 'GRUPO A',
            'codigo' => '1.2.3',
            'descripcion' => 'Infracción de prueba uno',
            'valor_ur' => '4',
        ]);

        $multa2 = Multa303Model::create([
            'grupo' => 'GRUPO B',
            'codigo' => '4.5.6',
            'descripcion' => 'Otra infracción de prueba dos',
            'valor_ur' => '2 x c/u',
        ]);

        // Test listado y búsqueda básica
        Livewire::test(Multa303::class)
            ->assertSee('1.2.3')
            ->assertSee('Infracción de prueba uno')
            ->assertSee('4.5.6')
            ->assertSee('Otra infracción de prueba dos')
            
            // Buscar por código
            ->set('search', '4.5.6')
            ->assertSee('Otra infracción de prueba dos')
            ->assertDontSee('Infracción de prueba uno');
    }

    /** @test */
    public function normalizacion_de_busqueda_convierte_comas_en_puntos(): void
    {
        $user = $this->crearUsuarioConPermiso('operador_tesoreria');
        $this->actingAs($user, 'api');

        Multa303Model::create([
            'grupo' => 'GRUPO A',
            'codigo' => '2.2',
            'descripcion' => 'Infracción con decimales',
            'valor_ur' => '3',
        ]);

        Livewire::test(Multa303::class)
            ->set('search', '2,2')
            ->assertSet('search', '2.2')
            ->assertSee('Infracción con decimales');
    }

    /** @test */
    public function usuario_puede_crear_multa_303(): void
    {
        $user = $this->crearUsuarioConPermiso('operador_tesoreria');
        $this->actingAs($user, 'api');

        Livewire::test(Multa303::class)
            ->call('create')
            ->assertSet('isOpen', true)
            ->assertSet('isEdit', false)
            
            // Rellenar formulario
            ->set('grupo', 'Grupo Test')
            ->set('codigo', '12.34.5')
            ->set('descripcion', 'Descripción de multa test')
            ->set('valor_ur', '5')
            
            // Guardar
            ->call('store')
            ->assertHasNoErrors()
            ->assertSet('isOpen', false);

        $this->assertDatabaseHas('tes_multas_303_2023', [
            'grupo' => 'Grupo Test',
            'codigo' => '12.34.5',
            'descripcion' => 'Descripción de multa test',
            'valor_ur' => '5',
        ]);
    }

    /** @test */
    public function usuario_puede_editar_multa_303(): void
    {
        $user = $this->crearUsuarioConPermiso('operador_tesoreria');
        $this->actingAs($user, 'api');

        $multa = Multa303Model::create([
            'grupo' => 'Grupo Original',
            'codigo' => '1.1',
            'descripcion' => 'Original desc',
            'valor_ur' => '1',
        ]);

        Livewire::test(Multa303::class)
            ->call('edit', $multa->id)
            ->assertSet('isOpen', true)
            ->assertSet('isEdit', true)
            ->assertSet('grupo', 'Grupo Original')
            
            // Cambiar valores
            ->set('descripcion', 'Modified desc')
            ->set('valor_ur', '2.5')
            
            // Guardar
            ->call('store')
            ->assertHasNoErrors()
            ->assertSet('isOpen', false);

        $this->assertDatabaseHas('tes_multas_303_2023', [
            'id' => $multa->id,
            'grupo' => 'Grupo Original',
            'codigo' => '1.1',
            'descripcion' => 'Modified desc',
            'valor_ur' => '2.5',
        ]);
    }

    /** @test */
    public function usuario_puede_eliminar_multa_303(): void
    {
        $user = $this->crearUsuarioConPermiso('operador_tesoreria');
        $this->actingAs($user, 'api');

        $multa = Multa303Model::create([
            'grupo' => 'Grupo Test',
            'codigo' => '9.9',
            'descripcion' => 'Para eliminar',
            'valor_ur' => '10',
        ]);

        Livewire::test(Multa303::class)
            ->call('delete', $multa->id);

        // Debería estar borrada lógicamente (soft deleted)
        $this->assertSoftDeleted('tes_multas_303_2023', [
            'id' => $multa->id
        ]);
    }
}
