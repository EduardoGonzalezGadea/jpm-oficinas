<!-- Menú de Tesorería -->
<div class="nav-item dropdown">
    <a href="#" class="nav-link dropdown-toggle" data-toggle="dropdown">
        <i class="fas fa-cash-register"></i>
        <span>Tesorería</span>
    </a>
    <div class="dropdown-menu">
        <!-- Cajas -->
        <div class="dropdown-submenu">
            <a class="dropdown-item dropdown-toggle" href="#" data-toggle="dropdown">
                <i class="fas fa-cash-register"></i> Cajas
            </a>
            <div class="dropdown-menu">
                <a class="dropdown-item" href="{{ route('cajas.apertura-cierre') }}">
                    <i class="fas fa-door-open"></i> Apertura/Cierre
                </a>
                <a class="dropdown-item" href="{{ route('cajas.movimientos') }}">
                    <i class="fas fa-exchange-alt"></i> Movimientos
                </a>
                <a class="dropdown-item" href="{{ route('cajas.arqueo') }}">
                    <i class="fas fa-balance-scale"></i> Arqueo
                </a>
            </div>
        </div>

        <!-- Caja Chica -->
        <a class="dropdown-item" href="{{ route('caja-chica.index') }}">
            <i class="fas fa-box"></i> Caja Chica
        </a>

        <!-- Valores -->
        <div class="dropdown-divider"></div>
        <a class="dropdown-item" href="{{ route('tesoreria.valores.index') }}">
            <i class="fas fa-money-check-alt"></i> Valores
        </a>
    </div>
</div>

<!-- Estilos para submenús -->
<style>
    .dropdown-submenu {
        position: relative;
    }

    .dropdown-submenu .dropdown-menu {
        top: 0;
        left: 100%;
        margin-top: -1px;
    }

    .dropdown-submenu:hover>.dropdown-menu {
        display: block;
    }
</style>

<!-- Script para submenús -->
<script>
    $(document).ready(function() {
        $('.dropdown-submenu a.dropdown-toggle').on("click", function(e) {
            $(this).next('div').toggle();
            e.stopPropagation();
            e.preventDefault();
        });
    });
</script>
