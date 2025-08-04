/**
 * Lógica para el cambio de tema dinámico de Bootswatch
 */
document.addEventListener("DOMContentLoaded", () => {
    const themeStylesheet = document.getElementById("bootswatch-theme");
    const defaultThemePath = "libs/bootstrap-4.6.2-dist/css/bootstrap.min.css";
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
        button.addEventListener("click", function (event) {
            event.preventDefault(); // Prevenir el envío del formulario

            const themeName = this.dataset.themeName; // ej: 'cerulean'
            const themePath = this.dataset.themePath; // ej: 'assets/css/bootswatch/cerulean/bootstrap.min.css'

            // Aplicar cambio de tema
            applyThemeChange(themeName, themePath);

            // 4. (Opcional) Notificar al backend para guardar en sesión
            // Esto es útil si alguna lógica de renderizado en PHP depende del tema
            fetch("{{ route('theme.switch') }}", {
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

    function applyThemeChange(themeName, themePath) {
        // 1. Cambiar el CSS en el DOM instantáneamente
        if (themeStylesheet) {
            themeStylesheet.setAttribute("href", themePath);
        }

        // 2. Aplicar color de fondo inmediatamente sin transiciones
        const darkThemes = ["darkly", "slate", "cyborg", "materia"];
        const isDark = darkThemes.includes(themeName);

        // Aplicar cambios de fondo sin transiciones
        document.documentElement.style.backgroundColor = isDark
            ? "#222222"
            : "#ffffff";
        document.body.style.backgroundColor = isDark ? "#222222" : "#ffffff";

        // 3. Guardar la elección en LocalStorage para persistencia
        localStorage.setItem("bootswatch-theme", themePath);
        localStorage.setItem("bootswatch-theme-name", themeName);

        // 4. Actualizar la marca de 'activo' en la UI
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
});

// Evitar que el script se ejecute múltiples veces en navegaciones de Livewire
if (window.themeScriptLoaded) {
    // El script ya se cargó, no hacer nada
} else {
    window.themeScriptLoaded = true;
}
