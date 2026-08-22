<?php

namespace App\Livewire\Concerns;

/**
 * Trait que normaliza a mayúsculas el campo $documento_referencia.
 * Se invoca explícitamente justo antes de guardar el dato en la base de
 * datos, para no interferir con la escritura del usuario en los formularios
 * (evita la pérdida de caracteres y la eliminación de espacios al teclear).
 * Soporta caracteres acentuados y especiales del español (UTF-8).
 */
trait NormalizaDocumentoReferencia
{
    protected function normalizarDocumentoReferencia(): void
    {
        if (is_string($this->documento_referencia)) {
            $this->documento_referencia = mb_strtoupper(trim($this->documento_referencia), 'UTF-8');
        }
    }
}
