<?php

namespace App\Traits;

/**
 * Trait WithOrdenCobroValidation
 *
 * Proporciona validación de unicidad para el campo orden_cobro
 * antes de crear o actualizar registros en los módulos de Tesorería.
 *
 * @package App\Traits
 */
trait WithOrdenCobroValidation
{
    /**
     * Verifica que la orden de cobro no exista ya en la tabla correspondiente.
     * Si existe, emite una alerta indicando el número de recibo donde ya fue registrada.
     *
     * @param  string      $modelClass   Clase del modelo Eloquent (ej: Prenda::class)
     * @param  string|null $ordenCobro   Valor de orden_cobro a verificar
     * @param  int|null    $excludeId    ID del registro actual a excluir (para actualizaciones)
     * @param  string      $reciboField  Campo(s) del recibo. Use pipe "|" para separar múltiples campos (ej: "recibo_serie|recibo_numero")
     * @return bool         True si es válido (no existe duplicado), False si ya existe
     */
    public function validarOrdenCobroUnica($modelClass, $ordenCobro, $excludeId = null, $reciboField = 'recibo'): bool
    {
        if (empty($ordenCobro)) {
            return true;
        }

        $query = $modelClass::where('orden_cobro', $ordenCobro);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        $existing = $query->first();

        if ($existing) {
            $recibo = $this->formatearReciboExistente($existing, $reciboField);

            $this->dispatchBrowserEvent('swal:error', [
                'title' => 'Orden de Cobro Duplicada',
                'text' => "La Orden de Cobro N° {$ordenCobro} ya existe en el recibo {$recibo}.",
            ]);

            return false;
        }

        return true;
    }

    /**
     * Obtiene el número/formato de recibo del registro existente para mostrarlo en la alerta.
     *
     * @param  object $existing    Registro existente
     * @param  string $reciboField Campo(s) del recibo
     * @return string
     */
    private function formatearReciboExistente($existing, string $reciboField): string
    {
        if (strpos($reciboField, '|') !== false) {
            $fields = explode('|', $reciboField);
            $parts = [];
            foreach ($fields as $field) {
                $field = trim($field);
                $parts[] = $existing->{$field} ?? '';
            }
            return implode('-', array_filter($parts));
        }

        return $existing->{$reciboField} ?? 'N/A';
    }
}