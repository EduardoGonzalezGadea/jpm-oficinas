<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE TABLE `tes_cajas_movimientos` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `caja_apertura_id` bigint(20) unsigned NOT NULL,
  `tipo_movimiento` varchar(20) NOT NULL,
  `monto` decimal(12,2) NOT NULL,
  `medio_pago_id` bigint(20) unsigned DEFAULT NULL,
  `cfe_id` bigint(20) unsigned DEFAULT NULL,
  `libro_diario_id` bigint(20) unsigned DEFAULT NULL,
  `concepto` varchar(255) DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tes_cajas_movimientos_caja_apertura_id_index` (`caja_apertura_id`),
  KEY `tes_cajas_movimientos_libro_diario_id_index` (`libro_diario_id`),
  KEY `tes_cajas_movimientos_cfe_id_index` (`cfe_id`),
  KEY `tes_cajas_movimientos_created_by_index` (`created_by`),
  CONSTRAINT `tes_cajas_movimientos_caja_apertura_id_foreign` FOREIGN KEY (`caja_apertura_id`) REFERENCES `tes_cajas_aperturas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }

    public function down(): void
    {
        Schema::dropIfExists('tes_cajas_movimientos');
    }
};
