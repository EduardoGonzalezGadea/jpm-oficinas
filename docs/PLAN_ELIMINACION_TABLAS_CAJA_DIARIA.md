# Plan de Eliminación de Tablas Huérfanas — Caja Diaria

**Fecha**: 2026-07-21  
**Contexto**: Las tablas listadas corresponden al módulo **Caja Diaria / ER**, cuyo código fuente (modelos, componentes Livewire, vistas, migraciones, seeders) fue previamente eliminado del proyecto.

---

## Tablas a Eliminar (18)

### Grupo 1: Caja Diaria (operativo diario)

| # | Tabla | Contenido típico | Dependencias |
|---|-------|------------------|--------------|
| 1 | `tes_cajas_diarias` | Aperturas y cierres de caja diaria | FK a `tes_estados_caja`, `users` |
| 2 | `tes_caja_movimientos` | Movimientos INGRESO/EGRESO | FK a `tes_cajas_diarias`, `tes_tipos_movimiento`, `tes_conceptos`, `tes_medios_pago_caja` |
| 3 | `tes_desglose_monedas` | Desglose de billetes/monedas | FK a `tes_cajas_diarias`, `tes_denominaciones_monedas`, `tes_instancias_desglose` |
| 4 | `tes_estados_caja` | Catálogo: Abierta, Cerrada | Referenciada por `tes_cajas_diarias` |
| 5 | `tes_instancias_desglose` | Catálogo: Apertura, Arqueo, Cierre | Referenciada por `tes_desglose_monedas` |
| 6 | `tes_denominaciones_monedas` | Catálogo: billetes y monedas (UYU) | Referenciada por `tes_desglose_monedas` |
| 7 | `tes_tipos_movimiento` | Catálogo: INGRESO, EGRESO | Referenciada por `tes_caja_movimientos` |
| 8 | `tes_medios_pago_caja` | Catálogo: EFECTIVO, CHEQUE, POS, TRANSFERENCIA | Referenciada por `tes_caja_movimientos` |
| 9 | `tes_conceptos` | Catálogo de conceptos contables | Referenciada por `tes_caja_movimientos` |
| 10 | `tes_estados_deposito` | Catálogo de estados de depósito | Referenciada por `tes_caja_movimientos` |

### Grupo 2: ER — Estado de Recaudación (incompleto)

| # | Tabla | Contenido típico | Dependencias |
|---|-------|------------------|--------------|
| 11 | `tes_er_definiciones` | Definiciones de Estado de Recaudación | — |
| 12 | `tes_er_definicion_conceptos` | Pivot ER ↔ conceptos | FK a `tes_er_definiciones`, `tes_conceptos` |
| 13 | `tes_distribuciones_er` | Distribuciones contables de ER | — |
| 14 | `tes_estados_er` | Catálogo de estados de ER | — |
| 15 | `tes_estados_recaudacion` | Recaudaciones generadas | — |
| 16 | `tes_estados_recaudacion_detalles` | Detalle de recaudaciones | FK a `tes_estados_recaudacion` |
| 17 | `tes_categorias_222` | Categorías Artículo 222 | — |
| 18 | `tes_instituciones_222` | Instituciones Artículo 222 | — |

---

## Order de Eliminación

Debe respetarse el orden de las FK. Ejecutar en este orden:

### Fase 1: Tablas con FK externas (hijas)
```sql
DROP TABLE IF EXISTS `tes_estados_recaudacion_detalles`;
DROP TABLE IF EXISTS `tes_er_definicion_conceptos`;
DROP TABLE IF EXISTS `tes_desglose_monedas`;
DROP TABLE IF EXISTS `tes_caja_movimientos`;
```

### Fase 2: Tablas con FK a catálogos (intermedias)
```sql
DROP TABLE IF EXISTS `tes_estados_recaudacion`;
DROP TABLE IF EXISTS `tes_er_definiciones`;
DROP TABLE IF EXISTS `tes_distribuciones_er`;
DROP TABLE IF EXISTS `tes_cajas_diarias`;
```

### Fase 3: Catálogos puros (sin FK salientes)
```sql
DROP TABLE IF EXISTS `tes_estados_er`;
DROP TABLE IF EXISTS `tes_estados_caja`;
DROP TABLE IF EXISTS `tes_instancias_desglose`;
DROP TABLE IF EXISTS `tes_denominaciones_monedas`;
DROP TABLE IF EXISTS `tes_tipos_movimiento`;
DROP TABLE IF EXISTS `tes_medios_pago_caja`;
DROP TABLE IF EXISTS `tes_conceptos`;
DROP TABLE IF EXISTS `tes_estados_deposito`;
DROP TABLE IF EXISTS `tes_categorias_222`;
DROP TABLE IF EXISTS `tes_instituciones_222`;
```

---

## Script de Backup (previo a eliminación)

Se recomienda hacer backup de los datos antes de eliminar:

```sql
-- Backup de datos operativos (pueden tener valor histórico)
CREATE TABLE `bkp_tes_cajas_diarias` AS SELECT * FROM `tes_cajas_diarias`;
CREATE TABLE `bkp_tes_caja_movimientos` AS SELECT * FROM `tes_caja_movimientos`;
CREATE TABLE `bkp_tes_desglose_monedas` AS SELECT * FROM `tes_desglose_monedas`;

-- Backup de catálogos (pueden reutilizarse)
CREATE TABLE `bkp_tes_denominaciones_monedas` AS SELECT * FROM `tes_denominaciones_monedas`;
CREATE TABLE `bkp_tes_conceptos` AS SELECT * FROM `tes_conceptos`;
CREATE TABLE `bkp_tes_estados_caja` AS SELECT * FROM `tes_estados_caja`;
```

---

## Verificación Post-Eliminación

```sql
-- Verificar que todas las tablas se eliminaron
SELECT TABLE_NAME
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME IN (
    'tes_cajas_diarias', 'tes_caja_movimientos', 'tes_desglose_monedas',
    'tes_estados_caja', 'tes_instancias_desglose', 'tes_denominaciones_monedas',
    'tes_tipos_movimiento', 'tes_medios_pago_caja', 'tes_conceptos', 'tes_estados_deposito',
    'tes_er_definiciones', 'tes_er_definicion_conceptos', 'tes_distribuciones_er',
    'tes_estados_er', 'tes_estados_recaudacion', 'tes_estados_recaudacion_detalles',
    'tes_categorias_222', 'tes_instituciones_222'
  );
-- Debe retornar 0 filas
```

```bash
# Verificar que el proyecto compila sin errores
php artisan route:list
php artisan tinker --execute="echo 'OK';"
```

---

## Comando único para ejecutar en producción

```bash
php artisan db:wipe --drop-views --drop-types
# NO USAR. Es preferible ejecutar los DROPS manuales listados arriba.
```

**⚠️ Advertencia**: No usar `db:wipe` porque borra TODAS las tablas. Ejecutar solo los DROPs específicos.

---

## Resumen

| Concepto | Cantidad |
|----------|----------|
| Tablas a eliminar | 18 |
| Tablas con posible data histórica | 3 (`tes_cajas_diarias`, `tes_caja_movimientos`, `tes_desglose_monedas`) |
| Catálogos reutilizables | 6 (`tes_denominaciones_monedas`, `tes_conceptos`, `tes_tipos_movimiento`, `tes_medios_pago_caja`, `tes_estados_caja`, `tes_instancias_desglose`) |
| Tablas de módulo ER incompleto (sin data) | 8 |
