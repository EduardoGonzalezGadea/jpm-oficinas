@extends('layouts.app')

@section('title', 'Autenticación de Dos Factores')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-12">
            <div class="card">
                <div class="card-header text-center">{{ __('Autenticación de dos factores (2FA)') }}</div>

                <div class="card-body text-center">
                    @if (session('status') == 'two-factor-authentication-enabled')
                    <div class="alert alert-success" role="alert">
                        {{ __('La autenticación de dos factores ha sido habilitada.') }}
                    </div>
                    @endif

                    @if (session('success'))
                    <div class="alert alert-success" role="alert">
                        {{ session('success') }}
                    </div>
                    @endif

                    @if (session('error'))
                    <div class="alert alert-danger" role="alert">
                        {{ session('error') }}
                    </div>
                    @endif

                    <p>
                        {{ __('La autenticación de dos factores agrega seguridad adicional a su cuenta mediante el uso de un código único de acción temporal (TOTP) generado por una aplicación como Google Authenticator.') }}
                    </p>

                    @if ($user->two_factor_confirmed_at)
                    {{-- 2FA ESTÁ HABILITADO --}}
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> {{ __('Tienes habilitada la autenticación de dos factores.') }}
                    </div>

                    @if($user->two_factor_recovery_codes)
                    <div class="mt-4">
                        <h5>{{ __('Códigos de Recuperación') }}</h5>
                        <p>{{ __('Guarde estos códigos en un lugar seguro. Pueden usarse para recuperar el acceso a su cuenta si pierde su dispositivo de autenticación de dos factores.') }}</p>

                        <div class="bg-light p-3 rounded">
                            @foreach ($user->two_factor_recovery_codes as $code)
                            <div class="mb-1 text-monospace">{{ $code }}</div>
                            @endforeach
                        </div>

                        <form method="POST" action="{{ route('two-factor.regenerate') }}" class="mt-3">
                            @csrf
                            <button type="submit" class="btn btn-warning">
                                {{ __('Regenerar Códigos de Recuperación') }}
                            </button>
                        </form>
                    </div>
                    @endif

                    <div class="mt-4">
                        <h5>{{ __('Deshabilitar 2FA') }}</h5>
                        <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#disable2FAModal">
                            {{ __('Deshabilitar') }}
                        </button>
                    </div>

                    <!-- Modal Deshabilitar -->
                    <div class="modal fade" id="disable2FAModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">{{ __('Confirmar Desactivación') }}</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <form method="POST" action="{{ route('two-factor.disable') }}">
                                    @csrf
                                    @method('DELETE')
                                    <div class="modal-body">
                                        <p>{{ __('Ingrese su contraseña actual para confirmar.') }}</p>
                                        <div class="form-group">
                                            <input type="password" name="password" class="form-control" required placeholder="Contraseña">
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('Cancelar') }}</button>
                                        <button type="submit" class="btn btn-danger">{{ __('Desactivar 2FA') }}</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    @else
                    {{-- 2FA NO ESTÁ HABILITADO --}}
                    <div class="mt-4">
                        <h5>{{ __('Habilitar 2FA') }}</h5>
                        <p>{{ __('Para habilitar la autenticación de dos factores, escanee el siguiente código QR usando la aplicación autenticadora de su teléfono (Google Authenticator, Authy, etc.).') }}</p>

                        <div class="text-center mb-4">
                            {!! $qrCodeUrl !!}
                        </div>

                        <div class="alert alert-info">
                            {{ __('Clave de configuración manual: ') }} <strong>{{ $secret }}</strong>
                        </div>

                        <form method="POST" action="{{ route('two-factor.enable') }}">
                            @csrf
                            <input type="hidden" name="secret" value="{{ $secret }}">
                            <div class="form-group">
                                <label for="code">{{ __('Ingrese el código de verificación generado por su aplicación:') }}</label>
                                <input type="text" name="code" id="code" class="form-control col-md-4 mx-auto text-center" required autofocus autocomplete="off">
                            </div>
                            <a href="{{ route('panel') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left mr-1"></i> {{ __('Volver') }}
                            </a>
                            <button type="submit" class="btn btn-primary">
                                {{ __('Confirmar y Habilitar') }}
                            </button>
                        </form>
                    </div>
                    @endif

                    @if ($user->two_factor_confirmed_at)
                    <div class="mt-4 border-top pt-3">
                        <a href="{{ route('panel') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left mr-1"></i> {{ __('Volver al Panel Principal') }}
                        </a>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection