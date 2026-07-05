<?php

namespace App\Exceptions;

use Exception;

class CfeExtraccionInvalidaException extends Exception
{
    public function __construct(
        public readonly array $errores,
        string $message = 'Datos extraídos del CFE inválidos',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    public static function fromValidationErrors(array $errors): self
    {
        return new self($errors, 'Validación de extracción CFE fallida: ' . implode(', ', $errors));
    }
}