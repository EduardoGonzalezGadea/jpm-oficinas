<?php

namespace App\DTOs;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

class CfeExtraccionDto implements Arrayable, JsonSerializable
{
    public function __construct(
        public readonly string $tipoCfe,
        public readonly ?string $serie,
        public readonly ?string $numero,
        public readonly ?string $fecha,
        public readonly float $monto,
        public readonly string $moneda,
        public readonly ?string $cedula,
        public readonly ?string $nombre,
        public readonly ?string $domicilio,
        public readonly ?float $montoTotal,
        public readonly ?string $formaPago,
        public readonly ?string $adicional,
        public readonly ?string $adenda,
        public readonly ?string $referencias,
        public readonly array $items,
        public readonly ?string $detalleCompleto,
        public readonly ?string $tipoCfeCodigo,
        public readonly ?string $extractorVersion,
        public readonly ?string $detalle = null,
        public readonly ?string $telefono = null,
        public readonly ?string $receptorDocumento = null,
        public readonly ?string $receptorNombre = null,
        public readonly ?string $ingresoContabilidad = null,
        public readonly ?string $ordenCobro = null,
        public readonly ?string $cedulaReceptor = null,
        public readonly ?string $nombreReceptor = null,
        public readonly ?string $cedulaTitular = null,
        public readonly ?string $nombreTitular = null,
        public readonly bool $retiraEsTitular = true,
        public readonly ?string $descripcion = null,
        public readonly ?string $tramite = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            tipoCfe: $data['tipo_cfe'] ?? 'No detectado',
            serie: $data['serie'] ?? null,
            numero: $data['numero'] ?? null,
            fecha: $data['fecha'] ?? null,
            monto: (float) ($data['monto'] ?? $data['monto_total'] ?? 0),
            moneda: $data['moneda'] ?? 'UYU',
            cedula: $data['cedula'] ?? null,
            nombre: $data['nombre'] ?? null,
            domicilio: $data['domicilio'] ?? null,
            montoTotal: isset($data['monto_total']) ? (float) $data['monto_total'] : null,
            formaPago: $data['forma_pago'] ?? null,
            adicional: $data['adicional'] ?? null,
            adenda: $data['adenda'] ?? null,
            referencias: $data['referencias'] ?? null,
            items: $data['items'] ?? [],
            detalle: $data['detalle'] ?? null,
            detalleCompleto: $data['detalle_completo'] ?? null,
            tipoCfeCodigo: $data['tipo_cfe_codigo'] ?? null,
            extractorVersion: $data['extractor_version'] ?? null,
            telefono: $data['telefono'] ?? null,
            receptorDocumento: $data['receptor_documento'] ?? null,
            receptorNombre: $data['receptor_nombre'] ?? null,
            ingresoContabilidad: $data['ingreso_contabilidad'] ?? null,
            ordenCobro: $data['orden_cobro'] ?? null,
            cedulaReceptor: $data['cedula_receptor'] ?? null,
            nombreReceptor: $data['nombre_receptor'] ?? null,
            cedulaTitular: $data['cedula_titular'] ?? null,
            nombreTitular: $data['nombre_titular'] ?? null,
            retiraEsTitular: isset($data['retira_es_titular']) ? (bool) $data['retira_es_titular'] : true,
            descripcion: $data['descripcion'] ?? null,
            tramite: $data['tramite'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'tipo_cfe' => $this->tipoCfe,
            'serie' => $this->serie,
            'numero' => $this->numero,
            'fecha' => $this->fecha,
            'monto' => $this->monto,
            'moneda' => $this->moneda,
            'cedula' => $this->cedula,
            'nombre' => $this->nombre,
            'domicilio' => $this->domicilio,
            'monto_total' => $this->montoTotal,
            'forma_pago' => $this->formaPago,
            'adicional' => $this->adicional,
            'adenda' => $this->adenda,
            'referencias' => $this->referencias,
            'items' => $this->items,
            'detalle' => $this->detalle,
            'detalle_completo' => $this->detalleCompleto,
            'tipo_cfe_codigo' => $this->tipoCfeCodigo,
            'extractor_version' => $this->extractorVersion,
            'telefono' => $this->telefono,
            'receptor_documento' => $this->receptorDocumento,
            'receptor_nombre' => $this->receptorNombre,
            'ingreso_contabilidad' => $this->ingresoContabilidad,
            'orden_cobro' => $this->ordenCobro,
            'cedula_receptor' => $this->cedulaReceptor,
            'nombre_receptor' => $this->nombreReceptor,
            'cedula_titular' => $this->cedulaTitular,
            'nombre_titular' => $this->nombreTitular,
            'retira_es_titular' => $this->retiraEsTitular,
            'descripcion' => $this->descripcion,
            'tramite' => $this->tramite,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function withExtractorVersion(string $version): self
    {
        return new self(
            tipoCfe: $this->tipoCfe,
            serie: $this->serie,
            numero: $this->numero,
            fecha: $this->fecha,
            monto: $this->monto,
            moneda: $this->moneda,
            cedula: $this->cedula,
            nombre: $this->nombre,
            domicilio: $this->domicilio,
            montoTotal: $this->montoTotal,
            formaPago: $this->formaPago,
            adicional: $this->adicional,
            adenda: $this->adenda,
            referencias: $this->referencias,
            items: $this->items,
            detalle: $this->detalle,
            detalleCompleto: $this->detalleCompleto,
            tipoCfeCodigo: $this->tipoCfeCodigo,
            extractorVersion: $version,
            telefono: $this->telefono,
            receptorDocumento: $this->receptorDocumento,
            receptorNombre: $this->receptorNombre,
            ingresoContabilidad: $this->ingresoContabilidad,
            ordenCobro: $this->ordenCobro,
            cedulaReceptor: $this->cedulaReceptor,
            nombreReceptor: $this->nombreReceptor,
            cedulaTitular: $this->cedulaTitular,
            nombreTitular: $this->nombreTitular,
            retiraEsTitular: $this->retiraEsTitular,
            descripcion: $this->descripcion,
            tramite: $this->tramite,
        );
    }

    public function merge(array $overrides): self
    {
        $data = $this->toArray();
        foreach ($overrides as $key => $value) {
            $snakeKey = $this->camelToSnake($key);
            if (array_key_exists($snakeKey, $data)) {
                $data[$snakeKey] = $value;
            }
        }
        return self::fromArray($data);
    }

    private function camelToSnake(string $string): string
    {
        return strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $string));
    }
}