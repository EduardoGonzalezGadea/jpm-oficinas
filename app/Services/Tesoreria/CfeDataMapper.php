<?php

namespace App\Services\Tesoreria;

use App\DataTransferObjects\CfeData;

class CfeDataMapper
{
    public static function fromArray(array $data): CfeData
    {
        return new CfeData(
            documento_tipo: $data['documento_tipo'] ?? '',
            documento_serie: $data['documento_serie'] ?? null,
            documento_numero: $data['documento_numero'] ?? '',
            fecha: $data['fecha'] ?? null,
            receptor_nombre_denominacion: $data['receptor_nombre_denominacion'] ?? null,
            receptor_documento_ruc: $data['receptor_documento_ruc'] ?? null,
            moneda: $data['moneda'] ?? 'UYU',
            total_a_pagar: (float)($data['total_a_pagar'] ?? 0),
            referencias: $data['referencias'] ?? null,
            adenda: $data['adenda'] ?? null,
            tes_caja_concepto_id: isset($data['tes_caja_concepto_id']) ? (int)$data['tes_caja_concepto_id'] : null,
            siif_distribucion_dependencia_id: isset($data['siif_distribucion_dependencia_id']) ? (int)$data['siif_distribucion_dependencia_id'] : null,
            items: $data['items'] ?? [],
            medios_pago: $data['medios_pago'] ?? [],
            item_distribuciones: $data['item_distribuciones'] ?? [],
            force: (bool)($data['force'] ?? false),
            emisor_nombre: $data['emisor_nombre'] ?? null,
            emisor_direccion: $data['emisor_direccion'] ?? null,
            emisor_localidad: $data['emisor_localidad'] ?? null,
            emisor_telefono: $data['emisor_telefono'] ?? null,
            emisor_correo: $data['emisor_correo'] ?? null,
            emisor_ruc: $data['emisor_ruc'] ?? null,
            forma_pago: $data['forma_pago'] ?? null,
            vencimiento: $data['vencimiento'] ?? null,
            comprobante_tipo: $data['comprobante_tipo'] ?? null,
            receptor_domicilio_fiscal: $data['receptor_domicilio_fiscal'] ?? null,
            periodo: $data['periodo'] ?? null,
            nro_compra: $data['nro_compra'] ?? null,
            monto_no_facturable: (float)($data['monto_no_facturable'] ?? 0),
            monto_total: (float)($data['monto_total'] ?? 0),
        );
    }

    public static function extraidosToArray(array $datosExtraidos): array
    {
        return [
            'documento_tipo' => $datosExtraidos['documento_tipo'] ?? '',
            'documento_serie' => $datosExtraidos['documento_serie'] ?? null,
            'documento_numero' => $datosExtraidos['documento_numero'] ?? '',
            'fecha' => $datosExtraidos['fecha'] ?? null,
            'receptor_nombre_denominacion' => $datosExtraidos['receptor_nombre_denominacion'] ?? null,
            'receptor_documento_ruc' => $datosExtraidos['receptor_documento_ruc'] ?? null,
            'moneda' => $datosExtraidos['moneda'] ?? 'UYU',
            'total_a_pagar' => (float)($datosExtraidos['total_a_pagar'] ?? 0),
            'referencias' => $datosExtraidos['referencias'] ?? null,
            'adenda' => $datosExtraidos['adenda'] ?? null,
            'items' => $datosExtraidos['items'] ?? [],
            'medios_pago' => $datosExtraidos['medios_pago'] ?? [],
            'emisor_nombre' => $datosExtraidos['emisor_nombre'] ?? null,
            'emisor_direccion' => $datosExtraidos['emisor_direccion'] ?? null,
            'emisor_localidad' => $datosExtraidos['emisor_localidad'] ?? null,
            'emisor_telefono' => $datosExtraidos['emisor_telefono'] ?? null,
            'emisor_correo' => $datosExtraidos['emisor_correo'] ?? null,
            'emisor_ruc' => $datosExtraidos['emisor_ruc'] ?? null,
            'forma_pago' => $datosExtraidos['forma_pago'] ?? null,
            'vencimiento' => $datosExtraidos['vencimiento'] ?? null,
            'comprobante_tipo' => $datosExtraidos['comprobante_tipo'] ?? null,
            'receptor_domicilio_fiscal' => $datosExtraidos['receptor_domicilio_fiscal'] ?? null,
            'periodo' => $datosExtraidos['periodo'] ?? null,
            'nro_compra' => $datosExtraidos['nro_compra'] ?? null,
            'monto_no_facturable' => (float)($datosExtraidos['monto_no_facturable'] ?? 0),
            'monto_total' => (float)($datosExtraidos['monto_total'] ?? 0),
        ];
    }

    public static function toArray(CfeData $data): array
    {
        return [
            'documento_tipo' => $data->documento_tipo,
            'documento_serie' => $data->documento_serie,
            'documento_numero' => $data->documento_numero,
            'fecha' => $data->fecha,
            'receptor_nombre_denominacion' => $data->receptor_nombre_denominacion,
            'receptor_documento_ruc' => $data->receptor_documento_ruc,
            'moneda' => $data->moneda,
            'total_a_pagar' => $data->total_a_pagar,
            'referencias' => $data->referencias,
            'adenda' => $data->adenda,
            'tes_caja_concepto_id' => $data->tes_caja_concepto_id,
            'siif_distribucion_dependencia_id' => $data->siif_distribucion_dependencia_id,
            'items' => $data->items,
            'medios_pago' => $data->medios_pago,
            'item_distribuciones' => $data->item_distribuciones,
            'force' => $data->force,
            'emisor_nombre' => $data->emisor_nombre,
            'emisor_direccion' => $data->emisor_direccion,
            'emisor_localidad' => $data->emisor_localidad,
            'emisor_telefono' => $data->emisor_telefono,
            'emisor_correo' => $data->emisor_correo,
            'emisor_ruc' => $data->emisor_ruc,
            'forma_pago' => $data->forma_pago,
            'vencimiento' => $data->vencimiento,
            'comprobante_tipo' => $data->comprobante_tipo,
            'receptor_domicilio_fiscal' => $data->receptor_domicilio_fiscal,
            'periodo' => $data->periodo,
            'nro_compra' => $data->nro_compra,
            'monto_no_facturable' => $data->monto_no_facturable,
            'monto_total' => $data->monto_total,
        ];
    }
}
