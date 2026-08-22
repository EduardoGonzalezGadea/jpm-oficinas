<?php

namespace Tests\Unit\Services\Security;

use App\Services\Security\BruteForceDetector;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class BruteForceDetectorTest extends TestCase
{
    private BruteForceDetector $detector;

    protected function setUp(): void
    {
        parent::setUp();
        $this->detector = new BruteForceDetector();
    }

    public function test_registrar_primer_intento_no_bloquea(): void
    {
        $resultado = $this->detector->registrarIntentoFallido('192.168.1.1', 'test@example.com');

        $this->assertFalse($resultado['blocked']);
        $this->assertEquals(1, $resultado['attempts']);
    }

    public function test_registrar_cinco_intentos_bloquea(): void
    {
        $ip = '10.0.0.1';

        for ($i = 0; $i < 4; $i++) {
            $resultado = $this->detector->registrarIntentoFallido($ip, 'test@example.com');
            $this->assertFalse($resultado['blocked']);
        }

        $resultado = $this->detector->registrarIntentoFallido($ip, 'test@example.com');
        $this->assertTrue($resultado['blocked']);
        $this->assertEquals(5, $resultado['attempts']);
    }

    public function test_esta_bloqueada_returns_false_below_threshold(): void
    {
        $this->detector->registrarIntentoFallido('172.16.0.1');

        $this->assertFalse($this->detector->estaBloqueada('172.16.0.1'));
    }

    public function test_esta_bloqueada_returns_true_at_threshold(): void
    {
        $ip = '172.16.0.2';

        for ($i = 0; $i < 5; $i++) {
            $this->detector->registrarIntentoFallido($ip);
        }

        $this->assertTrue($this->detector->estaBloqueada($ip));
    }

    public function test_limpiar_intentos_resets_counter(): void
    {
        $ip = '192.168.100.1';

        for ($i = 0; $i < 4; $i++) {
            $this->detector->registrarIntentoFallido($ip);
        }

        $this->detector->limpiarIntentos($ip);

        $this->assertEquals(0, $this->detector->obtenerIntentos($ip));
        $this->assertFalse($this->detector->estaBloqueada($ip));
    }

    public function test_obtener_intentos_returns_zero_for_unknown_ip(): void
    {
        $this->assertEquals(0, $this->detector->obtenerIntentos('192.168.99.99'));
    }

    public function test_intentos_se_acumulan(): void
    {
        $ip = '10.0.0.99';

        $this->detector->registrarIntentoFallido($ip);
        $this->detector->registrarIntentoFallido($ip);
        $this->detector->registrarIntentoFallido($ip);

        $this->assertEquals(3, $this->detector->obtenerIntentos($ip));
    }
}
