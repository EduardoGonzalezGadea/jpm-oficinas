<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE TABLE `tes_caja_conceptos` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `caja_concepto` varchar(255) NOT NULL,
  `requiere_confirmacion` tinyint(1) NOT NULL DEFAULT 0,
  `requiere_distribucion` tinyint(1) NOT NULL DEFAULT 0,
  `permite_planilla` tinyint(1) NOT NULL DEFAULT 0,
  `requiere_institucion` tinyint(1) NOT NULL DEFAULT 0 COMMENT \'Indica si el concepto requiere seleccionar una institución\',
  `siif_distribucion_tipo_id` bigint(20) unsigned DEFAULT NULL COMMENT \'Tipo de distribución SIIF asociado a este concepto\',
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `deleted_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tes_caja_conceptos_created_by_index` (`created_by`),
  KEY `tes_caja_conceptos_updated_by_index` (`updated_by`),
  KEY `tes_caja_conceptos_deleted_by_index` (`deleted_by`),
  KEY `tes_caja_conceptos_siif_distribucion_tipo_id_foreign` (`siif_distribucion_tipo_id`),
  CONSTRAINT `tes_caja_conceptos_siif_distribucion_tipo_id_foreign` FOREIGN KEY (`siif_distribucion_tipo_id`) REFERENCES `siif_distribucion_tipos` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }

    public function down(): void
    {
        Schema::dropIfExists('tes_caja_conceptos');
    }
};
