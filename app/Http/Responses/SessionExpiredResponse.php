<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SessionExpiredResponse
{
    public const DEFAULT_MESSAGE = 'Tu sesión ha expirado por inactividad. Por favor, inicia sesión nuevamente.';

    public static function make(Request $request, ?string $message = null): Response
    {
        $message = $message ?? self::DEFAULT_MESSAGE;

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if (self::wantsJsonResponse($request)) {
            return self::jsonResponse($message, $request->is('livewire/*') ? 419 : 401);
        }

        return redirect()
            ->route('login')
            ->with('error', $message)
            ->withoutCookie('jwt_token');
    }

    public static function isSessionExpiredMessage(string $message): bool
    {
        $normalized = mb_strtolower($message);

        return str_contains($normalized, 'sesión')
            && (
                str_contains($normalized, 'expir')
                || str_contains($normalized, 'inválid')
                || str_contains($normalized, 'invalid')
                || str_contains($normalized, 'termin')
            );
    }

    private static function wantsJsonResponse(Request $request): bool
    {
        return $request->expectsJson()
            || $request->is('livewire/*')
            || $request->ajax();
    }

    private static function jsonResponse(string $message, int $status): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'redirect' => route('login'),
        ], $status);
    }
}