<?php

namespace App\Exceptions;

use App\Http\Responses\SessionExpiredResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Session\TokenMismatchException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class Handler extends ExceptionHandler
{
    protected $dontReport = [];

    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register()
    {
        $this->renderable(function (AuthenticationException $e, $request) {
            if ($request->expectsJson() || $request->is('livewire/*') || $request->ajax()) {
                return SessionExpiredResponse::make($request, SessionExpiredResponse::DEFAULT_MESSAGE);
            }
        });

        $this->renderable(function (TokenMismatchException $e, $request) {
            return SessionExpiredResponse::make(
                $request,
                SessionExpiredResponse::DEFAULT_MESSAGE
            );
        });

        $this->renderable(function (HttpException $e, $request) {
            if ($e->getStatusCode() === 500 && SessionExpiredResponse::isSessionExpiredMessage($e->getMessage())) {
                return SessionExpiredResponse::make($request, $e->getMessage());
            }
        });
    }

    protected function unauthenticated($request, AuthenticationException $exception)
    {
        return SessionExpiredResponse::make($request, SessionExpiredResponse::DEFAULT_MESSAGE);
    }
}