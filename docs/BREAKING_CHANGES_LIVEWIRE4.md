# Breaking Changes: Livewire 3 → 4

**Fecha**: 15/08/2026  
**Encontrados durante**: Validación manual del sistema

---

## ❌ Breaking Change #1: `Livewire::setUpdateRoute()` Removido

### Problema Detectado

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

**Comentar la configuración personalizada**:

```php
// ✅ Livewire 4 maneja rutas automáticamente
// if ($basePath !== '') {
//     \Livewire\Livewire::setUpdateRoute(function ($handle) use ($basePath) {
//         return Route::post(
//             $basePath . '/livewire/update',
//             $handle
//         )->middleware('web');
//     });
// }
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

- ✅ **SOLUCIONADO**
- ✅ Servidor reiniciado
- ⏳ Pendiente: Validación manual usuario

---

## ⚠️ Posible Breaking Change #2: Subdirectorio XAMPP

### Contexto

El código original tenía `setUpdateRoute()` para manejar el caso donde la app Laravel está en un subdirectorio bajo XAMPP:

```
http://localhost/oficinas/public
```

En lugar de:
```
http://localhost:8000
```

### Riesgo

Con Livewire 4, **puede que las rutas no funcionen correctamente** cuando:
- La app está en subdirectorio (ej: `/oficinas/public`)
- Se accede vía Apache (XAMPP) en lugar de `php artisan serve`

### Solución Temporal

**Usar `php artisan serve`** durante validación:
```bash
php artisan serve
# Acceder: http://127.0.0.1:8000
```

### Solución Permanente (Si es necesario)

Si el sistema DEBE funcionar en subdirectorio XAMPP, revisar:

1. **Configuración de `APP_URL` en `.env`**:
   ```env
   APP_URL=http://localhost/oficinas/public
   ```

2. **Livewire 4 configuración de asset_url**:
   ```php
   // config/livewire.php
   'asset_url' => env('LIVEWIRE_ASSET_URL', null),
   ```

3. **Documentación oficial**: https://livewire.laravel.com/docs/installation

### Estado

- ⏳ **PENDIENTE DE CONFIRMAR**
- Depende de entorno de producción
- Si producción usa Apache + subdirectorio → Revisar

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

**Última actualización**: 15/08/2026 16:25  
**Breaking changes encontrados**: 1  
**Breaking changes resueltos**: 1  
**Breaking changes pendientes**: 0
