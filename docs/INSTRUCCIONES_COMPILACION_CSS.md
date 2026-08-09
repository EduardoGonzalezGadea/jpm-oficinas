# 📦 Instrucciones para Compilación de CSS

## ⚠️ Importante

Después de modificar archivos en `resources/css/app.css`, es necesario **compilar los assets** para que los cambios se reflejen en el navegador.

---

## 🔧 Comandos de Compilación

### Para desarrollo (con watch automático)
```bash
npm run dev
```
Este comando:
- ✅ Compila `resources/css/app.css` → `public/css/app.css`
- ✅ Compila archivos JavaScript
- ✅ Queda en modo "watch" vigilando cambios
- ✅ Recompila automáticamente cuando detecta cambios

### Para desarrollo (compilación única)
```bash
npm run development
```
Este comando compila una sola vez sin quedar vigilando cambios.

### Para producción
```bash
npm run production
```
Este comando:
- ✅ Compila y minifica CSS y JavaScript
- ✅ Optimiza para mejor performance
- ✅ Se debe ejecutar antes de subir cambios al servidor

---

## 📋 Cambios Recientes Realizados

### Fecha: 27/07/2026

**Archivo modificado:** `resources/css/app.css`

**Estilos agregados permanentemente:**

```css
/* Botones de acción con ancho fijo */
.btn-action-fixed {
    width: 30px;
    padding-left: 0;
    padding-right: 0;
}

/* Modal a ancho completo */
.modal-full-width {
    max-width: 95vw;
}

/* Grupos de planillas con outline */
.planilla-group {
    outline: 3px solid #1a73e8;
    outline-offset: -1px;
}

/* Cursor pointer para elementos interactivos */
.cursor-pointer {
    cursor: pointer;
}
```

**Vista actualizada:** `resources/views/livewire/tesoreria/estados-recaudacion/index.blade.php`
- ✅ Removidos estilos inline `<style>` del blade
- ✅ Ahora usa clases del CSS global compilado

---

## 🚀 Flujo de Trabajo

### 1. Durante el desarrollo:
```bash
# Terminal 1: Servidor PHP
php artisan serve

# Terminal 2: Compilación automática de assets
npm run dev
```

### 2. Antes de hacer commit:
```bash
# Compilar para producción
npm run production

# Agregar archivos compilados al commit
git add public/css/app.css
git add public/js/*.js
```

### 3. En el servidor de producción:
```bash
# Después de hacer pull
npm install
npm run production
```

---

## 🔍 Verificación

Para verificar que los estilos se compilaron correctamente:

1. **Inspeccionar en el navegador:**
   - Abrir DevTools (F12)
   - Ir a la pestaña "Network"
   - Recargar la página
   - Buscar `app.css` en la lista
   - Verificar que el tamaño del archivo haya cambiado

2. **Verificar archivo compilado:**
   ```bash
   # Ver las últimas líneas del archivo compilado
   tail -n 50 public/css/app.css
   ```

3. **Limpiar caché del navegador:**
   - Presionar `Ctrl + Shift + R` (Windows/Linux)
   - Presionar `Cmd + Shift + R` (Mac)

---

## ❓ Solución de Problemas

### Los estilos no se aplican después de compilar

**Solución 1: Limpiar caché de Laravel**
```bash
php artisan cache:clear
php artisan view:clear
php artisan config:clear
```

**Solución 2: Forzar recompilación**
```bash
# Eliminar archivos compilados
rm -rf public/css/app.css
rm -rf public/js/*.js

# Recompilar
npm run production
```

**Solución 3: Verificar que Laravel Mix esté correcto**
```bash
# Reinstalar dependencias
rm -rf node_modules
npm install
npm run production
```

### Error "npm: command not found"

Necesitas instalar Node.js:
```bash
# Verificar si Node está instalado
node -v
npm -v

# Si no está instalado, descargar de: https://nodejs.org/
```

---

## 📝 Notas Importantes

1. **Nunca editar `public/css/app.css` directamente** - Este archivo se sobrescribe al compilar
2. **Siempre editar `resources/css/app.css`** - Este es el archivo fuente
3. **Compilar antes de probar** - Los cambios en `resources/` no se reflejan hasta compilar
4. **Incluir archivos compilados en commits** - Para que funcione en otros entornos
5. **Los estilos en archivos blade con `<style>` NO requieren compilación** - Pero es mejor práctica usar CSS global

---

## 🎯 Mejores Prácticas

### ✅ Hacer (Recomendado):
- Agregar estilos reutilizables en `resources/css/app.css`
- Usar clases CSS en los templates blade
- Compilar con `npm run production` antes de commits
- Usar convenciones de nomenclatura claras (BEM, utility-first)

### ❌ Evitar:
- Estilos inline en blade (`<style>` dentro del template)
- Editar directamente archivos en `public/`
- Olvidar compilar antes de subir cambios
- Usar `!important` sin necesidad

---

## 📚 Referencias

- [Laravel Mix Documentation](https://laravel-mix.com/docs)
- [PostCSS Documentation](https://postcss.org/)
- [CSS Custom Properties (Variables)](https://developer.mozilla.org/en-US/docs/Web/CSS/Using_CSS_custom_properties)

---

*Documento creado: 27/07/2026*
*Relacionado con: Mejoras en Estados de Recaudación - Módulo Tesorería*
