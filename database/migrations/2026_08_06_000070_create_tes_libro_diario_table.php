<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE TABLE `tes_libro_diario` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `fecha` date NOT NULL,
  `tipo_id` bigint(20) unsigned NOT NULL,
  `numero` int(11) NOT NULL,
  `signo_efectivo` tinyint(4) NOT NULL,
  `identidad` varchar(255) DEFAULT NULL,
  `denominacion` varchar(255) DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `concepto_id` bigint(20) unsigned NOT NULL,
  `detalle_id` bigint(20) unsigned NOT NULL,
  `medio_id` bigint(20) unsigned DEFAULT NULL,
  `monto` decimal(12,2) NOT NULL,
  `saldo` decimal(12,2) NOT NULL,
  `asociar` bigint(20) unsigned DEFAULT NULL,
  `grupo_redistribucion_id` bigint(20) unsigned DEFAULT NULL,
  `cfe_id` bigint(20) unsigned DEFAULT NULL,
  `documento_referencia` varchar(255) DEFAULT NULL,
  `confirmado` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_confirmacion` timestamp NULL DEFAULT NULL,
  `es_contra_asiento` tinyint(1) NOT NULL DEFAULT 0,
  `cch_origen_type` varchar(50) DEFAULT NULL,
  `cch_origen_id` bigint(20) unsigned DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `updated_by` int(10) unsigned DEFAULT NULL,
  `deleted_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tes_libro_diario_fecha_index` (`fecha`),
  KEY `tes_libro_diario_numero_index` (`numero`),
  KEY `tes_libro_diario_concepto_id_index` (`concepto_id`),
  KEY `tes_libro_diario_detalle_id_index` (`detalle_id`),
  KEY `tes_libro_diario_asociar_index` (`asociar`),
  KEY `tes_libro_diario_tipo_id_deleted_at_index` (`tipo_id`,`deleted_at`),
  KEY `tes_libro_diario_grupo_redistribucion_id_index` (`grupo_redistribucion_id`),
  KEY `tes_libro_diario_created_by_index` (`created_by`),
  KEY `tes_libro_diario_updated_by_index` (`updated_by`),
  KEY `tes_libro_diario_deleted_by_index` (`deleted_by`),
  KEY `tes_libro_diario_cfe_id_foreign` (`cfe_id`),
  KEY `tes_libro_diario_medio_id_foreign` (`medio_id`),
  KEY `idx_libro_diario_cch_origen` (`cch_origen_type`,`cch_origen_id`),
  KEY `idx_libro_diario_fecha_tipo_deleted` (`fecha`,`tipo_id`,`deleted_at`),
  KEY `idx_libro_diario_cfe_contra` (`cfe_id`,`es_contra_asiento`),
  CONSTRAINT `tes_libro_diario_asociar_foreign` FOREIGN KEY (`asociar`) REFERENCES `tes_libro_diario` (`id`),
  CONSTRAINT `tes_libro_diario_cfe_id_foreign` FOREIGN KEY (`cfe_id`) REFERENCES `tes_cfes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tes_libro_diario_concepto_id_foreign` FOREIGN KEY (`concepto_id`) REFERENCES `tes_lb_conceptos` (`id`),
  CONSTRAINT `tes_libro_diario_detalle_id_foreign` FOREIGN KEY (`detalle_id`) REFERENCES `tes_lb_detalle` (`id`),
  CONSTRAINT `tes_libro_diario_medio_id_foreign` FOREIGN KEY (`medio_id`) REFERENCES `tes_medio_de_pagos` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tes_libro_diario_tipo_id_foreign` FOREIGN KEY (`tipo_id`) REFERENCES `tes_lb_tipos` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }

    public function down(): void
    {
        Schema::dropIfExists('tes_libro_diario');
    }
};
