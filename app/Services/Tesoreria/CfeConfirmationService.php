<?php

namespace App\Services\Tesoreria;

use App\Models\TesCfePendiente;
use App\Models\Tesoreria\TesCfe;
use App\DataTransferObjects\CfeData;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CfeConfirmationService
{
    public function __construct(
        private readonly CfeCreatorService $cfeCreator
    ) {}

    /**
     * Confirma un CFE pendiente y crea el registro definitivo en TesCfe.
     *
     * @param  TesCfePendiente  $pendiente
     * @param  array|null  $datosEditados  Datos corregidos por el usuario (merge con extraídos)
     * @return TesCfe
     * @throws \Exception
     */
    public function confirmar(TesCfePendiente $pendiente, ?array $datosEditados = null): TesCfe
    {
        if ($pendiente->estado !== 'pendiente' && $pendiente->estado !== 'en_revision') {
            throw new \Exception("El CFE pendiente no está en estado confirmable: {$pendiente->estado}");
        }

        $datosExtraidos = $pendiente->datos_extraidos ?? [];
        $datosFinales = array_merge($datosExtraidos, $datosEditados ?? []);

        $this->verificarReferenciaDuplicada($pendiente, $datosFinales);

        return DB::transaction(function () use ($pendiente, $datosFinales) {
            $cfeData = $this->mapearDatosACfeData($pendiente, $datosFinales);

            $cfe = $this->cfeCreator->createFromPdf($cfeData, $pendiente->pdf_path, $pendiente->id);

            $pendiente->estado = 'confirmado';
            $pendiente->procesado_por = auth()->id();
            $pendiente->procesado_at = now();
            $pendiente->save();

            Log::channel('cfe_audit')->info('CFE confirmado desde pendiente', [
                'pendiente_id' => $pendiente->id,
                'cfe_id' => $cfe->id,
                'tipo_cfe' => $pendiente->tipo_cfe,
                'usuario' => auth()->id(),
            ]);

            event(new \App\Events\Tesoreria\CfeConfirmado($cfe, $pendiente));

            return $cfe;
        });
    }

    private function verificarReferenciaDuplicada(TesCfePendiente $pendiente, array $datos): void
    {
        $referencia = trim($datos['referencias'] ?? '');

        if ($referencia === '') {
            return;
        }

        if (!preg_match(
            '/(e[- ]?(?:Factura|Ticket|Boleta)(?:[- ]Cobranza)?|Nota[- ]de[- ]Cr[ée]dito)\s*[-–\s]*([A-Z])?\s*[-–\s]*(\d+)\b/iu',
            $referencia,
            $m
        )) {
            return;
        }

        $refTipo = $m[1];
        $refSerie = !empty($m[2]) ? mb_strtoupper($m[2], 'UTF-8') : null;
        $refNumero = $m[3];
        $tipoNorm = $this->normalizarTipoDoc($refTipo);

        $candidatos = TesCfe::where('referencias', 'like', '%' . $refNumero . '%')
            ->whereNull('deleted_at')
            ->get();

        $encontrado = null;
        foreach ($candidatos as $cfe) {
            $refCfe = $cfe->referencias ?? '';
            $docTipoNorm = $this->normalizarTipoDoc($cfe->documento_tipo ?? '');

            if ($refSerie && !preg_match('/' . preg_quote($refSerie, '/') . '\s*-?\s*' . $refNumero . '/u', $refCfe)) {
                continue;
            }

            if (!str_contains($docTipoNorm, $tipoNorm) && !str_contains($tipoNorm, $docTipoNorm)) {
                continue;
            }

            $encontrado = $cfe;
            break;
        }

        if (!$encontrado) {
            return;
        }

        $docId = "{$encontrado->documento_tipo} {$encontrado->documento_serie}-{$encontrado->documento_numero}";
        $refCompleta = $refTipo . ($refSerie ? "-{$refSerie}" : "") . "-{$refNumero}";

        throw new \RuntimeException(
            "La referencia {$refCompleta} ya existe en el CFE {$docId}. Verifique antes de confirmar."
        );
    }

    private function normalizarTipoDoc(string $tipo): string
    {
        $tipo = mb_strtolower($tipo, 'UTF-8');
        $tipo = preg_replace('/[\s\-]+/', '', $tipo);
        $tipo = str_replace(['ó', 'é', 'í', 'ú'], ['o', 'e', 'i', 'u'], $tipo);
        return $tipo;
    }

    /**
     * Rechaza un CFE pendiente.
     *
     * @param  TesCfePendiente  $pendiente
     * @param  string  $motivo
     * @return void
     */
    public function rechazar(TesCfePendiente $pendiente, string $motivo): void
    {
        $pendiente->estado = 'rechazado';
        $pendiente->motivo_rechazo = $motivo;
        $pendiente->procesado_por = auth()->id();
        $pendiente->procesado_at = now();
        $pendiente->save();

        Log::channel('cfe_audit')->warning('CFE rechazado', [
            'pendiente_id' => $pendiente->id,
            'motivo' => $motivo,
            'usuario' => auth()->id(),
        ]);

        event(new \App\Events\Tesoreria\CfeRechazado($pendiente));
    }

    /**
     * Marca un CFE pendiente como en revisión.
     *
     * @param  TesCfePendiente  $pendiente
     * @return void
     */
    public function marcarEnRevision(TesCfePendiente $pendiente): void
    {
        $pendiente->estado = 'en_revision';
        $pendiente->save();
    }

    /**
     * Mapea los datos extraídos del PDF a CfeData para CfeCreatorService.
     *
     * @param  TesCfePendiente  $pendiente
     * @param  array  $datos
     * @return CfeData
     */
    private function mapearDatosACfeData(TesCfePendiente $pendiente, array $datos): CfeData
    {
        $tipoCfe = $pendiente->tipo_cfe;

        $mapaConceptos = [
            'multas_cobradas' => 'Multas de Tránsito',
            'eventuales' => 'Servicios Eventuales',
            'prendas' => 'Prendas',
            'arrendamientos' => 'Arrendamientos',
            'certificado_residencia' => 'Certificados de Residencia',
            'tenencia_armas' => 'Tenencia de Armas',
            'porte_armas' => 'Porte de Armas',
            'generico' => 'Genérico',
        ];

        $conceptoNombre = $mapaConceptos[$tipoCfe] ?? 'Genérico';
        $concepto = \App\Models\Tesoreria\CajaConcepto::where('caja_concepto', $conceptoNombre)->first();
        $dependenciaId = isset($datos['siif_distribucion_dependencia_id'])
            ? (int) $datos['siif_distribucion_dependencia_id']
            : 1;

        $items = $datos['items'] ?? [];
        if (empty($items) && isset($datos['detalle_completo'])) {
            $items = [['detalle' => $datos['detalle_completo'], 'importe' => $datos['monto_total'] ?? $pendiente->monto]];
        }

        $mediosPago = [];
        if (!empty($datos['forma_pago']) && $datos['forma_pago'] !== 'SIN DATOS') {
            $mediosPago[] = ['tipo' => $datos['forma_pago'], 'valor' => $pendiente->monto];
        }

        return new CfeData(
            documento_tipo: $datos['tipo_cfe'] ?? 'e-Ticket',
            documento_serie: $datos['serie'] ?? null,
            documento_numero: $datos['numero'] ?? '',
            fecha: $datos['fecha'] ?? now()->format('Y-m-d'),
            receptor_nombre_denominacion: $datos['nombre'] ?? 'Sin nombre',
            receptor_documento_ruc: $datos['cedula'] ?? null,
            moneda: $datos['moneda'] ?? 'UYU',
            monto_no_facturable: 0,
            monto_total: $datos['monto_total'] ?? $pendiente->monto,
            total_a_pagar: $pendiente->monto,
            forma_pago: $datos['forma_pago'] ?? null,
            referencias: $datos['referencias'] ?? null,
            adenda: $datos['adenda'] ?? null,
            tes_caja_concepto_id: $concepto?->id,
            siif_distribucion_dependencia_id: $dependenciaId,
            items: $items,
            medios_pago: $mediosPago,
            item_distribuciones: [],
            emisor_nombre: 'Jefatura de Policía de Montevideo',
            emisor_ruc: '214988770019',
            emisor_direccion: 'Av. 18 de Julio 1234',
            emisor_localidad: 'Montevideo',
            emisor_telefono: '029000000',
            emisor_correo: 'tesoreria@jpm.gub.uy',
            comprobante_tipo: 'e-Ticket',
            periodo: null,
            nro_compra: null,
        );
    }
}