<?php

namespace Tests\Feature\Tesoreria;

use App\Models\Tesoreria\CajaConcepto;
use App\Models\Tesoreria\SiifDistribucionDependencia;
use App\Models\Tesoreria\SiifDistribucionTipo;
use App\Models\Tesoreria\TesCfe;
use App\Models\Tesoreria\TesPlanillaEr;
use App\Models\User;
use Database\Factories\Tesoreria\TesCfeFactory;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

class GestionCfeTest extends TestCase
{
    use DatabaseTransactions;

    private User $user;
    private CajaConcepto $concepto;
    private SiifDistribucionDependencia $dependencia;
    private TesPlanillaEr $planilla;
    private SiifDistribucionTipo $tipo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        $this->tipo = SiifDistribucionTipo::create(['tipo' => 'Test Tipo']);
        $this->dependencia = SiifDistribucionDependencia::create([
            'dependencia' => 'Test Dependencia',
            'abreviatura' => 'TEST',
        ]);
        $this->planilla = TesPlanillaEr::create([
            'fecha' => now(),
            'numero' => 'PLANILLA001',
            'tipo_id' => $this->tipo->id,
            'dependencia_id' => $this->dependencia->id,
        ]);
        $this->concepto = CajaConcepto::create([
            'caja_concepto' => 'Test Concepto',
            'requiere_distribucion' => false,
            'requiere_confirmacion' => false,
            'siif_distribucion_tipo_id' => $this->tipo->id,
        ]);
    }

    /** @test */
    public function can_see_paginated_list_of_cfes(): void
    {
        TesCfe::factory()->count(3)->create([
            'tes_caja_concepto_id' => $this->concepto->id,
        ]);

        Livewire::test(\App\Http\Livewire\Tesoreria\GestionCfe\Index::class)
            ->assertSee('E-Factura Cobranza');
    }

    /** @test */
    public function can_open_new_cfe_modal(): void
    {
        Livewire::test(\App\Http\Livewire\Tesoreria\GestionCfe\Index::class)
            ->call('nuevoCfe')
            ->assertSet('mostrarModalNuevo', true);
    }

    /** @test */
    public function can_create_cfe_manually(): void
    {
        Livewire::test(\App\Http\Livewire\Tesoreria\GestionCfe\Index::class)
            ->call('nuevoCfe')
            ->set('nuevoDocumentoTipo', 'E-Factura Cobranza')
            ->set('nuevoDocumentoSerie', 'A')
            ->set('nuevoDocumentoNumero', 'TEST001')
            ->set('nuevoFecha', now()->format('Y-m-d'))
            ->set('nuevoReceptorNombre', 'Test Receptor')
            ->set('nuevoCajaConceptoSeleccionado', $this->concepto->id)
            ->set('nuevoSiifDependenciaSeleccionado', $this->dependencia->id)
            ->set('nuevoItems.0.detalle', 'Item de prueba')
            ->set('nuevoItems.0.importe', 1500)
            ->call('guardarNuevo')
            ->assertDispatchedBrowserEvent('swal:modal');

        $this->assertDatabaseHas('tes_cfes', [
            'documento_numero' => 'TEST001',
            'receptor_nombre_denominacion' => 'Test Receptor',
        ]);
    }

    /** @test */
    public function rejects_duplicate_document_number_on_manual_creation(): void
    {
        TesCfe::factory()->create([
            'documento_tipo' => 'E-Factura Cobranza',
            'documento_numero' => 'DUP001',
        ]);

        Livewire::test(\App\Http\Livewire\Tesoreria\GestionCfe\Index::class)
            ->call('nuevoCfe')
            ->set('nuevoDocumentoTipo', 'E-Factura Cobranza')
            ->set('nuevoDocumentoNumero', 'DUP001')
            ->set('nuevoFecha', now()->format('Y-m-d'))
            ->set('nuevoReceptorNombre', 'Test')
            ->set('nuevoCajaConceptoSeleccionado', $this->concepto->id)
            ->set('nuevoSiifDependenciaSeleccionado', $this->dependencia->id)
            ->set('nuevoItems.0.detalle', 'Item')
            ->set('nuevoItems.0.importe', 100)
            ->call('guardarNuevo')
            ->assertDispatchedBrowserEvent('swal:toast-error');
    }

    /** @test */
    public function can_open_edit_modal(): void
    {
        $cfe = TesCfe::factory()->create([
            'tes_caja_concepto_id' => $this->concepto->id,
        ]);

        Livewire::test(\App\Http\Livewire\Tesoreria\GestionCfe\Index::class)
            ->call('editarCfe', $cfe->id)
            ->assertSet('mostrarModalEditar', true)
            ->assertSet('cfeEditarId', $cfe->id);
    }

    /** @test */
    public function can_edit_cfe(): void
    {
        $cfe = TesCfe::factory()->create([
            'tes_caja_concepto_id' => $this->concepto->id,
        ]);

        $tipo2 = SiifDistribucionTipo::create(['tipo' => 'Otro Tipo']);
        $concepto2 = CajaConcepto::create([
            'caja_concepto' => 'Otro Concepto',
            'requiere_distribucion' => false,
            'requiere_confirmacion' => false,
            'siif_distribucion_tipo_id' => $tipo2->id,
        ]);
        $dependencia2 = SiifDistribucionDependencia::create([
            'dependencia' => 'Otra Dependencia',
            'abreviatura' => 'OTRA',
        ]);

        Livewire::test(\App\Http\Livewire\Tesoreria\GestionCfe\Index::class)
            ->call('editarCfe', $cfe->id)
            ->set('editCajaConceptoSeleccionado', $concepto2->id)
            ->set('editSiifDependenciaSeleccionado', $dependencia2->id)
            ->call('guardarEdicion')
            ->assertDispatchedBrowserEvent('swal:modal');

        $this->assertDatabaseHas('tes_cfes', [
            'id' => $cfe->id,
            'tes_caja_concepto_id' => $concepto2->id,
        ]);
    }

    /** @test */
    public function can_delete_cfe(): void
    {
        $cfe = TesCfe::factory()->create();

        Livewire::test(\App\Http\Livewire\Tesoreria\GestionCfe\Index::class)
            ->call('borrarCfe', $cfe->id)
            ->assertDispatchedBrowserEvent('swal:modal');

        $this->assertSoftDeleted('tes_cfes', ['id' => $cfe->id]);
    }

    /** @test */
    public function rejects_delete_when_item_in_planilla(): void
    {
        $cfe = TesCfe::factory()->create();
        $cfe->items()->create([
            'detalle' => 'En planilla',
            'importe' => 100,
            'planilla_er_id' => $this->planilla->id,
        ]);

        Livewire::test(\App\Http\Livewire\Tesoreria\GestionCfe\Index::class)
            ->call('borrarCfe', $cfe->id)
            ->assertDispatchedBrowserEvent('swal:toast-error');

        $this->assertDatabaseHas('tes_cfes', ['id' => $cfe->id, 'deleted_at' => null]);
    }

    /** @test */
    public function can_search_cfes(): void
    {
        TesCfe::factory()->create([
            'documento_numero' => 'BUSQUEDA001',
            'tes_caja_concepto_id' => $this->concepto->id,
        ]);
        TesCfe::factory()->create([
            'documento_numero' => 'OTRO002',
            'tes_caja_concepto_id' => $this->concepto->id,
        ]);

        Livewire::test(\App\Http\Livewire\Tesoreria\GestionCfe\Index::class)
            ->set('search', 'BUSQUEDA001')
            ->assertSee('BUSQUEDA001')
            ->assertDontSee('OTRO002');
    }

    /** @test */
    public function can_filter_by_concepto(): void
    {
        $conceptoFiltro = CajaConcepto::create([
            'caja_concepto' => 'Concepto Filtro',
            'requiere_distribucion' => false,
            'requiere_confirmacion' => false,
        ]);

        TesCfe::factory()->create([
            'tes_caja_concepto_id' => $conceptoFiltro->id,
            'documento_numero' => 'FILTRO001',
        ]);
        TesCfe::factory()->create([
            'tes_caja_concepto_id' => $this->concepto->id,
            'documento_numero' => 'NOFILTRO',
        ]);

        Livewire::test(\App\Http\Livewire\Tesoreria\GestionCfe\Index::class)
            ->set('filtroConcepto', $conceptoFiltro->id)
            ->assertSee('FILTRO001')
            ->assertDontSee('NOFILTRO');
    }
}
