<?php

namespace Tests\Feature\Tesoreria\CajaDiaria;

use App\Livewire\Tesoreria\CajaDiaria\AperturaCierre;
use App\Models\User;
use App\Models\Tesoreria\TesDiscriminacionMonetaria;
use App\Models\Tesoreria\Cajas\CajaApertura;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AperturaCierreTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private TesDiscriminacionMonetaria $bil;
    private TesDiscriminacionMonetaria $moneda;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $this->bil = TesDiscriminacionMonetaria::create([
            'tipo' => 'Billetes',
            'valor' => 1000,
            'texto' => 'Billete de 1000',
            'activo' => true,
        ]);

        $this->moneda = TesDiscriminacionMonetaria::create([
            'tipo' => 'Monedas',
            'valor' => 1,
            'texto' => 'Moneda de 1',
            'activo' => true,
        ]);
    }

    public function setUpComponenteConDesgloseInicial()
    {
        $this->actingAs($this->user, 'web');

        return Livewire::test(AperturaCierre::class)
            ->assertHasNoErrors()
            ->assertSet('cajaAbierta', null)
            ->assertSet('total_efectivo', 0)
            ->assertSet('saldo_inicial', 0);
    }

    /** @test */
    public function valor_vacio_en_cantidad_no_genera_error_interno(): void
    {
        $c = $this->setUpComponenteConDesgloseInicial();

        $c->set('desglose.' . $this->bil->id . '.cantidad', '')
            ->assertHasNoErrors()
            ->assertHasNoErrors('desglose.*');
    }

    /** @test */
    public function valor_no_numerico_en_cantidad_no_genera_error_interno(): void
    {
        $c = $this->setUpComponenteConDesgloseInicial();

        $c->set('desglose.' . $this->bil->id . '.cantidad', 'abc')
            ->assertHasNoErrors();
    }

    /** @test */
    public function valor_vacio_en_total_no_genera_error_interno(): void
    {
        $c = $this->setUpComponenteConDesgloseInicial();

        $c
            ->set('modo_calculo', 'total')
            ->set('desglose.' . $this->bil->id . '.total', '')
            ->assertHasNoErrors();
    }

    /** @test */
    public function monto_no_divisible_se_marca_como_invalido_sin_error_interno(): void
    {
        $c = $this->setUpComponenteConDesgloseInicial();

        $c
            ->set('modo_calculo', 'total')
            ->set('desglose.' . $this->bil->id . '.total', '1500')
            ->assertHasNoErrors()
            ->assertDispatched('swal:toast:warning')
            ->assertSet('desglose_invalido', [(string) $this->bil->id]);
    }

    /** @test */
    public function la_advertencia_se_vuelve_a_disparar_si_lo_ingresado_sigue_siendo_invalido(): void
    {
        $c = $this->setUpComponenteConDesgloseInicial();

        $c
            ->set('modo_calculo', 'total')
            ->set('desglose.' . $this->bil->id . '.total', '1500')
            ->assertHasNoErrors()
            ->assertSet('desglose_invalido', [(string) $this->bil->id]);

        // El usuario cambia el valor pero sigue sin ser divisible exactamente
        $c->set('desglose.' . $this->bil->id . '.total', '2500')
            ->assertHasNoErrors()
            ->assertDispatched('swal:toast:warning')
            ->assertSet('desglose_invalido', [(string) $this->bil->id]);
    }

    /** @test */
    public function la_advertencia_incluye_titulo_y_mensaje(): void
    {
        $c = $this->setUpComponenteConDesgloseInicial();

        $c
            ->set('modo_calculo', 'total')
            ->set('desglose.' . $this->bil->id . '.total', '1500')
            ->assertHasNoErrors()
            ->assertDispatched('swal:toast:warning', function ($event, $params) {
                $data = is_array($params) && isset($params[0]) ? $params[0] : $params;
                return ($data['title'] ?? '') === 'Valor no exacto'
                    && str_contains($data['text'] ?? '', 'no es divisible');
            })
            ->assertDispatched('swal:toast:warning', function ($event, $params) {
                $data = is_array($params) && isset($params[0]) ? $params[0] : $params;
                return ($data['focoDenId'] ?? null) == $this->bil->id
                    && ($data['focoCampo'] ?? null) === 'total';
            });
    }

    /** @test */
    public function la_advertencia_indica_el_campo_de_cantidad_a_enfocar_en_modo_cantidad(): void
    {
        $c = $this->setUpComponenteConDesgloseInicial();

        $c
            ->set('modo_calculo', 'cantidad')
            ->set('desglose.' . $this->bil->id . '.cantidad', '1.5')
            ->assertHasNoErrors()
            ->assertSet('desglose_invalido', [(string) $this->bil->id])
            ->assertDispatched('swal:toast:warning', function ($event, $params) {
                $data = is_array($params) && isset($params[0]) ? $params[0] : $params;
                return ($data['focoDenId'] ?? null) == $this->bil->id
                    && ($data['focoCampo'] ?? null) === 'cantidad';
            });
    }

    /** @test */
    public function monto_divisible_recalcula_saldo_desglose_sin_error(): void
    {
        $c = $this->setUpComponenteConDesgloseInicial();

        $c
            ->set('modo_calculo', 'total')
            ->set('desglose.' . $this->bil->id . '.total', '2000')
            ->assertHasNoErrors()
            ->assertSet('saldo_inicial', 2000);
    }

    /** @test */
    public function monto_con_formato_texto_valido_tambien_recalcula(): void
    {
        $c = $this->setUpComponenteConDesgloseInicial();

        $c
            ->set('modo_calculo', 'total')
            ->set('desglose.' . $this->bil->id . '.total', '2000')
            ->assertHasNoErrors()
            ->assertSet('saldo_inicial', 2000);
    }

    /** @test */
    public function abrir_caja_crea_la_apertura_y_redirige_al_index(): void
    {
        $this->actingAs($this->user, 'web');

        $c = $this->setUpComponenteConDesgloseInicial()
            ->set('fecha_apertura', today()->format('Y-m-d'))
            ->set('saldo_inicial', 1000)
            ->set('modo_calculo', 'cantidad')
            ->set('desglose.' . $this->bil->id . '.cantidad', 1)
            ->set('desglose.' . $this->bil->id . '.total', 1000)
            ->call('abrirCaja')
            ->assertRedirect(route('tesoreria.caja-diaria.index'));

        $this->assertDatabaseHas('tes_cajas_aperturas', [
            'cajero_id' => $this->user->id,
            'fecha_apertura' => today()->format('Y-m-d'),
            'saldo_inicial' => '1000',
            'estado' => 'abierta',
        ]);

        $this->assertDatabaseHas('tes_cajas_desgloses', [
            'tes_discriminacion_monetaria_id' => $this->bil->id,
            'cantidad' => 1,
            'subtotal' => '1000',
            'tipo_referencia' => 'apertura',
        ]);
    }

    /** @test */
    public function abrir_caja_no_permite_abrir_dos_veces(): void
    {
        $this->actingAs($this->user, 'web');

        $this->setUpComponenteConDesgloseInicial();

        $c = Livewire::test(AperturaCierre::class)
            ->set('fecha_apertura', today()->format('Y-m-d'))
            ->set('saldo_inicial', 500)
            ->call('abrirCaja');

        // Segundo intento: ya hay una caja abierta para el mismo cajero
        Livewire::test(AperturaCierre::class)
            ->set('fecha_apertura', today()->format('Y-m-d'))
            ->set('saldo_inicial', 500)
            ->call('abrirCaja')
            ->assertDispatched('alert', function ($event, $params) {
                $data = is_array($params) && isset($params[0]) ? $params[0] : $params;
                return ($data['type'] ?? '') === 'error'
                    && str_contains($data['message'] ?? '', 'Ya tienes una caja abierta.');
            });

        $this->assertSame(
            1,
            CajaApertura::where('cajero_id', $this->user->id)
                ->where('estado', 'abierta')
                ->count()
        );
    }

    /** @test */
    public function al_cambiar_cantidad_recalcula_total_de_fila_y_saldo_inicial(): void
    {
        $c = $this->setUpComponenteConDesgloseInicial();

        $c->set('modo_calculo', 'cantidad')
            ->set('desglose.' . $this->bil->id . '.cantidad', 3)
            ->set('desglose.' . $this->moneda->id . '.cantidad', 5)
            ->assertSet('desglose.' . $this->bil->id . '.total', 3000)
            ->assertSet('desglose.' . $this->moneda->id . '.total', 5)
            ->assertSet('saldo_inicial', 3005);
    }

    /** @test */
    public function al_cambiar_modo_calculo_sincroniza_valores(): void
    {
        $c = $this->setUpComponenteConDesgloseInicial();

        $c->set('modo_calculo', 'cantidad')
            ->set('desglose.' . $this->bil->id . '.cantidad', 4)
            ->assertSet('saldo_inicial', 4000)
            ->set('modo_calculo', 'total')
            ->assertSet('desglose.' . $this->bil->id . '.total', 4000)
            ->assertSet('desglose.' . $this->bil->id . '.cantidad', 4);
    }
}