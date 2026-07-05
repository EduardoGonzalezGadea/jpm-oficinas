<?php

namespace Tests\Unit\Services\CfeExtractor\Helpers;

trait WithCfeFixtures
{
    private function getFixturePath(string $type, string $name): string
    {
        return dirname(__DIR__, 4) . '/fixtures/cfe/' . $type . '/' . $name;
    }

    private function loadFixture(string $type, string $name): string
    {
        $path = $this->getFixturePath($type, $name);
        return file_get_contents($path);
    }

    private function loadMultasFixture(string $name = 'multa_valida.txt'): string
    {
        return $this->loadFixture('multas', $name);
    }

    private function loadEventualesFixture(string $name = 'eventual_valido.txt'): string
    {
        return $this->loadFixture('eventuales', $name);
    }

    private function loadArrendamientosFixture(string $name = 'arrendamiento_valido.txt'): string
    {
        return $this->loadFixture('arrendamientos', $name);
    }

    private function loadPrendasFixture(string $name = 'prenda_valida.txt'): string
    {
        return $this->loadFixture('prendas', $name);
    }

    private function loadCertificadoResidenciaFixture(string $name = 'certificado_valido.txt'): string
    {
        return $this->loadFixture('certificado_residencia', $name);
    }

    private function loadArmasFixture(string $name): string
    {
        return $this->loadFixture('armas', $name);
    }
}
