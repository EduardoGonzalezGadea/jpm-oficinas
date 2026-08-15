# Guía de Ejecución: Creación de Asientos Históricos

## 📊 Estado Actual del Sistema

**Asientos en Libro Diario:** 388 asientos totales
- 192 asientos vinculados a caja chica (49.5% del total)
  - 2 fondos fijos
  - 54 pendientes
  - 40 pagos
  - 96 movimientos

**Registros de Caja Chica:**
- 93 cajas chicas
- 1,462 pendientes
- 240 pagos
- 2,864 movimientos

**Lo que se creará:**
- **4,561 asientos nuevos** (según simulación)
- 98 registros se omitirán (ya tienen asientos)

Esto completará el historial contable desde 2019 hasta 2026.

## 🚀 Opción 1: Ejecución Manual (Paso a Paso)

### Paso 1: Simulación
```bash
php artisan caja-chica:crear-asientos-historicos --dry-run --skip-confirmacion
```

Revise el output cuidadosamente. Debe mostrar:
- Cuántas cajas chicas se procesarán
- Qué asientos se crearían para cada una
- Resumen final con totales

### Paso 2: Backup (IMPORTANTE)
```bash
# Si tiene configurado el backup automático
php artisan backup:run

# O haga un backup manual de la base de datos
mysqldump -u usuario -p base_de_datos > backup_antes_asientos_$(date +%Y%m%d_%H%M%S).sql
```

### Paso 3: Crear Asientos
```bash
php artisan caja-chica:crear-asientos-historicos
```

El comando pedirá confirmación. Responda "yes" para continuar.

### Paso 4: Recalcular Saldos
```bash
php artisan libro-diario:recalcular-saldos
```

### Paso 5: Verificación
Verifique los resultados en el sistema:
- Revise algunos asientos en el libro diario
- Verifique que los saldos sean correctos
- Pruebe los reportes de caja chica

## 🤖 Opción 2: Ejecución con Script Automático

### En Windows (PowerShell)
```powershell
.\scripts\ejecutar_creacion_asientos_historicos.ps1
```

### En Linux/Mac (Bash)
```bash
chmod +x scripts/ejecutar_creacion_asientos_historicos.sh
./scripts/ejecutar_creacion_asientos_historicos.sh
```

El script hará automáticamente:
1. ✅ Verificación del directorio
2. 📊 Mostrar estadísticas actuales
3. 🔍 Ejecutar simulación
4. ❓ Pedir confirmación
5. ⚠️ Recordar hacer backup
6. 🚀 Crear asientos históricos
7. 🔄 Recalcular saldos
8. 📊 Mostrar estadísticas finales

## 📋 Opciones Avanzadas

### Procesar Solo un Mes Específico
```bash
# Ejemplo: solo enero 2026
php artisan caja-chica:crear-asientos-historicos --mes=enero --anio=2026
php artisan libro-diario:recalcular-saldos
```

### Procesar Solo una Caja Chica
```bash
# Ejemplo: solo la caja chica ID 81
php artisan caja-chica:crear-asientos-historicos --caja-chica-id=81 --skip-confirmacion
php artisan libro-diario:recalcular-saldos
```

### Procesar por Rangos de Años
```bash
# 2019
php artisan caja-chica:crear-asientos-historicos --anio=2019

# 2020
php artisan caja-chica:crear-asientos-historicos --anio=2020

# etc...
```

## ⏱️ Tiempo Estimado

Basado en 4,561 asientos a crear:
- **Simulación:** ~2-3 minutos
- **Creación real:** ~10-15 minutos
- **Recalcular saldos:** ~2-3 minutos
- **Total:** ~15-20 minutos

## 🔍 Verificación Post-Ejecución

### 1. Verificar Totales
```bash
# Ejecutar script de verificación
php artisan tinker --execute="
echo 'Asientos en Libro Diario: ' . \App\Models\Tesoreria\LibroDiario::count() . PHP_EOL;
echo 'Asientos de Caja Chica: ' . \App\Models\Tesoreria\LibroDiario::whereNotNull('cch_origen_type')->count() . PHP_EOL;
"
```

Debería ver:
- **Asientos totales:** ~4,949 (388 + 4,561)
- **Asientos de caja chica:** ~4,753 (192 + 4,561)

### 2. Verificar Tipos
```sql
SELECT cch_origen_type, COUNT(*) as total 
FROM tes_libro_diario 
WHERE cch_origen_type IS NOT NULL 
GROUP BY cch_origen_type;
```

Debería ver algo como:
- caja_chica: ~93
- pendiente: ~1,489
- pago: ~273
- movimiento: ~2,898

### 3. Verificar Saldos
Revise en el sistema que:
- Los saldos del libro diario son positivos y coherentes
- No hay saldos negativos inesperados
- Los totales por concepto/detalle tienen sentido

## ⚠️ Solución de Problemas

### Error: "El monto a redistribuir supera el saldo disponible"
**Causa:** Problema de orden cronológico o falta de fondo fijo.

**Solución:**
```bash
# Procesar por año, de más antiguo a más reciente
php artisan caja-chica:crear-asientos-historicos --anio=2019
php artisan caja-chica:crear-asientos-historicos --anio=2020
php artisan caja-chica:crear-asientos-historicos --anio=2021
# etc...
```

### Error: Timeout o proceso muy lento
**Solución:** Procesar por períodos más pequeños:
```bash
# Por año
php artisan caja-chica:crear-asientos-historicos --anio=2019 --skip-confirmacion
php artisan libro-diario:recalcular-saldos

php artisan caja-chica:crear-asientos-historicos --anio=2020 --skip-confirmacion
php artisan libro-diario:recalcular-saldos

# etc...
```

### Asientos Duplicados
El comando detecta automáticamente asientos existentes y no crea duplicados. Es seguro ejecutarlo múltiples veces.

### Necesito Revertir Todo
Si necesita deshacer los cambios:
```sql
-- CUIDADO: Esto eliminará TODOS los asientos de caja chica
-- Haga backup primero
DELETE FROM tes_libro_diario WHERE cch_origen_type IS NOT NULL;
```

Luego restaure desde el backup:
```bash
mysql -u usuario -p base_de_datos < backup_antes_asientos_YYYYMMDD_HHMMSS.sql
```

## 📞 Soporte

Si encuentra problemas:
1. Revise los logs de Laravel: `storage/logs/laravel.log`
2. Consulte la documentación completa: `docs/comandos/caja-chica-crear-asientos-historicos.md`
3. Verifique el estado con: `php artisan caja-chica:crear-asientos-historicos --dry-run`

## ✅ Checklist Final

Antes de ejecutar:
- [ ] Hice una simulación con `--dry-run`
- [ ] Revisé el output y los totales son correctos
- [ ] Hice un backup de la base de datos
- [ ] Tengo tiempo suficiente (15-20 minutos)
- [ ] Estoy en el entorno correcto (desarrollo/producción)

Después de ejecutar:
- [ ] Verifiqué los totales de asientos creados
- [ ] Recalculé los saldos del libro diario
- [ ] Revisé algunos asientos en el sistema
- [ ] Los saldos son coherentes y positivos
- [ ] Probé los reportes de caja chica

---

**Última actualización:** 14/08/2026  
**Versión del comando:** 1.0  
**Compatibilidad:** Laravel 9 + PHP 8
