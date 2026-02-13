<?php

namespace App\Services\Tesoreria;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class MedioPagoService
{
    /**
     * Parsea un valor numérico que puede estar en formato uruguayo (1.234,56)
     * o en formato estándar (1234.56). Detecta automáticamente el formato.
     */
    protected function parsearValorNumerico(string $valorStr): ?float
    {
        $valorStr = trim($valorStr);
        if (empty($valorStr)) return null;

        $tieneComa = strpos($valorStr, ',') !== false;
        $tienePunto = strpos($valorStr, '.') !== false;

        if ($tieneComa && $tienePunto) {
            // Ambos separadores presentes
            $posComa = strrpos($valorStr, ',');
            $posPunto = strrpos($valorStr, '.');

            if ($posComa > $posPunto) {
                // Formato uruguayo: 1.234,56 → coma es decimal
                $limpio = str_replace('.', '', $valorStr);
                $limpio = str_replace(',', '.', $limpio);
            } else {
                // Formato inglés: 1,234.56 → punto es decimal
                $limpio = str_replace(',', '', $valorStr);
            }
        } elseif ($tieneComa) {
            // Solo coma: puede ser decimal uruguayo (1234,56)
            $limpio = str_replace(',', '.', $valorStr);
        } elseif ($tienePunto) {
            // Solo punto: verificar si es separador de miles o decimal
            $partesPunto = explode('.', $valorStr);
            if (count($partesPunto) == 2 && strlen($partesPunto[1]) == 3 && strlen($partesPunto[0]) <= 3) {
                // Patrón como 1.234 o 12.345 → separador de miles sin decimales
                $limpio = str_replace('.', '', $valorStr);
            } elseif (count($partesPunto) > 2) {
                // Múltiples puntos: 1.234.567 → separadores de miles
                $limpio = str_replace('.', '', $valorStr);
            } else {
                // Un solo punto con parte decimal de 1-2 dígitos: 1234.56 → decimal estándar
                $limpio = $valorStr;
            }
        } else {
            // Sin separadores
            $limpio = $valorStr;
        }

        return is_numeric($limpio) ? floatval($limpio) : null;
    }
    /**
     * Elimina acentos y caracteres especiales de un string
     */
    public function quitarAcentos(string $string): string
    {
        $search  = explode(",", "á,é,í,ó,ú,Á,É,Í,Ó,Ú,à,è,ì,ò,ù,À,È,Ì,Ò,Ù,ä,ë,ï,ö,ü,Ä,Ë,Ï,Ö,Ü,â,ê,î,ô,û,Â,Ê,Î,Ô,Û,ñ,Ñ");
        $replace = explode(",", "a,e,i,o,u,A,E,I,O,U,a,e,i,o,u,A,E,I,O,U,a,e,i,o,u,A,E,I,O,U,a,e,i,o,u,A,E,I,O,U,n,N");
        return str_replace($search, $replace, $string);
    }

    public function validarFormato(string $medioPago): bool
    {
        // Formatos válidos:
        // - "EFECTIVO:1000"
        // - "EFECTIVO:1000/CHEQUE:2000"
        // Cada medio DEBE tener su valor especificado con :

        $partes = explode('/', $medioPago);
        $mediosValidos = $this->obtenerMediosDisponibles();

        foreach ($partes as $parte) {
            $parte = trim($parte);

            if (empty($parte)) {
                return false;
            }

            $datos = explode(':', $parte);

            // Ahora exigimos exactamente 2 partes (Nombre:Valor)
            if (count($datos) !== 2) {
                return false;
            }

            $nombreLimpio = mb_strtoupper($this->quitarAcentos(trim($datos[0])), 'UTF-8');
            $valor = trim($datos[1]);

            if (empty($nombreLimpio) || empty($valor)) {
                return false;
            }

            // Validar que el nombre exista en los medios activos (comparando sin acentos y en Mayúsculas)
            $mediosValidosLimpios = array_map(fn($m) => mb_strtoupper($this->quitarAcentos($m), 'UTF-8'), $mediosValidos);
            if (!in_array($nombreLimpio, $mediosValidosLimpios)) {
                return false;
            }

            // Verificar que el valor sea numérico usando el parser inteligente
            $valorParseado = $this->parsearValorNumerico($valor);
            if ($valorParseado === null || $valorParseado < 0) {
                return false;
            }
        }

        return true;
    }

    /**
     * Valida la consistencia de un medio de pago combinado
     */
    public function validarConsistencia(string $medioPago, float $montoTotal): bool
    {
        if (!$this->esMedioCombinado($medioPago)) {
            return true;
        }

        $partes = $this->parsearMedioPago($medioPago);
        $sumaValores = array_sum(array_column($partes, 'valor'));

        return abs($montoTotal - $sumaValores) < 0.01;
    }

    /**
     * Determina si un medio de pago es combinado
     */
    public function esMedioCombinado(string $medioPago): bool
    {
        return str_contains($medioPago, '/');
    }

    /**
     * Parsea un medio de pago en partes individuales
     */
    public function parsearMedioPago(string $medioPago): array
    {
        $partes = explode('/', $medioPago);
        $resultado = [];

        foreach ($partes as $parte) {
            $datos = explode(':', trim($parte));
            $nombreMedio = mb_strtoupper($this->quitarAcentos(trim($datos[0])), 'UTF-8');

            if (isset($datos[1])) {
                $valor = $this->parsearValorNumerico($datos[1]);
            } else {
                $valor = null;
            }

            $resultado[] = [
                'nombre' => trim($datos[0]), // Mantener original sin forzar Mayúsculas
                'valor' => $valor,
            ];
        }

        return $resultado;
    }

    /**
     * Calcula los valores de cada medio de pago en un medio combinado
     */
    public function calcularValoresMedios(string $medioPago, float $montoTotal): array
    {
        $partes = $this->parsearMedioPago($medioPago);
        $partesConValores = [];

        // Verificar si alguno de los medios tiene valor especificado
        $tieneValoresEspecificados = collect($partes)->filter(fn($parte) => $parte['valor'] !== null)->count() > 0;

        if ($tieneValoresEspecificados) {
            // Sumar valores especificados
            $sumValoresEspecificados = collect($partes)->filter(fn($parte) => $parte['valor'] !== null)->sum('valor');

            // Calcular monto restante para repartir
            $montoRestante = $montoTotal - $sumValoresEspecificados;
            $partesSinValor = collect($partes)->filter(fn($parte) => $parte['valor'] === null)->count();

            foreach ($partes as $parte) {
                $valor = $parte['valor'];

                if ($valor === null) {
                    $valor = $partesSinValor > 0 ? $montoRestante / $partesSinValor : 0;
                }

                $partesConValores[] = [
                    'nombre' => $parte['nombre'],
                    'valor' => round($valor, 2),
                ];
            }
        } else {
            // Repartir igualmente
            $valorPorMedio = $montoTotal / count($partes);

            foreach ($partes as $parte) {
                $partesConValores[] = [
                    'nombre' => $parte['nombre'],
                    'valor' => round($valorPorMedio, 2),
                ];
            }
        }

        return $partesConValores;
    }

    /**
     * Busca el nombre real del medio de pago en la base de datos (con acentos)
     */
    public function obtenerNombreReal(string $nombre): string
    {
        $nombreLimpio = $this->quitarAcentos(mb_strtoupper(trim($nombre), 'UTF-8'));
        $medios = $this->obtenerMediosDisponibles();

        foreach ($medios as $m) {
            if ($this->quitarAcentos(mb_strtoupper($m, 'UTF-8')) === $nombreLimpio) {
                return $m; // Retornar el nombre original de la DB (con sus Mayúsculas/Minúsculas)
            }
        }

        return $nombre; // Si no está en la DB, retornar tal cual se ingresó
    }

    /**
     * Normaliza un medio de pago
     */
    public function normalizar(string $medioPago): string
    {
        $partes = $this->parsearMedioPago($medioPago);

        // Normalizar cada nombre rescatando acentos de la base de datos
        foreach ($partes as &$p) {
            $p['nombre'] = $this->obtenerNombreReal($p['nombre']);
        }

        // Ordenar los medios de pago alfabéticamente para normalizar combinaciones
        usort($partes, function ($a, $b) {
            return strcasecmp($this->quitarAcentos($a['nombre']), $this->quitarAcentos($b['nombre']));
        });

        $partesNormalizadas = [];

        foreach ($partes as $parte) {
            $nombre = $parte['nombre'];
            $valor = $parte['valor'];

            if ($valor !== null) {
                $partesNormalizadas[] = sprintf('%s:%s', $nombre, number_format($valor, 2, '.', ''));
            } else {
                $partesNormalizadas[] = $nombre;
            }
        }

        return implode('/', $partesNormalizadas);
    }

    public function obtenerMediosDisponibles(): array
    {
        return \App\Models\Tesoreria\MedioDePago::activos()
            ->ordenado()
            ->pluck('nombre')
            ->toArray();
    }

    public function validarYNormalizar(string $medioPago, float $montoTotal = null): string
    {
        if (! $this->validarFormato($medioPago)) {
            $mediosValidos = implode(', ', $this->obtenerMediosDisponibles());
            throw ValidationException::withMessages([
                'forma_pago' => "Formato inválido. Debe ser 'Medio:Valor'. Medios activos: {$mediosValidos}",
            ]);
        }

        if ($montoTotal !== null && $this->esMedioCombinado($medioPago) && ! $this->validarConsistencia($medioPago, $montoTotal)) {
            throw ValidationException::withMessages([
                'forma_pago' => 'La suma de los valores de los medios de pago no coincide con el monto total.',
            ]);
        }

        return $this->normalizar($medioPago);
    }
}
