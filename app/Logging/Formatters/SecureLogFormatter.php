<?php

namespace App\Logging\Formatters;

use Monolog\Formatter\LineFormatter;
use Monolog\LogRecord;

/**
 * Formateador de logs que sanitiza datos sensibles antes de escribirlos.
 * Elimina o enmascara tokens JWT, contraseñas, RUCs parciales y otros datos sensibles.
 */
class SecureLogFormatter extends LineFormatter
{
    /**
     * Patrones regex de datos sensibles a sanitizar.
     */
    private const SENSITIVE_PATTERNS = [
        // Tokens JWT (tres partes base64 separadas por puntos)
        '/eyJ[A-Za-z0-9_-]+\.eyJ[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+/' => '[JWT_TOKEN_REDACTED]',
        // Bearer tokens
        '/Bearer\s+[A-Za-z0-9_\-\.]+/' => 'Bearer [REDACTED]',
        // Contraseñas en URLs (user:pass@host)
        '/:\/\/[^:]+:[^@]+@/' => '://[REDACTED]:[REDACTED]@',
        // Cadenas tipo password= o password:
        '/(password|contraseña|secret|token|api_key|apikey)\s*[=:]\s*\S+/i' => '$1=[REDACTED]',
        // Cédulas/RUC completos (solo en logs de texto, no en datos estructurados)
        '/\b\d{12}\b/' => '[RUC_REDACTED]',
        // Números de tarjeta de crédito (16 dígitos)
        '/\b\d{4}[\s-]?\d{4}[\s-]?\d{4}[\s-]?\d{4}\b/' => '[CARD_REDACTED]',
    ];

    public function format(LogRecord $record): string
    {
        $formatted = parent::format($record);
        return $this->sanitizarSensitiveData($formatted);
    }

    /**
     * Sanitiza datos sensibles en el mensaje de log.
     */
    private function sanitizarSensitiveData(string $message): string
    {
        $sanitized = $message;

        foreach (self::SENSITIVE_PATTERNS as $pattern => $replacement) {
            $sanitized = preg_replace($pattern, $replacement, $sanitized);
        }

        return $sanitized;
    }
}
