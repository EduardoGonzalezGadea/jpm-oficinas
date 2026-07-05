<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

trait WithAnulacionCfe
{
    public $anulacionPendiente = null;
    private string $ultimaRefEncontrada = '';

    /**
     * Retorna la clase del modelo donde buscar el registro a anular.
     * @return class-string<Model>
     */
    abstract protected function getModelClassForAnulacion(): string;

    /**
     * Retorna el nombre del campo recibo en el modelo.
     * @return string
     */
    protected function getReciboFieldForAnulacion(): string
    {
        return 'recibo';
    }

    /**
     * Para modelos con recibo_serie + recibo_numero separados (ej: Prendas),
     * retorna el nombre del campo de serie. Retorna null si el campo es combinado.
     */
    protected function getReciboSerieFieldForAnulacion(): ?string
    {
        return null;
    }

    /**
     * Para modelos con recibo_serie + recibo_numero separados (ej: Prendas),
     * retorna el nombre del campo de número. Retorna null si el campo es combinado.
     */
    protected function getReciboNumeroFieldForAnulacion(): ?string
    {
        return null;
    }

    /**
     * Para módulos que usan múltiples modelos (ej: Armas busca en Porte y Tenencia),
     * retorna un array de clases de modelo.
     * @return array<class-string<Model>>
     */
    protected function getModelClassesForAnulacion(): array
    {
        return [$this->getModelClassForAnulacion()];
    }

    /**
     * Maneja el flujo de anulación cuando se detecta monto negativo.
     * Se llama desde el catch de CfeExtraccionInvalidaException.
     *
     * @return string|null 'confirmar', 'inexistente', '' o null si no aplica
     */
    protected function handleMontoAnulacion(string $text, string $exceptionMessage): ?string
    {
        if (!str_contains($exceptionMessage, 'Monto no valido')) {
            return null;
        }

        return $this->detectarAnulacion($text);
    }

    private function detectarAnulacion(string $text): string
    {
        if (!preg_match('/TOTAL\s+A\s+PAGAR:\s*-\s*([\d\.,]+)/iu', $text, $mMonto)) {
            return '';
        }

        if (!preg_match('/REFERENCIAS:\s*\n(.*?)(?=ADENDA\b|Fecha\s+de\s+Vencimiento|$)/isu', $text, $refBlock)) {
            return '';
        }

        $refText = $refBlock[1];
        if (!preg_match('/e-(?:Factura|Ticket|Boleta)[\s\-]*([A-Z])\s*-?\s*(\d+)/iu', $refText, $mRef)) {
            return '';
        }

        $refRecibo = mb_strtoupper($mRef[1] . '-' . $mRef[2], 'UTF-8');
        $refReciboNoSep = mb_strtoupper($mRef[1] . $mRef[2], 'UTF-8');
        $this->ultimaRefEncontrada = $refRecibo;

        $propioRecibo = '';
        if (preg_match('/SERIE\s*N[ÚU]MERO\b[^\n]*\n\s*([A-Z])\s+(\d+)/iu', $text, $mPropio)) {
            $propioRecibo = mb_strtoupper($mPropio[1] . '-' . $mPropio[2], 'UTF-8');
        }
        if (!empty($propioRecibo) && ($refRecibo === $propioRecibo || $refReciboNoSep === str_replace('-', '', $propioRecibo))) {
            return '';
        }

        $existing = $this->buscarRegistroPorRecibo($refRecibo, $refReciboNoSep);
        if (!$existing) {
            return 'inexistente';
        }

        $montoNota = (float)str_replace(['.', ','], ['', '.'], $mMonto[1]);

        if (abs(abs($montoNota) - (float)$existing->monto) > 1.0) {
            $this->mensajeError = 'Posible anulación: el monto (' . number_format($montoNota, 2, ',', '.') . ') no coincide con el registro referenciado ' . $refRecibo . ' (' . number_format($existing->monto, 2, ',', '.') . ').';
            return '';
        }

        $this->anulacionPendiente = [
            'orden_cobro' => $refRecibo,
            'record_id' => $existing->id,
            'titular' => $existing->titular ?? $existing->nombre ?? $this->obtenerTitular($existing),
            'fecha' => $this->formatearFecha($existing),
            'monto' => $existing->monto,
            'monto_nota' => $montoNota,
        ];

        return 'confirmar';
    }

    private function buscarRegistroPorRecibo(string $refRecibo, string $refReciboNoSep): ?Model
    {
        $models = $this->getModelClassesForAnulacion();
        $serieField = $this->getReciboSerieFieldForAnulacion();

        if ($serieField !== null) {
            $numeroField = $this->getReciboNumeroFieldForAnulacion();
            $serie = '';
            $numero = '';
            if (preg_match('/^([A-Z])\s*-?\s*(\d+)$/iu', $refRecibo, $m)) {
                $serie = mb_strtoupper($m[1], 'UTF-8');
                $numero = $m[2];
            } elseif (preg_match('/^([A-Z])\s*-?\s*(\d+)$/iu', $refReciboNoSep, $m)) {
                $serie = mb_strtoupper($m[1], 'UTF-8');
                $numero = $m[2];
            }
            if (empty($serie) || empty($numero)) return null;

            foreach ($models as $modelClass) {
                $result = $modelClass::where($serieField, $serie)
                    ->where($numeroField, $numero)
                    ->first();
                if ($result) return $result;
            }
            return null;
        }

        $field = $this->getReciboFieldForAnulacion();
        foreach ($models as $modelClass) {
            $result = $modelClass::where($field, $refRecibo)
                ->orWhere($field, $refReciboNoSep)
                ->first();
            if ($result) return $result;
        }

        return null;
    }

    private function formatearFecha(Model $record): string
    {
        if ($record->fecha instanceof \Carbon\Carbon) {
            return $record->fecha->format('d/m/Y');
        }
        if (is_string($record->fecha)) {
            try {
                return \Carbon\Carbon::parse($record->fecha)->format('d/m/Y');
            } catch (\Exception $e) {
                return $record->fecha;
            }
        }
        return (string)$record->fecha;
    }

    private function obtenerTitular(Model $record): string
    {
        foreach (['titular', 'nombre', 'titular_nombre', 'razon_social_receptor', 'nombre_receptor'] as $campo) {
            if (!empty($record->$campo)) {
                return $record->$campo;
            }
        }
        return 'S/D';
    }

    public function confirmarAnulacion()
    {
        if (!$this->anulacionPendiente) return;

        $models = $this->getModelClassesForAnulacion();
        $record = null;
        foreach ($models as $modelClass) {
            $record = $modelClass::find($this->anulacionPendiente['record_id']);
            if ($record) break;
        }

        if ($record) {
            $record->delete();
            Cache::flush();
            session()->flash('message', 'Registro ' . $this->anulacionPendiente['orden_cobro'] . ' eliminado exitosamente.');
        }

        $this->anulacionPendiente = null;
        $this->archivo = null;
        $this->datosExtraidos = null;
    }

    public function cancelarAnulacion()
    {
        $this->anulacionPendiente = null;
        $this->archivo = null;
        $this->datosExtraidos = null;
        $this->mensajeError = 'No se cargó el comprobante. El monto negativo no es válido para este módulo.';
    }

    protected function limpiarAnulacion()
    {
        $this->anulacionPendiente = null;
    }
}
