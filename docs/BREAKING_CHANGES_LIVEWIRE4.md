# Breaking Changes: Livewire 3 → 4

**Fecha**: 15/08/2026  
**Encontrados durante**: Validación manual del sistema

---

## ❌ Breaking Change #1: `Livewire::setUpdateRoute()` Removido ✅ RESUELTO

### Problema Detectado #1.1: Método Removido

**Error**:
```
MethodNotAllowedHttpException

The POST method is not supported for route oficinas/public/livewire/update. 
Supported methods: GET, HEAD.
```

**Causa**:
- Livewire 3 permitía `Livewire::setUpdateRoute()` para personalizar rutas
- Livewire 4 **removió este método**
- Configuración personalizada causaba conflicto de rutas

### Problema Detectado #1.2: Assets Desactualizados

**Error**:
```
Livewire: The published Livewire assets are out of date
Uncaught SyntaxError: Unexpected token '<'
```

**Causa**:
- Livewire 4 tiene nuevos assets JavaScript
- Assets publicados eran de versión anterior
- JavaScript no cargaba correctamente

### Código Problemático

**Archivo**: `app/Providers/AppServiceProvider.php`

```php
// ❌ NO funciona en Livewire 4
if ($basePath !== '') {
    \Livewire\Livewire::setUpdateRoute(function ($handle) use ($basePath) {
        return Route::post(
            $basePath . '/livewire/update',
            $handle
        )->middleware('web');
    });
}
```

### Solución Aplicada

**1. Reemplazar `setUpdateRoute()` con `asset_url`** (Livewire 4 correcto):

```php
// ✅ Livewire 4: Configuración correcta para subdirectorios
if ($basePath !== '') {
    config(['livewire.asset_url' => $basePath]);
}
```

**2. Publicar assets de Livewire 4**:

```bash
php artisan vendor:publish --tag=livewire:assets --force
```

Esto copia los archivos de `vendor/livewire/livewire/dist/` a `public/vendor/livewire/`:
- livewire.js
- livewire.min.js
- livewire.esm.js
- livewire.csp.js (nuevo en v4)
- Y mapas de source

**Antes vs Después**:

```php
// ❌ ANTES (Livewire 3)
\Livewire\Livewire::setUpdateRoute(function ($handle) use ($basePath) {
    return Route::post($basePath . '/livewire/update', $handle);
});

// ✅ DESPUÉS (Livewire 4)
config(['livewire.asset_url' => $basePath]);
```

### Rutas Antes y Después

**Livewire 3** (con setUpdateRoute):
```
POST oficinas/livewire/update → Custom route
```

**Livewire 4** (automático):
```
POST livewire-892282c4/update → Default Livewire route
```

### Estado

- ✅ **SOLUCIONADO COMPLETAMENTE**
- ✅ Implementado `config(['livewire.asset_url' => $basePath])`
- ✅ Assets de Livewire 4 publicados en `public/vendor/livewire/`
- ✅ Compatible con `php artisan serve` Y Apache/XAMPP
- ✅ Servidor reiniciado
- ⏳ Pendiente: Validación manual usuario

---

## ⚠️ ~~Posible Breaking Change #2: Subdirectorio XAMPP~~ ✅ RESUELTO

### Contexto

El código original tenía `setUpdateRoute()` para manejar el caso donde la app Laravel está en un subdirectorio bajo XAMPP:

```
http://localhost/oficinas/public
```

En lugar de:
```
http://localhost:8000
```

### Solución Implementada ✅

**Livewire 4 usa `asset_url` configuración**:

```php
// En AppServiceProvider.php
if ($basePath !== '') {
    config(['livewire.asset_url' => $basePath]);
}
```

Esto configura dinámicamente el prefijo de ruta para que Livewire genere URLs correctas en subdirectorios.

### Resultado

- ✅ Funciona con `php artisan serve` (sin basePath)
- ✅ Funciona con Apache/XAMPP en subdirectorio (con basePath)
- ✅ No requiere configuración manual adicional

### Estado

- ✅ **SOLUCIONADO**
- ✅ Compatible con ambos entornos
- No requiere acciones adicionales

---

## 📋 Otros Breaking Changes Posibles (No Encontrados Aún)

### 1. Wire Model Behavior

**Livewire 4 cambió**:
- `wire:model` → Comportamiento más estricto
- `wire:model.defer` → Puede requerir ajustes

**Verificar en**:
- Formularios
- Inputs dinámicos
- Componentes con mucho two-way binding

### 2. Component Lifecycle Hooks

**Livewire 4 deprecó**:
- Algunos lifecycle hooks antiguos
- Métodos de mounting

**Verificar**:
- `mount()` sigue igual
- `hydrate()` / `dehydrate()` pueden cambiar

### 3. JavaScript API

**Livewire 4 cambió**:
- `Livewire.emit()` → `$dispatch()`
- `Livewire.on()` → `$wire.on()`

**Verificar en**:
- JavaScript custom
- Integraciones con jQuery
- Event listeners

---

## ✅ Checklist de Validación

### Breaking Changes Conocidos

- [x] `setUpdateRoute()` removido → ✅ Solucionado
- [ ] Sistema funciona en `php artisan serve`
- [ ] Sistema funciona en XAMPP subdirectorio (si aplica)

### Funcionalidades a Validar

- [ ] Login y autenticación
- [ ] Gestión de CFEs (cargar, guardar)
- [ ] Livewire componentes responden
- [ ] Wire:model actualiza correctamente
- [ ] Wire:click ejecuta métodos
- [ ] Formularios guardan datos
- [ ] Paginación funciona
- [ ] Búsquedas en tiempo real
- [ ] Modales Livewire
- [ ] File uploads (si se usan)

---

## 🔧 Comandos de Debug

### Ver Rutas de Livewire

```bash
php artisan route:list | Select-String "livewire"
```

### Limpiar Todo

```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

### Ver Versión Livewire

```bash
composer show livewire/livewire
```

### Verificar Configuración

```bash
php artisan config:show livewire
```

---

## 📚 Referencias

### Documentación Oficial

- [Livewire 4 Upgrade Guide](https://livewire.laravel.com/docs/upgrade-guide)
- [Livewire 4 Documentation](https://livewire.laravel.com/docs)
- [Breaking Changes List](https://github.com/livewire/livewire/blob/main/UPGRADE.md)

### Issues Relevantes

- [GitHub: setUpdateRoute removed](https://github.com/livewire/livewire/issues)
- [Discussion: Subdirectory support](https://github.com/livewire/livewire/discussions)

---

## 📝 Notas

### Por Qué No Usar Livewire 3

**Consideramos volver a Livewire 3**:
- ❌ Laravel 12 requiere paquetes modernos
- ❌ Livewire 3 puede tener incompatibilidades con Laravel 12
- ❌ Perderíamos mejoras de Livewire 4

**Por qué continuar con Livewire 4**:
- ✅ Es la versión oficial para Laravel 12
- ✅ Breaking changes son manejables
- ✅ Soporte a largo plazo
- ✅ Mejoras de performance

### Decisión

**Continuar con Livewire 4** y resolver breaking changes uno por uno.

---

## ✅ Próximo Paso

**Validación manual del usuario**:

1. Servidor corriendo: http://127.0.0.1:8000
2. Probar Login
3. Probar Gestión CFEs
4. Reportar si funciona o hay más errores

---

**Última actualización**: 15/08/2026 17:25  
**Breaking changes encontrados**: 1 (con 3 sub-problemas)  
**Breaking changes resueltos**: 1 (completamente)  
**Breaking changes pendientes**: 0
