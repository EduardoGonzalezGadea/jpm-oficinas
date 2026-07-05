<?php

namespace App\Exceptions\Tesoreria;

use Exception;

class CfeNotFoundException extends Exception
{
    public function __construct(
        string $message = 'CFE no encontrado',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    public static function fromId(int $id): self
    {
        return new self("CFE con ID {$id} no encontrado.");
    }
}
