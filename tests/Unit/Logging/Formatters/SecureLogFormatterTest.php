<?php

namespace Tests\Unit\Logging\Formatters;

use App\Logging\Formatters\SecureLogFormatter;
use Monolog\Level;
use Monolog\LogRecord;
use Tests\TestCase;

class SecureLogFormatterTest extends TestCase
{
    private SecureLogFormatter $formatter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->formatter = new SecureLogFormatter();
    }

    private function makeRecord(string $message): LogRecord
    {
        return new LogRecord(
            datetime: new \DateTimeImmutable(),
            channel: 'test',
            level: Level::Info,
            message: $message,
        );
    }

    public function test_jwt_token_is_redacted(): void
    {
        $record = $this->makeRecord('Token received: eyJhbGciOiJIUzI1NiJ9.eyJzdWIiOiIxIn0.abc123def456');

        $output = $this->formatter->format($record);

        $this->assertStringNotContainsString('eyJhbGciOiJIUzI1NiJ9', $output);
        $this->assertStringContainsString('[JWT_TOKEN_REDACTED]', $output);
    }

    public function test_bearer_token_is_redacted(): void
    {
        $record = $this->makeRecord('Authorization: Bearer abc123xyz789');

        $output = $this->formatter->format($record);

        $this->assertStringContainsString('Bearer [REDACTED]', $output);
        $this->assertStringNotContainsString('abc123xyz789', $output);
    }

    public function test_password_in_url_is_redacted(): void
    {
        $record = $this->makeRecord('Connecting to mysql://admin:secretpass@localhost/db');

        $output = $this->formatter->format($record);

        $this->assertStringNotContainsString('secretpass', $output);
        $this->assertStringContainsString('[REDACTED]', $output);
    }

    public function test_password_equals_value_is_redacted(): void
    {
        $record = $this->makeRecord('Config: password=mysecretpassword123');

        $output = $this->formatter->format($record);

        $this->assertStringNotContainsString('mysecretpassword123', $output);
        $this->assertStringContainsString('password=[REDACTED]', $output);
    }

    public function test_ruc_number_is_redacted(): void
    {
        $record = $this->makeRecord('RUC del emisor: 214365870012');

        $output = $this->formatter->format($record);

        $this->assertStringNotContainsString('214365870012', $output);
        $this->assertStringContainsString('[RUC_REDACTED]', $output);
    }

    public function test_credit_card_number_is_redacted(): void
    {
        $record = $this->makeRecord('Card number: 4111 1111 1111 1111');

        $output = $this->formatter->format($record);

        $this->assertStringNotContainsString('4111', $output);
        $this->assertStringContainsString('[CARD_REDACTED]', $output);
    }

    public function test_clean_log_is_not_modified(): void
    {
        $record = $this->makeRecord('User logged in successfully from 192.168.1.1');

        $output = $this->formatter->format($record);

        $this->assertStringContainsString('User logged in successfully from 192.168.1.1', $output);
    }
}
