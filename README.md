# Sistema de Detección y Procesamiento de CFEs

## Descripción
Este proyecto implementa un sistema para detectar y procesar Comprobantes Fiscales Electrónicos (CFEs) descargados desde el sistema de facturación del estado. La solución consta de dos partes principales:

1. **Backend Laravel**: Procesa PDFs y extrae datos de CFEs
2. **Extensión del Navegador**: Detecta descargas de PDFs desde cualquier URL

## Arquitectura

### Backend Laravel
- **Tabla**: `tes_cfe_pendientes` - Almacena CFEs pendientes de confirmación
- **Modelo**: `TesCfePendiente` - Interfaz para interactuar con la tabla
- **Servicio**: `CfeProcessorService` - Procesa PDFs y extrae datos
- **Controlador API**: `CfeController` - Endpoints REST para gestionar CFEs
- **Livewire**: Componente `CfePendientesIndex` - Interfaz para revisar y confirmar/rechazar CFEs

### Extensión del Navegador
- **manifest.json** - Configuración de la extensión
- **background.js** - Detecta descargas completadas de PDFs
- **popup/** - Interfaz para usuarios
  - `popup.html` - Estructura HTML
  - `popup.js` - Lógica de procesamiento
  - `popup.css` - Estilos

## Flujo de Trabajo

1. **Detección**: La extensión detecta descargas de PDFs desde cualquier URL
2. **Notificación**: Se alerta al usuario sobre el CFE detectado
3. **Procesamiento**: El usuario puede ver CFEs pendientes en la interfaz Livewire
4. **Confirmación**: El usuario puede confirmar o rechazar cada CFE
5. **Actualización**: El estado del CFE se actualiza en la base de datos

## Tecnologías

- Laravel 10
- Livewire 3
- Smalot\PdfParser
- Chrome Extension APIs
- Bootstrap 5

## Estado del Proyecto

Todas las tareas iniciales han sido completadas:
- [x] Crear base de datos y tabla tes_cfe_pendientes
- [x] Crear modelo TesCfePendiente
- [x] Crear servicio CfeProcessorService
- [x] Crear controlador API CfeController
- [x] Crear request ProcesarCfeRequest
- [x] Crear Livewire component CfePendientesIndex
- [x] Crear vista Livewire cfe-pendientes-index.blade.php
- [x] Crear extensión del navegador manifest.json
- [x] Crear background.js
- [x] Crear popup.html
- [x] Crear popup.js
- [x] Crear popup.css
- [x] Crear directorio y archivos de íconos
- [x] Configurar extensión para detectar PDFs desde cualquier URL

### Notas Importantes

- La extensión ahora detecta descargas de PDFs desde cualquier URL, no solo desde dominios específicos del gobierno
- Esto permite probar el sistema con PDFs descargados de cualquier fuente
- El archivo `manifest.json` utiliza `"<all_urls>"` en `host_permissions` para permitir el acceso universal
- El archivo `background.js` ha sido simplificado eliminando la verificación de dominios específicos
- [x] Crear base de datos y tabla tes_cfe_pendientes
- [x] Crear modelo TesCfePendiente
- [x] Crear servicio CfeProcessorService
- [x] Crear controlador API CfeController
- [x] Crear request ProcesarCfeRequest
- [x] Crear Livewire component CfePendientesIndex
- [x] Crear vista Livewire cfe-pendientes-index.blade.php
- [x] Crear extensión del navegador manifest.json
- [x] Crear background.js
- [x] Crear popup.html
- [x] Crear popup.js
- [x] Crear popup.css
- [x] Crear directorio y archivos de íconos

## Uso

1. Instalar dependencias de Laravel
2. Configurar rutas API en `routes/api.php`
3. Levantar servidor de desarrollo
4. Instalar y cargar la extensión del navegador en Chrome/Firefox
5. Procesar CFEs a través de la interfaz

## Contacto

Para soporte o consultas, por favor contacte al equipo de desarrollo.
