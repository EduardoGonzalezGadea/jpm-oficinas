<?php

namespace Tests\Feature\Tesoreria;

use App\Livewire\AsesoriaContable\EstadosRecaudacion\Index as AsesoriaIndex;
use App\Livewire\Tesoreria\EstadosRecaudacion\Index as TesoreriaIndex;
use App\Models\Tesoreria\SiifDistribucion;
use App\Models\Tesoreria\SiifDistribucionDependencia;
use App\Models\Tesoreria\SiifDistribucionTipo;
use App\Models\Tesoreria\TesCfe;
use App\Models\Tesoreria\TesCfeItem;
use App\Models\Tesoreria\TesPlanillaEr;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class EstadosRecaudacionModalesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $permisos = [
            'tesoreria.acceso', 'tesoreria.supervisar',
            'asesoria_contable.acceso', 'asesoria_contable.supervisar',
        ];

        foreach ($permisos as $permiso) {
            Permission::firstOrCreate(['name' => $permiso, 'guard_name' => 'web']);
        }
    }

    private function crearPlanillaPrueba(): TesPlanillaEr
    {
        $tipo = SiifDistribucionTipo::create(['tipo' => 'Tipo Test', 'codigo' => 'TT']);
        $dep = SiifDistribucionDependencia::create(['dependencia' => 'Dep Test', 'abreviatura' => 'DT']);

        $planilla = TesPlanillaEr::create([
            'fecha' => '2026-08-20',
            'numero' => '20-08-2026-1',
            'tipo_id' => $tipo->id,
            'dependencia_id' => $dep->id,
            'turno' => 'Matutino',
            'er_numero' => 'ER-100',
            'egresos_numero' => 'EG-200',
            'ingresos_numero' => 'ING-300',
            'observaciones' => 'Observacion de prueba',
            'transferencia_fecha' => '2026-08-20',
            'transferencia_confirmacion' => 'CONF-400',
            'confirmada' => true,
        ]);

        $dist = SiifDistribucion::create([
            'tipo_id' => $tipo->id,
            'dependencia_id' => $dep->id,
            'concepto' => 'Concepto Test',
            'porcentaje' => 100,
            'inciso' => '04',
            'unidad_ejecutora' => '004',
            'financiacion' => '1.1',
        ]);

        $cfe = TesCfe::create([
            'documento_tipo' => 'e-Factura',
            'documento_serie' => 'A',
            'documento_numero' => '1001',
            'fecha' => '2026-08-20',
            'moneda' => 'UYU',
            'total_a_pagar' => 1000,
        ]);

        TesCfeItem::create([
            'tes_cfe_id' => $cfe->id,
            'planilla_er_id' => $planilla->id,
            'siif_distribucion_id' => $dist->id,
            'detalle' => 'Item de prueba',
            'cantidad' => 1,
            'precio' => 1000,
            'importe' => 1000,
        ]);

        return $planilla;
    }

    #[Test]
    public function asesoria_contable_puede_abrir_y_cerrar_modal_detalles(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('asesoria_contable.acceso');
        $this->actingAs($user, 'web');

        $planilla = $this->crearPlanillaPrueba();

        Livewire::test(AsesoriaIndex::class)
            ->call('verDetalles', $planilla->id)
            ->assertSet('mostrarModalDetalles', true)
            ->assertSee('modalDetallesPlanilla')
            ->assertSee('Detalles — Planilla ' . $planilla->numero)
            ->assertSee('Item de prueba')
            ->call('cerrarModalDetalles')
            ->assertSet('mostrarModalDetalles', false)
            ->assertDontSee('modalDetallesPlanilla');
    }

    #[Test]
    public function asesoria_contable_puede_abrir_y_cerrar_modal_planilla(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('asesoria_contable.acceso');
        $this->actingAs($user, 'web');

        $planilla = $this->crearPlanillaPrueba();

        Livewire::test(AsesoriaIndex::class)
            ->call('verPlanilla', $planilla->id)
            ->assertSet('mostrarModalPlanilla', true)
            ->assertSee('modalPlanilla')
            ->assertSee('Observacion de prueba')
            ->call('cerrarModalPlanilla')
            ->assertSet('mostrarModalPlanilla', false)
            ->assertDontSee('modalPlanilla');
    }

    #[Test]
    public function tesoreria_puede_abrir_y_cerrar_modal_detalles(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('tesoreria.acceso');
        $this->actingAs($user, 'web');

        $planilla = $this->crearPlanillaPrueba();

        Livewire::test(TesoreriaIndex::class)
            ->call('verDetalles', $planilla->id)
            ->assertSet('mostrarModalDetalles', true)
            ->assertSee('modalDetallesPlanilla')
            ->assertSee('Detalles — Planilla ' . $planilla->numero)
            ->assertSee('Item de prueba')
            ->call('cerrarModalDetalles')
            ->assertSet('mostrarModalDetalles', false)
            ->assertDontSee('modalDetallesPlanilla');
    }

    #[Test]
    public function tesoreria_puede_abrir_y_cerrar_modal_planilla(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('tesoreria.acceso');
        $this->actingAs($user, 'web');

        $planilla = $this->crearPlanillaPrueba();

        Livewire::test(TesoreriaIndex::class)
            ->call('verPlanilla', $planilla->id)
            ->assertSet('mostrarModalPlanilla', true)
            ->assertSee('modalPlanilla')
            ->assertSee('Observacion de prueba')
            ->call('cerrarModalPlanilla')
            ->assertSet('mostrarModalPlanilla', false)
            ->assertDontSee('modalPlanilla');
    }

    #[Test]
    public function tesoreria_puede_abrir_editar_y_guardar_planilla(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('tesoreria.acceso');
        $this->actingAs($user, 'web');

        $planilla = $this->crearPlanillaPrueba();

        Livewire::test(TesoreriaIndex::class)
            ->call('editarPlanilla', $planilla->id)
            ->assertSet('mostrarModalEditar', true)
            ->assertSet('edit_observaciones', 'Observacion de prueba')
            ->set('edit_observaciones', 'Observacion editada')
            ->call('guardarPlanilla')
            ->assertSet('mostrarModalEditar', false)
            ->assertDontSee('modalEditarPlanilla');

        $this->assertEquals('Observacion editada', $planilla->fresh()->observaciones);
    }
}
