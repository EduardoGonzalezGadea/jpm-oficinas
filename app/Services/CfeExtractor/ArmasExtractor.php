<?php

namespace App\Services\CfeExtractor;

/**
 * Extractor para CFE de Armas (Porte y Tenencia).
 */
class ArmasExtractor extends BaseExtractor
{
    /**
     * Verifica si este extractor soporta el tipo dado.
     *
     * @param string $tipo
     * @return bool
     */
    public function soporta(string $tipo): bool
    {
        $tipoLower = mb_strtolower($tipo, 'UTF-8');
        $tipoSinAcentos = $this->quitarAcentos($tipoLower);

        return strpos($tipoSinAcentos, 'porte') !== false
            || strpos($tipoSinAcentos, 'tenencia') !== false
            || strpos($tipoSinAcentos, 'arma') !== false
            || strpos($tipoSinAcentos, 'tahta') !== false;
    }

    /**
     * Retorna el nombre legible.
     *
     * @return string
     */
    public function getNombreLegible(): string
    {
        return 'Armas';
    }

    /**
     * Extrae los datos del CFE de armas.
     *
     * @param string $texto
     * @return array
     */
    public function extraer(string $texto): array
    {
        $texto = $this->limpiarTexto($texto);

        $datos = $this->getEstructuraBase();
        $datos['emisor_rut'] = '';
        $datos['emisor_nombre'] = '';
        $datos['receptor_documento'] = '';
        $datos['receptor_nombre'] = '';
        $datos['monto'] = 0.0;
        $datos['subtotal'] = 0.0;
        $datos['iva'] = 0.0;
        $datos['detalle'] = '';
        $datos['orden_cobro'] = '';
        $datos['tramite'] = '';
        $datos['ingreso_contabilidad'] = '';
        $datos['telefono'] = '';
        $datos['adenda'] = '';

        // Tipo de CFE
        $datos['tipo_cfe'] = $this->extraerTipoCfe($texto);

        // Serie y Numero
        $serieNumero = $this->extraerSerieNumero($texto);
        $datos['serie'] = $serieNumero['serie'];
        $datos['numero'] = $serieNumero['numero'];

        // Fecha
        $datos['fecha'] = $this->extraerFecha($texto);

        // RUC Emisor
        $datos['emisor_rut'] = $this->extraerEmisorRut($texto);

        // Receptor
        $datos['receptor_documento'] = $this->extraerReceptorDocumento($texto);
        $datos['receptor_nombre'] = $this->extraerReceptorNombre($texto);

        // Razon Social Emisor
        if (preg_match('/^([^\n]+)\n([^\n]+)/', ltrim($texto), $matches)) {
            $datos['emisor_nombre'] = trim($matches[1] . ' ' . $matches[2]);
        }

        // Montos
        if (preg_match('/TOTAL A PAGAR:\s*([\d\.,]+)/i', $texto, $matches)) {
            $datos['monto'] = $this->parsearMonto($matches[1]);
            $datos['subtotal'] = $datos['monto'];
        } elseif (preg_match('/MONTO NO FACTURABLE:\s*([\d\.,]+)/i', $texto, $matches)) {
            $datos['monto'] = $this->parsearMonto($matches[1]);
            $datos['subtotal'] = $datos['monto'];
        }

        // Moneda
        $datos['moneda'] = $this->detectarMoneda($texto);

        // Telefono
        $datos['telefono'] = $this->extraerTelefono($texto);

        // Datos adicionales de Jefatura
        if (preg_match('/(?:ING\.?|ING:|INGRESO|ING)(?:\s*N.?)?[:\s\t-]*(\d+)/iu', $texto, $matches)) {
            $datos['ingreso_contabilidad'] = $matches[1];
        }

        if (preg_match('/(?:ORDEN\s+DE\s+COBRO|ORDEN\s+COBRO|O\.C\.|O\/C|O\.\(?C\.)(?:\s*N.?)?[:\s\t-]*(\d+)/iu', $texto, $matches)) {
            $datos['orden_cobro'] = $matches[1];
        }

        // Tramite
        if (preg_match('/(?:TRÁMITE|TRAMITE)(?:\s*N.?)?[:\s\t-]*([\d\/]+)/iu', $texto, $matches)) {
            $datos['tramite'] = $matches[1];
        }

        // Adenda
        $datos['adenda'] = $this->extraerAdenda($texto);

        // Orden de Cobro desde adenda
        if (empty($datos['orden_cobro']) && !empty($datos['adenda'])) {
            $datos['orden_cobro'] = $this->extraerOrdenCobroAdenda($datos['adenda']);
        }

        // Ingreso desde adenda
        if (empty($datos['ingreso_contabilidad']) && !empty($datos['adenda'])) {
            $adendaNorm = $this->quitarAcentos(mb_strtolower($datos['adenda'], 'UTF-8'));
            if (preg_match('/(?:ing\.?|ing:|ingreso|ing)(?:\s*n.?)?[:\s\t-]*(\d+)/iu', $adendaNorm, $matches)) {
                $datos['ingreso_contabilidad'] = $matches[1];
            }
        }

        // Detalle descriptivo
        if (preg_match('/DETALLE DESCRIPCIÓN[^\n]+\n\s*([^\n]+(?:\n\s*[^\n,]+)*)/i', $texto, $matches)) {
            $datos['detalle'] = trim(preg_replace('/\s+/', ' ', $matches[1]));
        }

        return $datos;
    }

    /**
     * Extrae la orden de cobro de la adenda.
     *
     * @param string $adenda
     * @return string
     */
    private function extraerOrdenCobroAdenda(string $adenda): string
    {
        $adendaNorm = $this->quitarAcentos(mb_strtolower($adenda, 'UTF-8'));

        if (preg_match('/(?:orden\s+de\s+cobro|orden\s+cobro|o\.\s*\(?c\.?|o\/c)\s*(\d+)/iu', $adendaNorm, $matches)) {
            return $matches[1];
        }

        // Buscar un numero de 4 a 6 digitos que este solo o con separadores
        $numeros = [];
        if (preg_match_all('/\b(\d{4,6})\b/', $adenda, $numMatches)) {
            $numeros = $numMatches[1];
        }

        if (count($numeros) === 1) {
            return $numeros[0];
        }

        return '';
    }

    /**
     * Determina si es Porte o Tenencia basandose en el texto.
     *
     * @param string $texto
     * @return string 'porte_armas' o 'tenencia_armas'
     */
    public function determinarTipoArma(string $texto): string
    {
        $textoSinAcentos = $this->quitarAcentos(mb_strtolower($texto, 'UTF-8'));

        if (strpos($textoSinAcentos, 'tenencia') !== false || strpos($textoSinAcentos, 'tahta') !== false) {
            return 'tenencia_armas';
        }

        if (strpos($textoSinAcentos, 'porte') !== false) {
            return 'porte_armas';
        }

        // Por defecto, si tiene 'arma' pero no es especifico
        return 'tenencia_armas';
    }
}