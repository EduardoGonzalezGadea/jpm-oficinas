<nav class="navbar navbar-expand-md navbar-dark bg-dark barra-oscura" style="margin-bottom: 0px;">
    <a class="navbar-brand" href="{{ route('panel') }}">
        <i class="fas fa-building mr-2"></i> JPM Oficinas
    </a>

    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav">
        <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav mr-auto">
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownThemes" role="button"
                    data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fas fa-palette mr-2"></i>Estilo
                </a>
                <div class="dropdown-menu" aria-labelledby="navbarDropdownThemes">
                    {{-- Tema por Defecto (Bootstrap Original) --}}
                    <button type="button" class="dropdown-item theme-select-button"
                        data-theme-name="bootstrap-default"
                        data-theme-path="{{ asset('libs/bootstrap-4.6.2-dist/css/bootstrap.min.css') }}">
                        Por defecto
                        <span class="text-success theme-active-indicator" style="display: none;">✔</span>
                    </button>

                    <div class="dropdown-divider"></div>
                    <h6 class="dropdown-header">Temas Claros</h6>

                    {{-- Tema Cosmo --}}
                    <button type="button" class="dropdown-item theme-select-button"
                        data-theme-name="cosmo"
                        data-theme-path="{{ asset('libs/bootswatch@4.6.2/dist/cosmo/bootstrap.min.css') }}">
                        Cosmo
                        <span class="text-success theme-active-indicator" style="display: none;">✔</span>
                    </button>

                    {{-- Tema Cerulean --}}
                    <button type="button" class="dropdown-item theme-select-button"
                        data-theme-name="cerulean"
                        data-theme-path="{{ asset('libs/bootswatch@4.6.2/dist/cerulean/bootstrap.min.css') }}">
                        Cerulean
                        <span class="text-success theme-active-indicator" style="display: none;">✔</span>
                    </button>

                    {{-- Tema Litera --}}
                    <button type="button" class="dropdown-item theme-select-button"
                        data-theme-name="litera"
                        data-theme-path="{{ asset('libs/bootswatch@4.6.2/dist/litera/bootstrap.min.css') }}">
                        Litera
                        <span class="text-success theme-active-indicator" style="display: none;">✔</span>
                    </button>

                    {{-- Tema Materia --}}
                    <button type="button" class="dropdown-item theme-select-button"
                        data-theme-name="material"
                        data-theme-path="{{ asset('libs/bootswatch@4.6.2/dist/materia/bootstrap.min.css') }}">
                        Materia
                        <span class="text-success theme-active-indicator" style="display: none;">✔</span>
                    </button>

                    <div class="dropdown-divider"></div>
                    <h6 class="dropdown-header">Temas Oscuros</h6>

                    {{-- Tema Cyborg --}}
                    <button type="button" class="dropdown-item theme-select-button"
                        data-theme-name="cyborg"
                        data-theme-path="{{ asset('libs/bootswatch@4.6.2/dist/cyborg/bootstrap.min.css') }}">
                        Cyborg
                        <span class="text-success theme-active-indicator" style="display: none;">✔</span>
                    </button>

                    {{-- Tema Darkly --}}
                    <button type="button" class="dropdown-item theme-select-button"
                        data-theme-name="darkly"
                        data-theme-path="{{ asset('libs/bootswatch@4.6.2/dist/darkly/bootstrap.min.css') }}">
                        Darkly
                        <span class="text-success theme-active-indicator" style="display: none;">✔</span>
                    </button>
                </div>
            </li>

            {{-- TESORERÍA --}}
            @can('operador_tesoreria')
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownTesoreria" role="button"
                        data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="fas fa-dollar-sign mr-2"></i>Tesorería
                    </a>
                    <div class="dropdown-menu" aria-labelledby="navbarDropdownTesoreria">
                        {{-- Link Caja Chica --}}
                        <a class="dropdown-item" href="{{ route('tesoreria.caja-chica.index') }}">
                            <i class="fas fa-coins mr-2"></i>Caja Chica
                        </a>
                        {{-- Link Valores --}}
                        <a class="dropdown-item" href="{{ route('tesoreria.valores.index') }}">
                            <i class="fas fa-file-invoice mr-2"></i>Valores
                        </a>
                    </div>
                </li>
            @endcan

            @can('operador_contabilidad')
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('contabilidad.index') }}">
                        <i class="fas fa-calculator mr-2"></i>Contabilidad
                    </a>
                </li>
            @endcan
        </ul>

        <ul class="navbar-nav">
            @can('ver_usuarios')
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('usuarios.index') }}">
                        <i class="fas fa-users mr-2"></i>Usuarios
                    </a>
                </li>
            @endcan

            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" role="button" data-toggle="dropdown">
                    <i class="fas fa-user mr-2"></i>{{ auth()->user()->nombre }} {{ auth()->user()->apellido }}
                </a>
                <div class="dropdown-menu dropdown-menu-right">
                    <a href="{{ route('usuarios.miPerfil') }}" class="dropdown-item">
                        <i class="fas fa-user-edit mr-2"></i>Mi Perfil
                    </a>
                    <div class="dropdown-divider"></div>
                    <form action="{{ route('logout') }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="dropdown-item">
                            <i class="fas fa-sign-out-alt mr-2"></i>Cerrar Sesión
                        </button>
                    </form>
                </div>
            </li>
        </ul>
    </div>
</nav>

<style>
/* La clase que aplicas a tu <nav> */
.barra-oscura {
  /* Establece la posición relativa para que el ::before se posicione correctamente */
  position: relative;
  /* Establece un z-index base para la barra de navegación. */
  z-index: 10;
}

/* El pseudoelemento que oscurece el fondo */
.barra-oscura::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: black;
  mix-blend-mode: multiply;
  opacity: 0.5;
  /* Esto es lo que hace la magia:
     - El z-index negativo lo coloca 'detrás' del contenido.
     - Asegura que el ::before no capture los eventos de clic.
  */
  z-index: -1;
}

/* Opcional: Esto ayuda a que el texto siempre sea claro y legible */
.barra-oscura .navbar-nav .nav-link {
  color: white;
  /* Opcional, para que el texto sea más fácil de leer sobre un fondo oscuro */
  text-shadow: 1px 1px 2px rgba(0,0,0,0.5);
}
.barra-oscura .navbar-nav .nav-link:hover {
  color: yellow;
}
</style>
