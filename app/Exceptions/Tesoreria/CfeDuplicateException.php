<?php

namespace App\Exceptions\Tesoreria;

use Exception;

class CfeDuplicateException extends Exception
{
    public function __construct(
        string $message = 'El CFE ya existe en el sistema',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    public static function fromDocumento(string $tipo, string $numero, ?string $serie = null): self
    {
        $ref = $tipo . ($serie ? "-{$serie}" : '') . "-{$numero}";
        return new self("El documento {$ref} ya existe en el sistema.");
    }

    public static function fromPendiente(string $numero, ?string $serie, string $estado): self
    {
        $ref = $serie ? "{$serie}-{$numero}" : $numero;
        return new self("El recibo {$ref} ya existe como pendiente (estado: {$estado}).");
    }
}
