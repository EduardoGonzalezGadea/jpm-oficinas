<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Iniciar Sesión - JPM Oficinas</title>

    <!-- Lógica para el tema dinámico -->
    <script src="{{ asset('js/theme-change.js') }}"></script>

    <!-- Bootstrap 4 CSS -->
    <link href="{{ asset('libs/bootstrap-4.6.2-dist/css/bootstrap.min.css') }}" rel="stylesheet">
    <!-- Hoja de estilos del tema dinámico -->
    @php
        $themePath = request()->cookie('theme_path', 'libs/bootswatch@4.6.2/dist/cosmo/bootstrap.min.css');
    @endphp
    @if ($themePath)
        <link id="bootswatch-theme" rel="stylesheet" href="{{ asset($themePath) }}">
    @endif
    <!-- Font Awesome -->
    <link href="{{ asset('libs/fontawesome-free-5.15.4-web/css/all.min.css') }}" rel="stylesheet">

    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .login-container {
            max-width: 400px;
            width: 100%;
        }

        @media (max-height: 700px) {
            .login-container {
                max-width: 360px;
            }
        }

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .card-header {
            background: linear-gradient(45deg, #007bff, #0056b3);
            color: white;
            border-radius: 15px 15px 0 0 !important;
            text-align: center;
            padding: 1.5rem;
        }

        .card-body {
            padding: 1.5rem;
        }

        .form-control {
            border-radius: 8px;
            padding: 0.6rem;
            border: 1px solid #ddd;
        }

        .form-control:focus {
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
            border-color: #007bff;
        }

        .btn-primary {
            background: linear-gradient(45deg, #007bff, #0056b3);
            border: none;
            border-radius: 8px;
            padding: 0.6rem;
            font-weight: 600;
            font-size: 1rem;
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .login-icon {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }

        .input-group-text {
            background-color: #f8f9fa;
            border-right: none;
            border-radius: 8px 0 0 8px;
        }

        .input-group .form-control {
            border-left: none;
            border-radius: 0 8px 8px 0;
        }

        .form-group {
            margin-bottom: 0.75rem;
        }

        @media (max-height: 700px) {
            .card-header {
                padding: 1rem;
            }
            .card-body {
                padding: 1rem;
            }
            .login-icon {
                font-size: 2rem;
                margin-bottom: 0.25rem;
            }
            .form-group {
                margin-bottom: 0.5rem;
            }
            .form-control {
                padding: 0.5rem;
            }
            .text-center.mt-4 {
                margin-top: 0.5rem !important;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="login-container">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex align-items-center justify-content-center">
                            <div class="login-icon mr-2">
                                <i class="fas fa-building"></i>
                            </div>
                            <div class="text-left">
                                <h4 class="mb-0 text-white">JPM Oficinas</h4>
                                <small>República Oriental del Uruguay</small>
                                <small class="d-block">Ministerio del Interior</small>
                                <small class="d-block">Jefatura de Policía de Montevideo</small>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        @if (session('success'))
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                {{ session('success') }}
                                <button type="button" class="close" data-dismiss="alert">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                        @endif

                        @if (session('error'))
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                {{ session('error') }}
                                <button type="button" class="close" data-dismiss="alert">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                        @endif


                        <form method="POST" action="{{ route('login') }}">
                            @csrf

                            <div class="form-group" style="margin-bottom: 0.75rem;">
                                <label for="email">Correo Electrónico</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">
                                            <i class="fas fa-envelope"></i>
                                        </span>
                                    </div>
                                    <input type="email" class="form-control @error('email') is-invalid @enderror"
                                        id="email" name="email" value="{{ old('email') }}"
                                        placeholder="Ingresa tu correro electrónico" required autocomplete="email">
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="password">Contraseña</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">
                                            <i class="fas fa-lock"></i>
                                        </span>
                                    </div>
                                    <input type="password" class="form-control @error('password') is-invalid @enderror"
                                        id="password" name="password" placeholder="Ingresa tu contraseña" required
                                        autocomplete="password">
                                </div>
                            </div>

                            <div class="form-group">
                                <button type="submit" class="btn btn-primary btn-block">
                                    <i class="fas fa-sign-in-alt mr-2"></i>
                                    Iniciar Sesión
                                </button>
                            </div>
                        </form>

                        <div class="text-center mt-2">
                            <small class="text-muted">
                                <i class="fas fa-shield-alt"></i>
                                Sistema seguro con autenticación JWT
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 4 JS -->
    <script src="{{ asset('libs/jquery/js/jquery-3.6.0.min.js') }}"></script>
    <script src="{{ asset('libs/bootstrap-4.6.2-dist/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('libs/fontawesome-free-5.15.4-web/js/all.min.js') }}"></script>
</body>

</html>
