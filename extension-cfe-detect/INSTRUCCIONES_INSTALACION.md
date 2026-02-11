# Instrucciones de Instalación y Prueba de la Extensión

## Requisitos Previos

1. **Google Chrome** (versión 88 o superior)
2. **Servidor Laravel** ejecutándose en `http://localhost:8000`
3. **Archivos de la extensión** en el directorio `extension-cfe-detect/`
4. **Archivos de íconos** en `extension-cfe-detect/icons/` (icon16.png, icon48.png, icon128.png)

### Creación de Íconos

Si los archivos de íconos no existen o están vacíos, puedes crearlos de las siguientes formas:

**Opción 1: Usar íconos existentes del proyecto**
```bash
copy public\images\icons\policia.png extension-cfe-detect\icons\icon16.png
copy public\images\icons\policia.png extension-cfe-detect\icons\icon48.png
copy public\images\icons\policia.png extension-cfe-detect\icons\icon128.png
```

**Opción 2: Crear íconos simples**
- Puedes usar cualquier editor de imágenes (Paint, GIMP, Photoshop, etc.)
- Crea tres imágenes PNG con las siguientes dimensiones:
  - icon16.png: 16x16 píxeles
  - icon48.png: 48x48 píxeles
  - icon128.png: 128x128 píxeles
- Guárdalas en el directorio `extension-cfe-detect/icons/`

**Opción 3: Usar generadores de íconos online**
- Visita sitios como: https://www.favicon-generator.org/ o https://realfavicongenerator.net/
- Sube una imagen y descarga los íconos en los tamaños necesarios
- Renombra los archivos a icon16.png, icon48.png y icon128.png

## Instalación de la Extensión en Chrome

### Verificación previa de archivos

Antes de instalar la extensión, verifica que todos los archivos necesarios existan:

```bash
# Verificar archivos principales
dir extension-cfe-detect\manifest.json
dir extension-cfe-detect\background.js
dir extension-cfe-detect\popup\popup.html
dir extension-cfe-detect\popup\popup.js
dir extension-cfe-detect\popup\popup.css

# Verificar archivos de íconos
dir extension-cfe-detect\icons\icon16.png
dir extension-cfe-detect\icons\icon48.png
dir extension-cfe-detect\icons\icon128.png
```

Si algún archivo falta, créalo siguiendo las instrucciones de la sección "Creación de Íconos".

### Paso 1: Abrir Chrome en modo desarrollador

1. Abre Google Chrome
2. En la barra de direcciones, escribe: `chrome://extensions`
3. Presiona Enter

### Paso 2: Habilitar el modo desarrollador

1. En la esquina superior derecha, activa el interruptor **"Modo desarrollador"** (Developer mode)
2. Verás que aparecen nuevos botones en la parte superior izquierda

### Paso 3: Cargar la extensión

1. Haz clic en el botón **"Cargar descomprimida"** (Load unpacked)
2. Navega hasta el directorio: `c:\xampp\htdocs\oficinas\extension-cfe-detect`
3. Selecciona la carpeta `extension-cfe-detect` y haz clic en **"Seleccionar carpeta"**

### Paso 4: Verificar la instalación

1. La extensión debería aparecer en la lista de extensiones
2. Verás el nombre: "Detector de CFEs - Tesorería"
3. Asegúrate de que el interruptor esté activado
4. Si ves errores relacionados con los íconos, verifica que los archivos existan en `extension-cfe-detect/icons/`

## Configuración del Backend

### Verificación previa del servidor

Antes de probar la extensión, verifica que el servidor Laravel esté funcionando:

```bash
# Iniciar el servidor Laravel
php artisan serve
```

El servidor debería iniciarse en `http://127.0.0.1:8000` o `http://localhost:8000`.

Verifica que puedas acceder a:
- `http://localhost:8000` - Página principal de Laravel
- `http://localhost:8000/api/cfe/pendientes` - Endpoint de CFEs pendientes

### Paso 1: Ejecutar las migraciones

Abre una terminal en el directorio del proyecto y ejecuta:

```bash
php artisan migrate
```

Esto creará la tabla `tes_cfe_pendientes` en la base de datos.

Esto creará la tabla `tes_cfe_pendientes` en la base de datos.

### Paso 2: Iniciar el servidor Laravel

```bash
php artisan serve
```

El servidor debería iniciarse en `http://127.0.0.1:8000` o `http://localhost:8000`.

### Paso 3: Verificar que los endpoints API funcionen

Puedes probar los endpoints usando Postman o curl:

Puedes probar los endpoints usando Postman o curl:

```bash
# Ver CFEs pendientes
curl http://localhost:8000/api/cfe/pendientes
```

## Prueba de la Extensión

### Prueba 1: Descargar un PDF de prueba

1. Abre cualquier sitio web que tenga un PDF para descargar
2. Descarga un archivo PDF
3. Deberías ver una notificación de Chrome con el mensaje: "CFE Detectado - Se ha detectado un CFE que puede ser procesado."

### Prueba 2: Usar el popup de la extensión

1. Haz clic en el icono de la extensión en la barra de herramientas de Chrome
2. Se abrirá el popup con el título "Detector de CFEs"
3. Haz clic en el botón "Ver CFEs Pendientes"
4. Deberías ver el estado de los CFEs pendientes

### Prueba 3: Procesar un PDF manualmente

1. Descarga un PDF de prueba
2. Haz clic en el icono de la extensión
3. En el popup, haz clic en "Procesar PDF"
4. El PDF se enviará al servidor Laravel para procesamiento

### Prueba 4: Ver CFEs en la interfaz de Laravel

1. Abre tu navegador y ve a: `http://localhost:8000/cfe-pendientes`
2. Deberías ver la lista de CFEs pendientes
3. Puedes ver detalles, confirmar o rechazar cada CFE

## Solución de Problemas

### Problema: La extensión no aparece en la lista

**Solución:**
- Asegúrate de que seleccionaste la carpeta correcta (`extension-cfe-detect`)
- Verifica que el archivo `manifest.json` esté en la raíz de la carpeta

### Problema: No se detectan las descargas de PDF

**Solución:**
- Verifica que el modo desarrollador esté activado
- Asegúrate de que la extensión esté habilitada
- Revisa la consola de la extensión para errores:
  1. Ve a `chrome://extensions`
  2. Haz clic en "Detalles" en tu extensión
  3. Haz clic en "Service worker" para ver la consola

### Problema: Error al conectar con el servidor

**Solución:**
- Asegúrate de que el servidor Laravel esté ejecutándose
- Verifica que la URL en `background.js` sea correcta: `http://localhost:8000/api/cfe/procesar`
- Revisa que CORS esté configurado correctamente en Laravel

### Problema: Los íconos no se muestran

**Solución:**
- Los archivos de íconos deben estar en `extension-cfe-detect/icons/`
- Si faltan, puedes crear archivos PNG simples o usar íconos de ejemplo

## Verificación de Funcionamiento

Para verificar que todo funciona correctamente:

1. **Extensión cargada:** Deberías ver el icono de la extensión en la barra de herramientas
2. **Notificaciones:** Al descargar un PDF, deberías recibir una notificación
3. **Popup funcional:** Al hacer clic en el icono, debería abrirse el popup
4. **Backend conectado:** Los PDFs deberían enviarse al servidor Laravel
5. **Base de datos:** Los CFEs deberían aparecer en la tabla `tes_cfe_pendientes`

## Pruebas Adicionales

### Prueba con PDFs de diferentes fuentes

Dado que la extensión ahora detecta PDFs desde cualquier URL, puedes probar con:

1. PDFs descargados de sitios web del gobierno
2. PDFs descargados de sitios web privados
3. PDFs descargados de tu propio servidor local
4. PDFs almacenados en tu computadora

### Prueba de extracción de datos

Una vez que el PDF se procesa, verifica que:

1. El tipo de CFE se detecte correctamente
2. Los datos se extraigan correctamente (serie, número, fecha, monto)
3. El registro se guarde en la base de datos

## Próximos Pasos

Una vez que la extensión esté funcionando correctamente:

1. Implementa la lógica completa de extracción de datos en `CfeProcessorService.php`
2. Crea íconos personalizados para la extensión
3. Mejora la interfaz de usuario del popup
4. Agrega más funcionalidades según tus necesidades

## Soporte

Si encuentras algún problema:

1. Revisa la consola del service worker de la extensión
2. Revisa los logs de Laravel (`storage/logs/laravel.log`)
3. Verifica que todas las dependencias estén instaladas
4. Asegúrate de que la base de datos esté configurada correctamente
