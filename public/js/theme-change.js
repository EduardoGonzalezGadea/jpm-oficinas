/**
 * Lógica para el cambio de tema dinámico de Bootswatch
 */
document.addEventListener("DOMContentLoaded", () => {
    const themeSwitcherLinks = document.querySelectorAll(
        ".theme-switcher-link"
    );
    const themeStylesheet = document.getElementById("bootswatch-theme");

    // Cargar el tema guardado en LocalStorage al iniciar
    const savedTheme = localStorage.getItem("bootswatch-theme");
    if (savedTheme && themeStylesheet) {
        themeStylesheet.setAttribute("href", savedTheme);
    }

    // Manejar el clic en los botones del selector
    const themeButtons = document.querySelectorAll(".theme-select-button");

    themeButtons.forEach((button) => {
        button.addEventListener("click", function (event) {
            event.preventDefault(); // Prevenir el envío del formulario

            const themeName = this.dataset.themeName; // ej: 'cerulean'
            const themePath = this.dataset.themePath; // ej: 'assets/css/bootswatch/cerulean/bootstrap.min.css'

            // 1. Cambiar el CSS en el DOM instantáneamente
            if (themeStylesheet) {
                themeStylesheet.setAttribute("href", themePath);
            }

            // 2. Guardar la elección en LocalStorage para persistencia
            localStorage.setItem("bootswatch-theme", themePath);
            localStorage.setItem("bootswatch-theme-name", themeName);

            // 3. Actualizar la marca de 'activo' en la UI
            document
                .querySelectorAll(".theme-active-indicator")
                .forEach((span) => (span.style.display = "none"));
            this.querySelector(".theme-active-indicator").style.display =
                "inline";

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
});
