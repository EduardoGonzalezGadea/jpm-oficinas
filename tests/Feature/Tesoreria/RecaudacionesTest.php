<?php

namespace Tests\Feature\Tesoreria;

use App\Livewire\Tesoreria\Recaudaciones\Index;
use App\Models\Tesoreria\CajaConcepto;
use App\Models\Tesoreria\SiifDistribucionDependencia;
use App\Models\Tesoreria\SiifDistribucionTipo;
use App\Models\Tesoreria\TesCfe;
use App\Models\User;
use Database\Factories\Tesoreria\TesCfeItemFactory;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class RecaudacionesTest extends TestCase
{
    use DatabaseTransactions;

    private User $user;
    private SiifDistribucionTipo $tipo;
    private SiifDistribucionDependencia $dependencia;
    private CajaConcepto $concepto;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['tesoreria.cfe.ver', 'tesoreria.supervisar'] as $permiso) {
            Permission::firstOrCreate(['name' => $permiso, 'guard_name' => 'web']);
        }

        $this->user = User::factory()->create();
        $this->user->givePermissionTo(['tesoreria.cfe.ver', 'tesoreria.supervisar']);
        $this->actingAs($this->user);

        $this->tipo = SiifDistribucionTipo::create(['tipo' => 'Test Tipo']);
        $this->dependencia = SiifDistribucionDependencia::create([
            'dependencia' => 'Test Dependencia',
            'abreviatura' => 'TEST',
        ]);
        $this->concepto = CajaConcepto::create([
            'caja_concepto' => 'Test Concepto',
            'requiere_distribucion' => false,
            'requiere_confirmacion' => true,
            'siif_distribucion_tipo_id' => $this->tipo->id,
        ]);
    }

    private function crearCfeConItems(array $cfeOverrides = [], array $itemOverrides = []): TesCfe
    {
        $cfe = TesCfe::factory()
            ->conMediosPago(1)
            ->create(array_merge([
                'tes_caja_concepto_id' => $this->concepto->id,
                'siif_distribucion_dependencia_id' => $this->dependencia->id,
            ], $cfeOverrides));

        TesCfeItemFactory::new()->count(2)->create(
            array_merge(['tes_cfe_id' => $cfe->id], $itemOverrides)
        );

        return $cfe;
    }

    /** @test */
    public function muestra_columna_conf_solo_cuando_el_concepto_seleccionado_requiere_confirmacion(): void
    {
        $cfe = $this->crearCfeConItems();

        Livewire::test(Index::class)
            ->set('concepto_id', $this->concepto->id)
            ->assertSee('CONF.')
            ->assertSee($cfe->documento_numero);

        $conceptoSinConfirmacion = CajaConcepto::create([
            'caja_concepto' => 'Sin Confirmacion',
            'requiere_distribucion' => false,
            'requiere_confirmacion' => false,
            'siif_distribucion_tipo_id' => $this->tipo->id,
        ]);

        $cfe2 = TesCfe::factory()
            ->conMediosPago(1)
            ->create([
                'tes_caja_concepto_id' => $conceptoSinConfirmacion->id,
                'siif_distribucion_dependencia_id' => $this->dependencia->id,
            ]);
        TesCfeItemFactory::new()->count(1)->create(['tes_cfe_id' => $cfe2->id]);

        Livewire::test(Index::class)
            ->set('concepto_id', $conceptoSinConfirmacion->id)
            ->assertDontSee('CONF.');
    }

    /** @test */
    public function no_muestra_columna_conf_sin_concepto_filtrado_aunque_haya_cfe_confirmable(): void
    {
        $cfe = $this->crearCfeConItems();

        Livewire::test(Index::class)
            ->assertDontSee('CONF.')
            ->assertSee($cfe->documento_numero);
    }

    /** @test */
    public function no_muestra_columna_conf_cuando_ningun_cfe_visible_requiere_confirmacion(): void
    {
        $conceptoSinConfirmacion = CajaConcepto::create([
            'caja_concepto' => 'Sin Confirmacion',
            'requiere_distribucion' => false,
            'requiere_confirmacion' => false,
            'siif_distribucion_tipo_id' => $this->tipo->id,
        ]);

        $cfe = TesCfe::factory()
            ->conMediosPago(1)
            ->create([
                'tes_caja_concepto_id' => $conceptoSinConfirmacion->id,
                'siif_distribucion_dependencia_id' => $this->dependencia->id,
            ]);
        TesCfeItemFactory::new()->count(1)->create(['tes_cfe_id' => $cfe->id]);

        Livewire::test(Index::class)
            ->assertDontSee('CONF.');
    }

    /** @test */
    public function toggle_confirmado_alterna_todos_los_items_del_cfe(): void
    {
        $cfe = $this->crearCfeConItems();

        Livewire::test(Index::class)
            ->set('concepto_id', $this->concepto->id)
            ->call('toggleConfirmado', $cfe->id);

        $this->assertDatabaseHas('tes_cfe_items', [
            'tes_cfe_id' => $cfe->id,
            'confirmado' => 1,
        ]);
        $this->assertSame(2, $cfe->items()->where('confirmado', true)->count());

        Livewire::test(Index::class)
            ->set('concepto_id', $this->concepto->id)
            ->call('toggleConfirmado', $cfe->id);

        $this->assertSame(0, $cfe->items()->where('confirmado', true)->count());
    }

    /** @test */
    public function toggle_confirmado_requiere_permiso_de_supervision(): void
    {
        $cfe = $this->crearCfeConItems();

        $userSinPermiso = User::factory()->create();
        $this->actingAs($userSinPermiso);

        Livewire::test(Index::class)
            ->set('concepto_id', $this->concepto->id)
            ->call('toggleConfirmado', $cfe->id)
            ->assertStatus(403);
    }

    /** @test */
    public function toggle_confirmado_requiere_concepto_que_requiera_confirmacion(): void
    {
        $conceptoSinConfirmacion = CajaConcepto::create([
            'caja_concepto' => 'Sin Confirmacion',
            'requiere_distribucion' => false,
            'requiere_confirmacion' => false,
            'siif_distribucion_tipo_id' => $this->tipo->id,
        ]);

        $cfe = TesCfe::factory()->create([
            'tes_caja_concepto_id' => $conceptoSinConfirmacion->id,
            'siif_distribucion_dependencia_id' => $this->dependencia->id,
        ]);
        TesCfeItemFactory::new()->count(1)->create(['tes_cfe_id' => $cfe->id]);

        Livewire::test(Index::class)
            ->set('concepto_id', $conceptoSinConfirmacion->id)
            ->call('toggleConfirmado', $cfe->id)
            ->assertStatus(403);
    }
}
