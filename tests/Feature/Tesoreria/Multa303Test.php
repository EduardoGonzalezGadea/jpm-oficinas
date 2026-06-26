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
    public function usuario_puede_listar_y_buscar_multas_303(): void
    {
        $user = $this->crearUsuarioConPermiso('tesoreria.acceso');
        $this->actingAs($user, 'web');

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

        Livewire::test(Multa303::class)
            ->assertSee('1.2.3')
            ->assertSee('Infracción de prueba uno')
            ->assertSee('4.5.6')
            ->assertSee('Otra infracción de prueba dos')
            ->set('search', '4.5.6')
            ->assertSee('Otra infracción de prueba dos')
            ->assertDontSee('Infracción de prueba uno');
    }

    /** @test */
    public function normalizacion_de_busqueda_convierte_comas_en_puntos(): void
    {
        $user = $this->crearUsuarioConPermiso('tesoreria.acceso');
        $this->actingAs($user, 'web');

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
        $user = $this->crearUsuarioConPermiso('tesoreria.acceso');
        $this->actingAs($user, 'web');

        Livewire::test(Multa303::class)
            ->call('create')
            ->assertSet('isOpen', true)
            ->assertSet('isEdit', false)
            ->set('grupo', 'Grupo Test')
            ->set('codigo', '12.34.5')
            ->set('descripcion', 'Descripción de multa test')
            ->set('valor_ur', '5')
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
        $user = $this->crearUsuarioConPermiso('tesoreria.acceso');
        $this->actingAs($user, 'web');

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
            ->set('descripcion', 'Modified desc')
            ->set('valor_ur', '2.5')
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
        $user = $this->crearUsuarioConPermiso('tesoreria.acceso');
        $this->actingAs($user, 'web');

        $multa = Multa303Model::create([
            'grupo' => 'Grupo Test',
            'codigo' => '9.9',
            'descripcion' => 'Para eliminar',
            'valor_ur' => '10',
        ]);

        Livewire::test(Multa303::class)
            ->call('delete', $multa->id);

        $this->assertSoftDeleted('tes_multas_303_2023', [
            'id' => $multa->id
        ]);
    }
}
