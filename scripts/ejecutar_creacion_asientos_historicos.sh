#!/bin/bash

# Script para ejecutar la creación de asientos históricos de caja chica
# con validaciones y backup automático

set -e  # Detener en caso de error

echo "=========================================="
echo "Creación de Asientos Históricos"
echo "Caja Chica → Libro Diario"
echo "=========================================="
echo ""

# 1. Verificar que estamos en el directorio correcto
if [ ! -f "artisan" ]; then
    echo "❌ Error: No se encuentra el archivo artisan"
    echo "   Asegúrate de ejecutar este script desde la raíz del proyecto"
    exit 1
fi

echo "✅ Directorio verificado"
echo ""

# 2. Mostrar estadísticas actuales
echo "📊 Estadísticas Actuales:"
php temp_check_asientos.php
echo ""

# 3. Ejecutar simulación
echo "🔍 Ejecutando simulación (--dry-run)..."
echo ""
php artisan caja-chica:crear-asientos-historicos --dry-run --skip-confirmacion
echo ""

# 4. Preguntar confirmación
read -p "¿Desea continuar con la creación de asientos? (s/n): " -n 1 -r
echo ""

if [[ ! $REPLY =~ ^[Ss]$ ]]; then
    echo "❌ Operación cancelada por el usuario"
    exit 0
fi

# 5. Sugerir backup
echo ""
echo "⚠️  IMPORTANTE: Se recomienda hacer un backup de la base de datos"
read -p "¿Ya realizó el backup? (s/n): " -n 1 -r
echo ""

if [[ ! $REPLY =~ ^[Ss]$ ]]; then
    echo "⏸️  Por favor, realice un backup antes de continuar"
    echo "   Puede usar: php artisan backup:run"
    exit 0
fi

# 6. Ejecutar creación de asientos
echo ""
echo "🚀 Ejecutando creación de asientos históricos..."
echo ""
php artisan caja-chica:crear-asientos-historicos --skip-confirmacion

# 7. Recalcular saldos
echo ""
echo "🔄 Recalculando saldos del libro diario..."
echo ""
php artisan libro-diario:recalcular-saldos

# 8. Mostrar estadísticas finales
echo ""
echo "📊 Estadísticas Finales:"
php temp_check_asientos.php

echo ""
echo "=========================================="
echo "✅ Proceso completado exitosamente"
echo "=========================================="
echo ""
echo "Recomendaciones:"
echo "  - Verificar los asientos creados en el sistema"
echo "  - Revisar los saldos del libro diario"
echo "  - Realizar pruebas de integridad de datos"
