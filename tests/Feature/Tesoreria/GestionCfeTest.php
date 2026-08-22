<?php

namespace Tests\Feature\Tesoreria;

use App\Models\Tesoreria\CajaConcepto;
use App\Models\Tesoreria\SiifDistribucion;
use App\Models\Tesoreria\SiifDistribucionDependencia;
use App\Models\Tesoreria\SiifDistribucionTipo;
use App\Models\Tesoreria\TesCfe;
use App\Models\Tesoreria\TesPlanillaEr;
use App\Models\User;
use Database\Factories\Tesoreria\TesCfeFactory;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
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

        $permisos = [
            'tesoreria.cfe.ver', 'tesoreria.supervisar', 'tesoreria.acceso',
            'asesoria_contable.acceso', 'usuarios.ver', 'sistema.acceso.administrador',
            'sistema.auditoria', 'sistema.backups',
        ];

        foreach ($permisos as $permiso) {
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
        $cfes = TesCfe::factory()->count(3)->create([
            'tes_caja_concepto_id' => $this->concepto->id,
        ]);

        Livewire::test(\App\Livewire\Tesoreria\GestionCfe\Index::class)
            ->assertSee($cfes->first()->documento_numero);
    }

    /** @test */
    public function can_open_new_cfe_modal(): void
    {
        Livewire::test(\App\Livewire\Tesoreria\GestionCfe\Index::class)
            ->call('nuevoCfe')
            ->assertSet('mostrarModalNuevo', true);
    }

    /** @test */
    public function can_create_cfe_manually(): void
    {
        Livewire::test(\App\Livewire\Tesoreria\GestionCfe\Index::class)
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
            ->assertDispatched('swal:toast-success');

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
            'documento_serie' => null,
            'documento_numero' => 'DUP001',
        ]);

        Livewire::test(\App\Livewire\Tesoreria\GestionCfe\Index::class)
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
            ->assertDispatched('swal:toast-error');
    }

    /** @test */
    public function can_open_edit_modal(): void
    {
        $cfe = TesCfe::factory()->create([
            'tes_caja_concepto_id' => $this->concepto->id,
        ]);

        Livewire::test(\App\Livewire\Tesoreria\GestionCfe\Index::class)
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

        Livewire::test(\App\Livewire\Tesoreria\GestionCfe\Index::class)
            ->call('editarCfe', $cfe->id)
            ->set('editCajaConceptoSeleccionado', $concepto2->id)
            ->set('editSiifDependenciaSeleccionado', $dependencia2->id)
            ->call('guardarEdicion')
            ->assertDispatched('swal:toast-success');

        $this->assertDatabaseHas('tes_cfes', [
            'id' => $cfe->id,
            'tes_caja_concepto_id' => $concepto2->id,
        ]);
    }

    /** @test */
    public function can_delete_cfe(): void
    {
        $cfe = TesCfe::factory()->create();

        Livewire::test(\App\Livewire\Tesoreria\GestionCfe\Index::class)
            ->call('borrarCfe', $cfe->id)
            ->assertDispatched('swal:toast-success');

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

        Livewire::test(\App\Livewire\Tesoreria\GestionCfe\Index::class)
            ->call('borrarCfe', $cfe->id)
            ->assertDispatched('swal:toast-error');

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

        Livewire::test(\App\Livewire\Tesoreria\GestionCfe\Index::class)
            ->set('filtroAno', 0)
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

        Livewire::test(\App\Livewire\Tesoreria\GestionCfe\Index::class)
            ->set('filtroAno', 0)
            ->set('filtroConcepto', $conceptoFiltro->id)
            ->assertSee('FILTRO001')
            ->assertDontSee('NOFILTRO');
    }

    /** @test */
    public function muestra_columna_conf_cuando_hay_cfe_con_concepto_que_requiere_confirmacion(): void
    {
        $conceptoConfirmable = CajaConcepto::create([
            'caja_concepto' => 'Concepto Confirmable',
            'requiere_distribucion' => false,
            'requiere_confirmacion' => true,
            'siif_distribucion_tipo_id' => $this->tipo->id,
        ]);

        TesCfe::factory()->create([
            'tes_caja_concepto_id' => $conceptoConfirmable->id,
            'documento_numero' => 'CONF001',
        ]);

        Livewire::test(\App\Livewire\Tesoreria\GestionCfe\Index::class)
            ->set('filtroConcepto', $conceptoConfirmable->id)
            ->assertSee('CONF001')
            ->assertSee('CONF.');
    }

    /** @test */
    public function no_muestra_columna_conf_sin_concepto_filtrado_aunque_haya_cfe_confirmable(): void
    {
        $conceptoConfirmable = CajaConcepto::create([
            'caja_concepto' => 'Concepto Confirmable',
            'requiere_distribucion' => false,
            'requiere_confirmacion' => true,
            'siif_distribucion_tipo_id' => $this->tipo->id,
        ]);

        TesCfe::factory()->create([
            'tes_caja_concepto_id' => $conceptoConfirmable->id,
            'documento_numero' => 'CONF001',
        ]);

        Livewire::test(\App\Livewire\Tesoreria\GestionCfe\Index::class)
            ->assertSee('CONF001')
            ->assertDontSee('CONF.');
    }

    /** @test */
    public function no_muestra_columna_conf_sin_cfe_con_concepto_que_requiera_confirmacion(): void
    {
        TesCfe::factory()->create([
            'tes_caja_concepto_id' => $this->concepto->id,
            'documento_numero' => 'SINCONF',
        ]);

        Livewire::test(\App\Livewire\Tesoreria\GestionCfe\Index::class)
            ->assertSee('SINCONF')
            ->assertDontSee('CONF.');
    }

    /** @test */
    public function toggle_confirmado_alterna_items_del_cfe(): void
    {
        $conceptoConfirmable = CajaConcepto::create([
            'caja_concepto' => 'Concepto Confirmable',
            'requiere_distribucion' => false,
            'requiere_confirmacion' => true,
            'siif_distribucion_tipo_id' => $this->tipo->id,
        ]);

        $cfe = TesCfe::factory()->create([
            'tes_caja_concepto_id' => $conceptoConfirmable->id,
        ]);
        $cfe->items()->create(['detalle' => 'Item 1', 'importe' => 100]);

        Livewire::test(\App\Livewire\Tesoreria\GestionCfe\Index::class)
            ->call('toggleConfirmado', $cfe->id)
            ->assertDispatched('swal:toast-success');

        $this->assertDatabaseHas('tes_cfe_items', [
            'tes_cfe_id' => $cfe->id,
            'confirmado' => 1,
        ]);
    }

    /** @test */
    public function toggle_confirmado_rechazado_cuando_concepto_no_requiere_confirmacion(): void
    {
        $cfe = TesCfe::factory()->create([
            'tes_caja_concepto_id' => $this->concepto->id,
        ]);
        $cfe->items()->create(['detalle' => 'Item 1', 'importe' => 100]);

        Livewire::test(\App\Livewire\Tesoreria\GestionCfe\Index::class)
            ->call('toggleConfirmado', $cfe->id)
            ->assertStatus(403);

        $this->assertDatabaseHas('tes_cfe_items', [
            'tes_cfe_id' => $cfe->id,
            'confirmado' => 0,
        ]);
    }

    /** @test */
    public function multas_con_distribucion_soa_emite_advertencia_de_concepto_por_monto(): void
    {
        $tipoMultas = SiifDistribucionTipo::create(['tipo' => 'Multas Tipo']);
        $conceptoMultas = CajaConcepto::create([
            'caja_concepto' => 'MULTAS DE TRÁNSITO',
            'requiere_distribucion' => true,
            'requiere_confirmacion' => false,
            'siif_distribucion_tipo_id' => $tipoMultas->id,
        ]);

        $soa = SiifDistribucion::create([
            'tipo_id' => $tipoMultas->id,
            'dependencia_id' => $this->dependencia->id,
            'concepto' => 'Multa por circular sin seguro obligatorio automotor (SOA)',
            'distribucion' => 'Multa por circular sin seguro obligatorio automotor (SOA)',
            'porcentaje' => 100,
        ]);

        Livewire::test(\App\Livewire\Tesoreria\GestionCfe\Index::class)
            ->call('nuevoCfe')
            ->set('nuevoDocumentoTipo', 'E-Ticket')
            ->set('nuevoDocumentoNumero', 'SOA001')
            ->set('nuevoFecha', now()->format('Y-m-d'))
            ->set('nuevoReceptorNombre', 'Test SOA')
            ->set('nuevoCajaConceptoSeleccionado', $conceptoMultas->id)
            ->set('nuevoSiifDependenciaSeleccionado', $this->dependencia->id)
            ->set('nuevoItems.0.detalle', 'MULTA CARECER DE SOA')
            ->set('nuevoItems.0.importe', 1500)
            ->set('nuevoItemDistribuciones.0', $soa->id)
            ->call('guardarNuevo')
            ->assertDispatched('swal:confirmar-concepto-nuevo-nuevo');

        $this->assertDatabaseMissing('tes_cfes', ['documento_numero' => 'SOA001']);
    }

    /** @test */
    public function multas_sin_distribucion_soa_no_emite_advertencia_de_concepto_por_monto(): void
    {
        $tipoMultas = SiifDistribucionTipo::create(['tipo' => 'Multas Tipo']);
        $conceptoMultas = CajaConcepto::create([
            'caja_concepto' => 'MULTAS DE TRÁNSITO',
            'requiere_distribucion' => true,
            'requiere_confirmacion' => false,
            'siif_distribucion_tipo_id' => $tipoMultas->id,
        ]);

        $noSoa = SiifDistribucion::create([
            'tipo_id' => $tipoMultas->id,
            'dependencia_id' => $this->dependencia->id,
            'concepto' => 'Multa por otra infracción',
            'distribucion' => 'Multa por otra infracción',
            'porcentaje' => 100,
        ]);

        Livewire::test(\App\Livewire\Tesoreria\GestionCfe\Index::class)
            ->call('nuevoCfe')
            ->set('nuevoDocumentoTipo', 'E-Ticket')
            ->set('nuevoDocumentoNumero', 'NOSOA001')
            ->set('nuevoFecha', now()->format('Y-m-d'))
            ->set('nuevoReceptorNombre', 'Test No SOA')
            ->set('nuevoCajaConceptoSeleccionado', $conceptoMultas->id)
            ->set('nuevoSiifDependenciaSeleccionado', $this->dependencia->id)
            ->set('nuevoItems.0.detalle', 'MULTA OTRA INFRACCION')
            ->set('nuevoItems.0.importe', 1500)
            ->set('nuevoItemDistribuciones.0', $noSoa->id)
            ->call('guardarNuevo')
            ->assertNotDispatched('swal:confirmar-concepto-nuevo-nuevo')
            ->assertDispatched('swal:toast-success');

        $this->assertDatabaseHas('tes_cfes', ['documento_numero' => 'NOSOA001']);
    }

    /** @test */
    public function confirmar_carga_de_multas_soa_emite_advertencia_de_concepto(): void
    {
        $tipoMultas = SiifDistribucionTipo::create(['tipo' => 'Multas Tipo']);
        $conceptoMultas = CajaConcepto::create([
            'caja_concepto' => 'MULTAS DE TRÁNSITO',
            'requiere_distribucion' => true,
            'requiere_confirmacion' => false,
            'siif_distribucion_tipo_id' => $tipoMultas->id,
        ]);

        $soa = SiifDistribucion::create([
            'tipo_id' => $tipoMultas->id,
            'dependencia_id' => $this->dependencia->id,
            'concepto' => 'Multa por circular sin seguro obligatorio automotor (SOA)',
            'distribucion' => 'Multa por circular sin seguro obligatorio automotor (SOA)',
            'porcentaje' => 100,
        ]);

        Livewire::test(\App\Livewire\Tesoreria\GestionCfe\Index::class)
            ->set('cajaConceptoSeleccionado', $conceptoMultas->id)
            ->set('siifDependenciaSeleccionado', $this->dependencia->id)
            ->set('datosExtraidos', [
                'documento_tipo' => 'E-Ticket',
                'documento_numero' => 'SOA-PDF',
                'items' => [
                    ['detalle' => 'MULTA CARECER DE SOA', 'importe' => 1500],
                ],
                'medios_pago' => [],
                'referencias' => '',
                'adenda' => '',
            ])
            ->set('itemDistribuciones', ['0' => $soa->id])
            ->call('confirmarCarga')
            ->assertDispatched('swal:confirmar-concepto-nuevo');
    }

    /** @test */
    public function confirmar_carga_sin_concepto_emite_toast_error(): void
    {
        Livewire::test(\App\Livewire\Tesoreria\GestionCfe\Index::class)
            ->set('cajaConceptoSeleccionado', null)
            ->set('datosExtraidos', [
                'documento_tipo' => 'E-Ticket',
                'documento_numero' => '12345',
                'items' => [['detalle' => 'Item 1', 'importe' => 100]],
            ])
            ->call('confirmarCarga')
            ->assertDispatched('swal:toast-error');
    }

    /** @test */
    public function confirmar_carga_sin_institucion_requerida_emite_toast_error(): void
    {
        $conceptoConInst = CajaConcepto::create([
            'caja_concepto' => 'EVENTUALES CON INSTITUCION',
            'requiere_institucion' => true,
            'requiere_distribucion' => false,
        ]);

        Livewire::test(\App\Livewire\Tesoreria\GestionCfe\Index::class)
            ->set('cajaConceptoSeleccionado', $conceptoConInst->id)
            ->set('confirmacionInstitucionSeleccionada', null)
            ->set('datosExtraidos', [
                'documento_tipo' => 'E-Ticket',
                'documento_numero' => '12345',
                'items' => [['detalle' => 'Item 1', 'importe' => 100]],
            ])
            ->call('confirmarCarga')
            ->assertDispatched('swal:toast-error');
    }

    /** @test */
    public function confirmar_carga_con_advertencias_multiples_se_ejecuta_secuencialmente(): void
    {
        $tipoThata = \App\Models\Tesoreria\SiifDistribucionTipo::create(['tipo' => 'THATA_SEC']);
        $conceptoThata = CajaConcepto::create([
            'caja_concepto' => 'TÍTULO DE HABILITACIÓN Y TENENCIA DE ARMAS (THATA)',
            'requiere_distribucion' => false,
            'siif_distribucion_tipo_id' => $tipoThata->id,
        ]);

        // CFE existente con Orden de Cobro 998877
        TesCfe::create([
            'documento_tipo' => 'E-Ticket',
            'documento_serie' => 'A',
            'documento_numero' => '1001',
            'fecha' => '2026-08-01',
            'total_a_pagar' => 500,
            'referencias' => 'O/C 998877',
            'moneda' => 'UYU',
        ]);

        // Nuevo CFE que tiene la misma OC (998877) Y un monto nuevo (7777)
        $component = Livewire::test(\App\Livewire\Tesoreria\GestionCfe\Index::class)
            ->set('cajaConceptoSeleccionado', $conceptoThata->id)
            ->set('siifDependenciaSeleccionado', $this->dependencia->id)
            ->set('datosExtraidos', [
                'documento_tipo' => 'E-Ticket',
                'documento_numero' => '2002',
                'items' => [
                    ['detalle' => 'TÍTULO DE HABILITACIÓN', 'importe' => 7777],
                ],
                'medios_pago' => [],
                'referencias' => 'Orden de Cobro 998877',
                'adenda' => '',
            ])
            ->set('itemDistribuciones', []);

        // 1. Primera llamada: debe advertir sobre la orden de cobro duplicada
        $component->call('confirmarCarga')
            ->assertDispatched('swal:confirmar-orden-cobro-duplicada');

        // 2. Al aceptar la OC duplicada (ignorar duplicados): debe advertir sobre el concepto/monto no habitual
        $component->call('confirmarCargaIgnorarDuplicados')
            ->assertDispatched('swal:confirmar-concepto-nuevo');
    }
}
