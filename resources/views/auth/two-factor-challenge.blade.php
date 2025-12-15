@extends('layouts.app')

@section('title', 'Verificación de Dos Factores')

@section('content')
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white text-center">
                    <h4 class="mb-0">{{ __('Verificación de Dos Factores') }}</h4>
                </div>

                <div class="card-body text-center">
                    <p class="text-center mb-4">
                        {{ __('Por favor confirma el acceso a tu cuenta ingresando el código de autenticación proporcionado por tu aplicación.') }}
                    </p>

                    @if (session('error'))
                    <div class="alert alert-danger" role="alert">
                        {{ session('error') }}
                    </div>
                    @endif

                    <form method="POST" action="{{ route('two-factor.verify') }}">
                        @csrf

                        <div class="form-group" x-data="{ recovery: false }">

                            {{-- Input de Código TOTP --}}
                            <div x-show="!recovery">
                                <label for="code">{{ __('Código de Autenticación') }}</label>
                                <input id="code" type="text" class="form-control form-control-lg text-center @error('code') is-invalid @enderror"
                                    name="code" autofocus x-bind:disabled="recovery" autocomplete="one-time-code" inputmode="numeric" pattern="[0-9]*">
                            </div>

                            {{-- Input de Código de Recuperación --}}
                            <div x-show="recovery" style="display: none;">
                                <label for="recovery_code">{{ __('Código de Recuperación') }}</label>
                                <input id="recovery_code" type="text" class="form-control form-control-lg text-center @error('recovery_code') is-invalid @enderror"
                                    name="recovery_code" x-bind:disabled="!recovery" autocomplete="off">
                            </div>

                            {{-- Botones Toggle --}}
                            <div class="mt-3">

                                <button type="button" class="btn btn-secondary"
                                    x-show="!recovery"
                                    x-on:click="recovery = true; $nextTick(() => $refs.recovery_code.focus())">
                                    {{ __('Cambiar a usar un código de recuperación') }}
                                </button>

                                <button type="button" class="btn btn-success"
                                    x-show="recovery"
                                    x-on:click="recovery = false; $nextTick(() => $refs.code.focus())"
                                    style="display: none;">
                                    {{ __('Cambiar a usar un código de autenticación') }}
                                </button>
                            </div>
                        </div>

                        <div class="form-group mb-0">
                            <button type="submit" class="btn btn-primary btn-block btn-lg">
                                {{ __('Verificar') }}
                            </button>
                        </div>
                    </form>

                    <div class="mt-3 text-center">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-secondary">
                                {{ __('Cancelar / Cerrar Sesión') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection