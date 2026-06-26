<?php

namespace App\DataTransferObjects;

class CfeData
{
    public function __construct(
        public readonly string $documento_tipo = '',
        public readonly ?string $documento_serie = null,
        public readonly string $documento_numero = '',
        public readonly ?string $fecha = null,
        public readonly ?string $receptor_nombre_denominacion = null,
        public readonly ?string $receptor_documento_ruc = null,
        public readonly ?string $moneda = 'UYU',
        public readonly float $total_a_pagar = 0,
        public readonly ?string $referencias = null,
        public readonly ?string $adenda = null,
        public readonly ?int $tes_caja_concepto_id = null,
        public readonly ?int $siif_distribucion_dependencia_id = null,
        public readonly array $items = [],
        public readonly array $medios_pago = [],
        public readonly array $item_distribuciones = [],
        public readonly bool $force = false,

        // PDF-only fields
        public readonly ?string $emisor_nombre = null,
        public readonly ?string $emisor_direccion = null,
        public readonly ?string $emisor_localidad = null,
        public readonly ?string $emisor_telefono = null,
        public readonly ?string $emisor_correo = null,
        public readonly ?string $emisor_ruc = null,
        public readonly ?string $forma_pago = null,
        public readonly ?string $vencimiento = null,
        public readonly ?string $comprobante_tipo = null,
        public readonly ?string $receptor_domicilio_fiscal = null,
        public readonly ?string $periodo = null,
        public readonly ?string $nro_compra = null,
        public readonly float $monto_no_facturable = 0,
        public readonly float $monto_total = 0,
    ) {}
}
