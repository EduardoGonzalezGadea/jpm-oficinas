# Plan de Migración Laravel 10 → 12 + Livewire 3 → 4

## 📋 Estado Actual

**Versiones Actuales**:
- PHP: ^8.1
- Laravel: ^10.0
- Livewire: ^3.0

**Versiones Objetivo**:
- PHP: ^8.2
- Laravel: ^12.0
- Livewire: ^4.0

---

## 🗂️ Inventario de Componentes Livewire

### Componentes de Tesorería Identificados

#### Módulo Caja Chica
- `app/Livewire/Tesoreria/CajaChica/` (múltiples componentes)

#### Módulo Multas
- `Multa.php`
- `Multa303.php`
- `Multa303Publico.php`
- `MultaPublico.php`
- `PrintMultasArticulos.php`
- `MultasCobradas/` (directorio)

#### Módulo CFE
- `CfePendientesIndex.php`
- `CfePendientes/` (directorio)
- `CfeMonitoring/` (directorio)
- `GestionCfe/` (directorio)

#### Módulo Libro Diario
- `LibroDiario/` (directorio)

#### Otros Módulos
- `Armas/`
- `Arrendamientos/`
- `Banco/`
- `CajaDiaria/`
- `CargaMasivaHaberes/`
- `CertificadosResidencia/`
- `Cheque/`
- `Configuracion/`
- `CuentaBancaria/`
- `DepositoVehiculos/`
- `EstadosRecaudacion/`
- `Eventuales/`
- `PlanillasComunes/`
- `Prendas/`
- `Recaudaciones/`
- `ReporteRecibos/`
- `TarjetasCobroBrou/`
- `Valores/`

#### Compartidos
- `Shared/`
- `Traits/`

#### Sistema
- `Sistema/`
- `AsesoriaContable/`
- `UsersTable.php`

**Total Estimado**: ~80-100 componentes Livewire

---

## 📅 Cronograma de Migración (5 Semanas)

### Semana 1: Preparación y Core Laravel

#### Día 1-2: Preparación
- [ ] Crear backup completo de BD
- [ ] Crear backup completo del código
- [ ] Crear rama `feature/laravel-12-migration`
- [ ] Documentar estado actual
- [ ] Ejecutar tests baseline (deben pasar los 335)

#### Día 3-5: Actualización Core
- [ ] Actualizar `composer.json`
- [ ] Ejecutar `composer update`
- [ ] Resolver conflictos de dependencias
- [ ] Actualizar configuraciones
- [ ] Ejecutar tests (verificar que siguen pasando)

---

### Semana 2: Migración Livewire - Módulos Críticos

#### Prioridad 1: Módulos con Tests ✅
- [ ] **Caja Chica** (66 tests) - CRÍTICO
- [ ] **Libro Diario** (57 tests) - CRÍTICO
- [ ] **Multas** (83 tests) - CRÍTICO
- [ ] **CFE** (85 tests) - CRÍTICO

**Estrategia**:
1. Migrar componente
2. Ejecutar tests del módulo
3. Corregir errores
4. Siguiente componente

---

### Semana 3: Migración Livewire - Módulos Secundarios

#### Prioridad 2: Módulos Sin Tests
- [ ] Certificados Residencia
- [ ] Armas y Explosivos
- [ ] Arrendamientos
- [ ] Prenda con Registro
- [ ] Banco y Cuentas
- [ ] Cheques
- [ ] Caja Diaria

**Estrategia**:
1. Migrar componente
2. Pruebas manuales
3. Documentar issues

---

### Semana 4: Migración Livewire - Resto + Testing

#### Prioridad 3: Módulos de Soporte
- [ ] Configuración
- [ ] Eventuales
- [ ] Recaudaciones
- [ ] Reportes
- [ ] Valores externos
- [ ] Componentes compartidos
- [ ] Sistema y usuarios

#### Testing Adicional
- [ ] Crear tests para módulos migrados sin tests
- [ ] Tests E2E de flujos nuevos
- [ ] Pruebas de integración completas

---

### Semana 5: Estabilización y Deploy

#### Días 1-2: Testing Intensivo
- [ ] Suite completa de tests
- [ ] Pruebas manuales exhaustivas
- [ ] Performance testing
- [ ] Security audit

#### Días 3-4: Staging
- [ ] Deploy a ambiente de staging
- [ ] Pruebas con usuarios beta
- [ ] Recolección de feedback
- [ ] Ajustes finales

#### Día 5: Producción
- [ ] Deploy gradual a producción
- [ ] Monitoreo intensivo
- [ ] Rollback plan listo

---

## 🔧 Cambios Necesarios por Componente

### 1. Properties con Tipado

```php
// ❌ Livewire 3
public $nombre;
public $monto;

// ✅ Livewire 4
#[Locked]
public string $nombre;

#[Locked]
public float $monto;

// O usar Modelable para búsquedas
#[Modelable]
public string $search = '';
```

### 2. Eventos

```php
// ❌ Livewire 3
$this->emit('multaActualizada', $multaId);
$this->emitTo('multas-list', 'refrescar');
$this->emitSelf('cargar');
$this->emitUp('cerrarModal');

// ✅ Livewire 4
$this->dispatch('multaActualizada', multaId: $multaId);
$this->dispatch('refrescar')->to('multas-list');
$this->dispatch('cargar')->self();
$this->dispatch('cerrarModal')->up();
```

### 3. Listeners

```php
// ❌ Livewire 3
protected $listeners = ['refrescar' => 'cargarDatos'];

// ✅ Livewire 4
#[On('refrescar')]
public function cargarDatos(): void
{
    // ...
}
```

### 4. Computed Properties

```php
// ❌ Livewire 3
public function getTotalProperty()
{
    return $this->items->sum('monto');
}

// ✅ Livewire 4
#[Computed]
public function total(): float
{
    return $this->items->sum('monto');
}

// En blade: {{ $this->total }}
```

### 5. Rules y Validation

```php
// ❌ Livewire 3
protected $rules = [
    'nombre' => 'required|min:3',
    'monto' => 'required|numeric|min:0',
];

// ✅ Livewire 4
#[Validate('required|min:3')]
public string $nombre = '';

#[Validate('required|numeric|min:0')]
public float $monto = 0;
```

### 6. Lazy Loading

```php
// ❌ Livewire 3
public function loadItems()
{
    $this->readyToLoad = true;
}

// ✅ Livewire 4
#[Lazy]
public function placeholder(): string
{
    return <<<'HTML'
    <div>
        <div class="spinner-border"></div>
        Cargando...
    </div>
    HTML;
}
```

---

## 🔍 Script de Detección de Patrones

Voy a crear scripts para detectar automáticamente qué hay que cambiar:

### Script 1: Detectar Emits
```bash
grep -r "\$this->emit" app/Livewire/ > migracion-emits.txt
grep -r "\$this->emitTo" app/Livewire/ >> migracion-emits.txt
grep -r "\$this->emitSelf" app/Livewire/ >> migracion-emits.txt
grep -r "\$this->emitUp" app/Livewire/ >> migracion-emits.txt
```

### Script 2: Detectar Listeners
```bash
grep -r "protected \$listeners" app/Livewire/ > migracion-listeners.txt
```

### Script 3: Detectar Properties Sin Tipo
```bash
grep -r "public \$" app/Livewire/ > migracion-properties.txt
```

### Script 4: Detectar Rules
```bash
grep -r "protected \$rules" app/Livewire/ > migracion-rules.txt
```

---

## 📊 Métricas de Éxito

### Pre-Migración
- [x] 335 tests pasando
- [x] 0 errores en logs
- [x] Performance baseline documentada

### Durante Migración
- [ ] Tests siguen pasando después de cada cambio
- [ ] 0 nuevos errores en logs
- [ ] Performance similar o mejor

### Post-Migración
- [ ] 335+ tests pasando
- [ ] 0 errores críticos
- [ ] Performance igual o mejor
- [ ] 0 bugs reportados en primera semana

---

## 🚨 Plan de Rollback

### Si algo sale mal:

1. **Detener deploy inmediatamente**
2. **Revertir a rama principal**: `git checkout main`
3. **Restaurar BD si es necesario** (desde backup)
4. **Notificar al equipo**
5. **Analizar logs de error**
6. **Documentar el problema**
7. **Corregir en rama de migración**
8. **Re-intentar**

---

## 📝 Checklist Diario Durante Migración

Al finalizar cada día:

- [ ] Tests ejecutados y pasando
- [ ] Cambios commiteados con mensajes claros
- [ ] Issues documentados
- [ ] Progreso actualizado en este documento
- [ ] Logs revisados (sin errores nuevos)

---

## 🔗 Recursos

### Documentación Oficial
- [Laravel 12 Upgrade Guide](https://laravel.com/docs/12.x/upgrade)
- [Livewire 4 Upgrade Guide](https://livewire.laravel.com/docs/upgrade-guide)

### Documentación Interna
- `docs/MIGRACION_LARAVEL_12_LIVEWIRE_4.md` - Análisis detallado
- `docs/GUIA_TESTING.md` - Guía de tests
- `docs/TESTING_TROUBLESHOOTING.md` - Solución de problemas

### Tests
- Ejecutar: `php artisan test`
- Por módulo: `php artisan test --filter=NombreModulo`
- Con cobertura: `php artisan test --coverage`

---

## 📞 Contactos y Soporte

- **Tech Lead**: [Nombre]
- **Equipo QA**: [Contacto]
- **DevOps**: [Contacto]
- **Documentación**: Este archivo + `docs/`

---

## 🎯 Siguiente Paso INMEDIATO

**AHORA**: Crear el `composer.json` actualizado

Voy a generar el archivo actualizado en el siguiente paso.

---

**Fecha de creación**: 14/08/2026  
**Última actualización**: 14/08/2026  
**Estado**: 📝 PLANIFICACIÓN COMPLETA
