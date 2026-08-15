# Diagnóstico: Problema con Carga de CFE

## 🔍 Síntoma
Al intentar cargar un CFE desde la gestión de recaudaciones:
- Aparece un loader por unos segundos
- El loader desaparece
- No ocurre nada más (no se abre modal ni mensaje de error)

## 📋 Análisis del Código

### Componente: `app/Livewire/Tesoreria/GestionCfe/Index.php`
- ✅ Usa trait `WithFileUploads`
- ✅ Usa trait `WithConfirmacionCarga`
- ✅ Define propiedad `public $archivoPdf;`

### Vista: `resources/views/livewire/tesoreria/gestion-cfe/index.blade.php`
```html
<input type="file" id="archivoPdfInput" wire:model.live="archivoPdf" class="d-none" accept="application/pdf">
```

### Trait: `app/Livewire/Tesoreria/GestionCfe/WithConfirmacionCarga.php`
```php
public function updatedArchivoPdf(): void
{
    $this->validate([
        'archivoPdf' => 'required|mimes:pdf|max:5120',
    ]);

    try {
        $parser = app(CfeUniversalParserService::class);
        $datos = $parser->parsePdf($this->archivoPdf->getRealPath());
        // ... más código
    } catch (\Throwable $e) {
        $this->dispatch('swal:modal', type: 'error', title: 'Error al procesar', text: 'Hubo un problema procesando el archivo: ' . $e->getMessage());
    }

    $this->reset('archivoPdf');
}
```

## 🐛 Posibles Causas

### 1. Error en Validación
El archivo puede estar fallando la validación silenciosamente:
- Tamaño > 5MB
- No es un PDF válido
- MIME type incorrecto

### 2. Error en Parser
El servicio `CfeUniversalParserService` puede estar fallando al procesar el PDF.

### 3. Configuración de Livewire
- Límite de tamaño de archivo en Livewire
- Timeout en la subida
- Configuración de PHP (upload_max_filesize, post_max_size)

### 4. Error JavaScript
- Evento no se dispara correctamente
- Conflicto con otros scripts

## 🔧 Soluciones Propuestas

### Solución 1: Agregar Logging Detallado

Modificar `WithConfirmacionCarga.php` para agregar logs:

```php
public function updatedArchivoPdf(): void
{
    Log::info('updatedArchivoPdf: Iniciando carga de archivo', [
        'archivo_nombre' => $this->archivoPdf->getClientOriginalName(),
        'archivo_size' => $this->archivoPdf->getSize(),
        'archivo_mime' => $this->archivoPdf->getMimeType(),
    ]);

    try {
        $this->validate([
            'archivoPdf' => 'required|mimes:pdf|max:5120',
        ]);
        
        Log::info('updatedArchivoPdf: Validación exitosa');
        
        // ... resto del código
    } catch (\Illuminate\Validation\ValidationException $e) {
        Log::error('updatedArchivoPdf: Error de validación', [
            'errors' => $e->errors(),
        ]);
        
        $errores = collect($e->errors())->flatten()->implode(' ');
        $this->dispatch('swal:toast-error', text: "Error de validación: {$errores}");
        $this->reset('archivoPdf');
        return;
    } catch (\Throwable $e) {
        Log::error('updatedArchivoPdf: Error general', [
            'mensaje' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
        
        $this->dispatch('swal:modal', type: 'error', title: 'Error al procesar', text: 'Hubo un problema procesando el archivo: ' . $e->getMessage());
        $this->reset('archivoPdf');
        return;
    }
}
```

### Solución 2: Verificar Configuración PHP

Verificar en `php.ini`:
```ini
upload_max_filesize = 10M
post_max_size = 10M
max_execution_time = 120
memory_limit = 256M
```

### Solución 3: Verificar Configuración Livewire

En `config/livewire.php`:
```php
'temporary_file_upload' => [
    'disk' => env('LIVEWIRE_TMP_DISK', 'local'),
    'rules' => ['file', 'max:10240'], // 10MB
    'directory' => 'livewire-tmp',
    'middleware' => null,
],
```

### Solución 4: Agregar Validación Frontend

En la vista, agregar validación JavaScript antes de la subida:

```html
<input type="file" id="archivoPdfInput" 
    wire:model.live="archivoPdf" 
    class="d-none"
    accept="application/pdf"
    onchange="validarArchivo(this)">

<script>
function validarArchivo(input) {
    if (input.files && input.files[0]) {
        const archivo = input.files[0];
        const maxSize = 5 * 1024 * 1024; // 5MB
        
        console.log('Archivo seleccionado:', {
            nombre: archivo.name,
            tipo: archivo.type,
            tamaño: archivo.size,
        });
        
        if (archivo.size > maxSize) {
            Swal.fire({
                icon: 'error',
                title: 'Archivo muy grande',
                text: 'El archivo no debe superar 5MB',
            });
            input.value = '';
            return false;
        }
        
        if (archivo.type !== 'application/pdf') {
            Swal.fire({
                icon: 'error',
                title: 'Tipo de archivo inválido',
                text: 'Solo se permiten archivos PDF',
            });
            input.value = '';
            return false;
        }
    }
}
</script>
```

## 📝 Pasos para Diagnosticar

1. **Revisar logs de Laravel:**
   ```bash
   tail -f storage/logs/laravel.log
   ```

2. **Intentar cargar un CFE y verificar qué se registra en el log**

3. **Revisar la consola del navegador:**
   - Abrir DevTools (F12)
   - Ir a la pestaña "Console"
   - Intentar cargar el archivo
   - Ver si hay errores JavaScript

4. **Revisar la pestaña "Network" del navegador:**
   - Ver si la petición se envía
   - Ver qué código de respuesta retorna
   - Ver el payload de la respuesta

5. **Verificar permisos de carpetas:**
   ```bash
   # Verificar que estas carpetas sean escribibles
   storage/app/livewire-tmp
   storage/app/cfes_cargados
   ```

## 🎯 Siguiente Acción

Aplicar la **Solución 1** (agregar logging detallado) y luego intentar cargar un archivo para ver qué se registra en los logs.
