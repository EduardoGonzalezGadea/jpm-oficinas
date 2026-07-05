<?php

namespace App\Exceptions\Tesoreria;

use Exception;

class CfeValidationException extends Exception
{
    public function __construct(
        string $message = 'Datos del CFE inválidos',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    public static function fromItems(string $detalle): self
    {
        return new self("Error de validación en ítems: {$detalle}");
    }
}
