/**
 * Lógica para el cambio de tema dinámico de Bootswatch
 * Incluye respaldo con cookies para compatibilidad con sistemas que no soportan LocalStorage
 */
(() => {
    // Si el script ya se ejecutó, salir
    if (window.themeScriptLoaded) return;
    window.themeScriptLoaded = true;

    // Obtener la URL base de la aplicación (para manejar subcarpetas)
    const getBaseUrl = () => {
        // Primero, intentar usar el elemento base si existe
        const baseElement = document.querySelector('base');
        if (baseElement) {
            return baseElement.href.replace(/\/$/, '');
        }

        // Si estamos en /oficinas/public/*, la ruta base es /oficinas/public
        const pathname = window.location.pathname;
        const matches = pathname.match(/^(.+?)\/(login|dashboard|\/|$)/);
        if (matches) {
            let basePath = matches[1];
            if (basePath === '') basePath = '/oficinas/public';
            return window.location.origin + basePath;
        }

        // Como último recurso, devolver origen + /oficinas/public
        return window.location.origin + '/oficinas/public';
    };

    const baseUrl = getBaseUrl();
    console.log('Base URL para fetch:', baseUrl);

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

    const deleteCookie = (name) => {
        document.cookie = `${name}=;expires=Thu, 01 Jan 1970 00:00:00 GMT;path=/`;
    };

    const setupTheme = () => {
        const themeStylesheet = document.getElementById("bootswatch-theme");
        const defaultThemePath = "/libs/bootswatch@4.6.2/dist/cosmo/bootstrap.min.css";
        const defaultThemeName = "cosmo";

        // Cargar el tema guardado al iniciar (LocalStorage primero, luego cookies como respaldo)
        let savedThemePath = localStorage.getItem("bootswatch-theme");
        let savedThemeName = localStorage.getItem("bootswatch-theme-name");

        // Si no hay en LocalStorage, intentar cargar desde cookies
        if (!savedThemePath) {
            savedThemePath = getCookie("bootswatch-theme");
            savedThemeName = getCookie("bootswatch-theme-name");

            // Si se cargó desde cookies, sincronizar con LocalStorage
            if (savedThemePath && savedThemeName) {
                try {
                    localStorage.setItem("bootswatch-theme", savedThemePath);
                    localStorage.setItem("bootswatch-theme-name", savedThemeName);
                } catch (e) {
                    // Si LocalStorage falla, continuar con cookies
                    console.warn("No se pudo sincronizar con LocalStorage, usando cookies como respaldo");
                }
            }
        }

        if (!savedThemePath) {
            // Si no hay tema guardado en ningún lugar, usar el por defecto y guardarlo
            savedThemePath = defaultThemePath;
            savedThemeName = defaultThemeName;
            try {
                localStorage.setItem("bootswatch-theme", savedThemePath);
                localStorage.setItem("bootswatch-theme-name", savedThemeName);
            } catch (e) {
                // Si LocalStorage falla, guardar en cookies
                setCookie("bootswatch-theme", savedThemePath);
                setCookie("bootswatch-theme-name", savedThemeName);
            }
        }

        if (themeStylesheet) {
            themeStylesheet.setAttribute("href", savedThemePath);
        }

        // Actualizar el indicador de activo en el menú al cargar la página
        updateActiveThemeIndicator(savedThemeName);

        // Manejar el clic en los botones del selector
        const themeButtons = document.querySelectorAll(".theme-select-button");

        themeButtons.forEach((button) => {
            // Evitar registrar múltiples listeners
            if (button.dataset.themeHandlerAttached) return;
            button.dataset.themeHandlerAttached = true;

            button.addEventListener("click", function (event) {
                event.preventDefault();

                const themeName = this.dataset.themeName;
                const themePath = this.dataset.themePath;

                applyThemeChange(themeName, themePath);

                // IMPORTANTE: Solo notificar al backend si el usuario está logueado
                // En la página de login no hay botones con esta clase, pero por seguridad lo validamos
                if (document.querySelector('meta[name="user-authenticated"]')?.content === 'false') {
                    return;
                }

                // Notificar al backend
                fetch(baseUrl + "/tema/cambiar", {
                    method: "POST",
                    credentials: "include", // IMPORTANTE: Enviar cookies para mantener la sesión
                    headers: {
                        "Content-Type": "application/json",
                        "Accept": "application/json",
                        "X-CSRF-TOKEN": document
                            .querySelector('meta[name="csrf-token"]')
                            .getAttribute("content"),
                    },
                    body: JSON.stringify({ theme: themeName }),
                })
                    .then(response => {
                        if (response.status === 419) {
                            // Si la sesión expiró justo ahora, recargamos para limpiar todo
                            window.location.reload();
                            return;
                        }
                        if (!response.ok) {
                            console.error('Error al guardar el tema en el servidor');
                        }
                        return response.json();
                    })
                    .then(data => {
                        console.log('Tema guardado exitosamente:', data);
                    })
                    .catch(error => {
                        console.error('Error de red al intentar guardar el tema:', error);
                    });
            });
        });
    };

    function applyThemeChange(themeName, themePath) {
        const themeStylesheet = document.getElementById("bootswatch-theme");

        // Cambiar el CSS en el DOM instantáneamente
        if (themeStylesheet) {
            themeStylesheet.setAttribute("href", themePath);
        }

        // Aplicar color de fondo inmediatamente sin transiciones
        const darkThemes = ["darkly", "slate", "cyborg", "materia"];
        const isDark = darkThemes.includes(themeName);

        document.documentElement.style.backgroundColor = isDark
            ? "#222222"
            : "#ffffff";
        document.body.style.backgroundColor = isDark ? "#222222" : "#ffffff";

        // Guardar la elección en LocalStorage y cookies (doble respaldo)
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
    document.addEventListener("livewire:load", () => {
        setupTheme();
    });
})();
