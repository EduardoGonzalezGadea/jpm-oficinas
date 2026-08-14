/**
 * Lógica para el cambio de tema dinámico de Bootswatch
 *
 * Estrategia de persistencia:
 *  - Usuario autenticado: el tema se guarda en la BD (columna users.theme).
 *    El layout (app.blade.php) renderiza el <link> correcto desde el servidor.
 *  - Usuario no autenticado (login): se lee desde localStorage/cookies para
 *    mostrar el último tema elegido en la página de login.
 *  - Al cambiar el tema: se actualiza el DOM, se guarda en localStorage
 *    (para la página de login) y se notifica al backend (para persistir en BD).
 */
(() => {
    // Si el script ya se ejecutó, salir
    if (window.themeScriptLoaded) return;
    window.themeScriptLoaded = true;

    // Obtener la URL base de la aplicación (para manejar subcarpetas)
    const getBaseUrl = () => {
        // 1. Intentar extraer desde el src del propio script (más confiable)
        //    El script se carga desde {baseUrl}/js/theme-change.js
        const scripts = document.querySelectorAll('script[src*="theme-change.js"]');
        if (scripts.length > 0) {
            const src = scripts[scripts.length - 1].src;
            // Quitar todo desde /js/theme-change.js en adelante
            const idx = src.indexOf('/js/theme-change.js');
            if (idx > 0) {
                return src.substring(0, idx);
            }
        }

        // 2. Intentar usar el elemento base si existe
        const baseElement = document.querySelector('base');
        if (baseElement) {
            return baseElement.href.replace(/\/$/, '');
        }

        // 3. Usar el meta base-url inyectado por el layout
        const baseMeta = document.querySelector('meta[name="base-url"]');
        if (baseMeta) {
            return baseMeta.getAttribute('content').replace(/\/$/, '');
        }

        // 4. Si window.BASE_URL está definido, usarlo
        if (window.BASE_URL) {
            return String(window.BASE_URL).replace(/\/$/, '');
        }

        // 5. Como último recurso, usar el origin
        return window.location.origin;
    };

    const baseUrl = getBaseUrl();

    // Funciones helper para manejo de cookies
    const setCookie = (name, value, days = 365) => {
        const expires = new Date();
        expires.setTime(expires.getTime() + days * 24 * 60 * 60 * 1000);
        document.cookie = `${name}=${encodeURIComponent(value)};expires=${expires.toUTCString()};path=/;SameSite=Lax`;
    };

    const getCookie = (name) => {
        const value = `; ${document.cookie}`;
        const parts = value.split(`; ${name}=`);
        if (parts.length === 2) {
            return decodeURIComponent(parts.pop().split(';').shift());
        }
        return null;
    };

    // Lista de temas oscuros conocidos de Bootswatch
    const DARK_THEMES = ["darkly", "slate", "cyborg", "superhero", "vapor", "material"];

    /**
     * Aplica o remueve la clase .dark-theme en el elemento <html>
     * según si el nombre del tema está en la lista de oscuros
     */
    function applyDarkThemeClass(themeName) {
        const isDark = DARK_THEMES.includes(themeName);
        if (isDark) {
            document.documentElement.classList.add('dark-theme');
        } else {
            document.documentElement.classList.remove('dark-theme');
        }
        // También establecer atributo data-theme como respaldo legacy
        document.documentElement.setAttribute('data-theme', themeName);
    }

    /**
     * Devuelve la ruta (path) del CSS para un nombre de tema dado.
     */
    function themePathFor(themeName) {
        const paths = {
            'cerulean': 'libs/bootswatch@4.6.2/dist/cerulean/bootstrap.min.css',
            'cosmo':    'libs/bootswatch@4.6.2/dist/cosmo/bootstrap.min.css',
            'litera':   'libs/bootswatch@4.6.2/dist/litera/bootstrap.min.css',
            'cyborg':   'libs/bootswatch@4.6.2/dist/cyborg/bootstrap.min.css',
            'darkly':   'libs/bootswatch@4.6.2/dist/darkly/bootstrap.min.css',
            'material': 'libs/bootswatch@4.6.2/dist/materia/bootstrap.min.css',
            'default':  'libs/bootstrap-4.6.2-dist/css/bootstrap.min.css',
        };
        return paths[themeName] || paths['cosmo'];
    }

    const setupTheme = () => {
        const themeStylesheet = document.getElementById("bootswatch-theme");
        const defaultThemeName = "cosmo";

        // Determinar si el usuario está autenticado
        const userAuthenticated = document.querySelector('meta[name="user-authenticated"]')?.content === 'true';

        // El nombre del tema actual se determina así:
        // 1. Si el usuario está autenticado, el <link> ya fue renderizado desde
        //    el servidor con el tema de la BD. Leemos el data-theme-name del <link>.
        // 2. Si no está autenticado (login), leemos de localStorage/cookies.
        let currentThemeName = null;

        if (userAuthenticated && themeStylesheet) {
            // El servidor ya cargó el tema correcto; lo respetamos.
            currentThemeName = themeStylesheet.getAttribute('data-theme-name') || defaultThemeName;
        } else {
            // Usuario no autenticado: leer de localStorage (respaldo cookies)
            currentThemeName = localStorage.getItem("bootswatch-theme-name");
            if (!currentThemeName) {
                currentThemeName = getCookie("bootswatch-theme-name");
            }

            if (!currentThemeName) {
                currentThemeName = defaultThemeName;
            }

            // Aplicar el tema al <link> si existe
            if (themeStylesheet) {
                themeStylesheet.setAttribute("href", baseUrl + "/" + themePathFor(currentThemeName));
            }
        }

        // Aplicar la clase dark-theme según el tema actual
        applyDarkThemeClass(currentThemeName);

        // Actualizar el indicador de activo en el menú al cargar la página
        updateActiveThemeIndicator(currentThemeName);

        // Manejar el clic en los botones del selector mediante delegación de eventos
        // (evita que balanceSistemaMenu() rompa los listeners al reconstruir el DOM)
        if (!document._themeClickDelegated) {
            document._themeClickDelegated = true;
            document.addEventListener('click', function (event) {
                const button = event.target.closest('.theme-select-button');
                if (!button) return;
                event.preventDefault();
                const themeName = button.dataset.themeName;
                const themePath = button.dataset.themePath;
                applyThemeChange(themeName, themePath);
                const userAuth = document.querySelector('meta[name="user-authenticated"]')?.content === 'true';
                if (!userAuth) return;
                fetch(baseUrl + "/tema/cambiar", {
                    method: "POST",
                    credentials: "include",
                    headers: {
                        "Content-Type": "application/json",
                        "Accept": "application/json",
                        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute("content"),
                    },
                    body: JSON.stringify({ theme: themeName }),
                })
                .then(response => {
                    if (response.status === 419 || response.status === 401) {
                        if (window.handleSessionExpired) {
                            response.clone().json().then(function (payload) {
                                window.handleSessionExpired({ message: payload.message || payload.error, redirect: payload.redirect });
                            }).catch(function () { window.handleSessionExpired(); });
                        } else {
                            window.location.href = baseUrl + '/login';
                        }
                        return;
                    }
                    if (!response.ok) console.error('Error al guardar el tema en el servidor');
                    return response.json();
                })
                .then(data => { if (data) console.log('Tema guardado:', data); })
                .catch(error => { console.error('Error de red al guardar el tema:', error); });
            });
        }
    };

    function applyThemeChange(themeName, themePath) {
        const themeStylesheet = document.getElementById("bootswatch-theme");

        // Cambiar el CSS en el DOM instantáneamente
        if (themeStylesheet) {
            themeStylesheet.setAttribute("href", themePath);
            // Actualizar el atributo data-theme-name para que setupTheme
            // pueda leerlo en futuras cargas si el usuario está autenticado.
            themeStylesheet.setAttribute("data-theme-name", themeName);
        }

        // Aplicar color de fondo inmediatamente sin transiciones
        const isDark = DARK_THEMES.includes(themeName);

        document.documentElement.style.backgroundColor = isDark
            ? "#222222"
            : "#ffffff";
        document.body.style.backgroundColor = isDark ? "#222222" : "#ffffff";

        // Aplicar la clase dark-theme según el tema
        applyDarkThemeClass(themeName);

        // Guardar la elección en localStorage y cookies (para página de login)
        try {
            localStorage.setItem("bootswatch-theme", themePath);
            localStorage.setItem("bootswatch-theme-name", themeName);
        } catch (e) {
            console.warn("LocalStorage no disponible, guardando solo en cookies");
        }

        // Siempre guardar en cookies como respaldo
        setCookie("bootswatch-theme", themePath);
        setCookie("bootswatch-theme-name", themeName);

        // Actualizar la marca de 'activo' en la UI
        updateActiveThemeIndicator(themeName);
    }

    function updateActiveThemeIndicator(activeThemeName) {
        document
            .querySelectorAll(".theme-active-indicator")
            .forEach((span) => (span.style.display = "none"));

        document.querySelectorAll(".theme-select-button").forEach((button) => {
            if (button.dataset.themeName === activeThemeName) {
                const indicator = button.querySelector(
                    ".theme-active-indicator"
                );
                if (indicator) {
                    indicator.style.display = "inline";
                }
            }
        });
    }

    // Inicializar cuando el DOM esté listo
    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", setupTheme);
    } else {
        setupTheme();
    }

    // Reinicializar cuando Livewire haga una actualización
    document.addEventListener("livewire:init", () => {
        setupTheme();
    });
})();