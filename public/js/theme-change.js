/**
 * Lógica para el cambio de tema dinámico de Bootswatch
 */
(() => {
    // Si el script ya se ejecutó, salir
    if (window.themeScriptLoaded) return;
    window.themeScriptLoaded = true;

    const setupTheme = () => {
        const themeStylesheet = document.getElementById("bootswatch-theme");
        const defaultThemePath =
            "{{ assets('libs/bootstrap-4.6.2-dist/css/bootstrap.min.css') }}";
        const defaultThemeName = "bootstrap-default";

        // Cargar el tema guardado en LocalStorage al iniciar
        let savedThemePath = localStorage.getItem("bootswatch-theme");
        let savedThemeName = localStorage.getItem("bootswatch-theme-name");

        if (!savedThemePath) {
            // Si no hay tema en LocalStorage, usar el por defecto y guardarlo
            savedThemePath = defaultThemePath;
            savedThemeName = defaultThemeName;
            localStorage.setItem("bootswatch-theme", savedThemePath);
            localStorage.setItem("bootswatch-theme-name", savedThemeName);
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

                // Notificar al backend
                fetch("/tema/cambiar", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": document
                            .querySelector('meta[name="csrf-token"]')
                            .getAttribute("content"),
                    },
                    body: JSON.stringify({ theme: themeName }),
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

        // Guardar la elección en LocalStorage
        localStorage.setItem("bootswatch-theme", themePath);
        localStorage.setItem("bootswatch-theme-name", themeName);

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
