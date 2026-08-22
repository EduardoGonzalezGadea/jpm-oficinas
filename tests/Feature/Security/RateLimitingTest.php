<?php

namespace Tests\Feature\Security;

use Tests\TestCase;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class RateLimitingTest extends TestCase
{
    protected bool $requiresDatabase = false;

    public function test_limiter_api_esta_configurado(): void
    {
        $limiter = RateLimiter::limiter('api');
        $this->assertNotNull($limiter);
    }

    public function test_rate_limiting_limita_peticiones_con_la_misma_key(): void
    {
        $key = 'test-rate-limit-' . Str::random(10);

        for ($i = 0; $i < 65; $i++) {
            RateLimiter::hit($key, 60);
        }

        $this->assertTrue(RateLimiter::tooManyAttempts($key, 60));
    }

    public function test_rate_limiting_resetea_contador(): void
    {
        $key = 'test-rate-limit-reset-' . Str::random(10);

        for ($i = 0; $i < 65; $i++) {
            RateLimiter::hit($key, 60);
        }

        $this->assertTrue(RateLimiter::tooManyAttempts($key, 60));

        RateLimiter::clear($key);
        $this->assertFalse(RateLimiter::tooManyAttempts($key, 60));
    }

    public function test_rate_limiting_permite_peticiones_dentro_del_limite(): void
    {
        $key = 'test-rate-limit-within-' . Str::random(10);

        for ($i = 0; $i < 5; $i++) {
            RateLimiter::hit($key, 60);
        }

        $this->assertFalse(RateLimiter::tooManyAttempts($key, 60));
        $this->assertGreaterThan(0, RateLimiter::remaining($key, 60));
    }
}
