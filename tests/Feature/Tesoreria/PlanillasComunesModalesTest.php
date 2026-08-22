<?php

namespace Tests\Feature\Tesoreria;

use App\Livewire\Tesoreria\PlanillasComunes\Index as PlanillasComunesIndex;
use App\Models\Tesoreria\CajaConcepto;
use App\Models\Tesoreria\TesCfe;
use App\Models\Tesoreria\TesPlanillaComun;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PlanillasComunesModalesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'tesoreria.acceso', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'tesoreria.supervisar', 'guard_name' => 'web']);
    }

    #[Test]
    public function usuario_puede_abrir_y_cerrar_modal_ver_planilla_comun(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('tesoreria.acceso');
        $this->actingAs($user, 'web');

        $concepto = CajaConcepto::create([
            'caja_concepto' => 'Concepto Planilla Test',
            'permite_planilla' => true,
            'requiere_confirmacion' => false,
            'requiere_distribucion' => false,
            'requiere_institucion' => false,
        ]);

        $planilla = TesPlanillaComun::create([
            'fecha' => '2026-08-20',
            'numero' => 'PC-2026-001',
            'tes_caja_concepto_id' => $concepto->id,
            'confirmada' => true,
        ]);

        TesCfe::create([
            'documento_tipo' => 'e-Factura',
            'documento_serie' => 'A',
            'documento_numero' => '5001',
            'fecha' => '2026-08-20',
            'moneda' => 'UYU',
            'total_a_pagar' => 2500,
            'planilla_comun_id' => $planilla->id,
            'tes_caja_concepto_id' => $concepto->id,
        ]);

        Livewire::test(PlanillasComunesIndex::class)
            ->call('verPlanilla', $planilla->id)
            ->assertSet('mostrarModalPlanilla', true)
            ->assertSee('modalPlanilla')
            ->assertSee('Planilla PC-2026-001')
            ->assertSee('5001')
            ->call('cerrarModalPlanilla')
            ->assertSet('mostrarModalPlanilla', false)
            ->assertDontSee('modalPlanilla');
    }
}
