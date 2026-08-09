<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE TABLE `tes_planilla_ers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `fecha` date NOT NULL,
  `numero` varchar(255) NOT NULL,
  `tipo_id` bigint(20) unsigned NOT NULL,
  `dependencia_id` bigint(20) unsigned NOT NULL,
  `turno` varchar(255) DEFAULT NULL,
  `er_numero` varchar(255) DEFAULT NULL,
  `egresos_numero` varchar(255) DEFAULT NULL,
  `ingresos_numero` varchar(255) DEFAULT NULL,
  `total_recaudado` decimal(12,2) NOT NULL DEFAULT 0.00,
  `transferencia_fecha` date DEFAULT NULL,
  `transferencia_confirmacion` varchar(255) DEFAULT NULL,
  `confirmada` tinyint(1) NOT NULL DEFAULT 0,
  `motivo_anulacion` text DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `updated_by` int(10) unsigned DEFAULT NULL,
  `deleted_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tes_planilla_ers_tipo_id_foreign` (`tipo_id`),
  KEY `tes_planilla_ers_dependencia_id_foreign` (`dependencia_id`),
  KEY `tes_planilla_ers_created_by_foreign` (`created_by`),
  KEY `tes_planilla_ers_updated_by_foreign` (`updated_by`),
  KEY `tes_planilla_ers_deleted_by_foreign` (`deleted_by`),
  KEY `idx_tes_planilla_ers_fecha_tipo_dep_conf` (`fecha`,`tipo_id`,`dependencia_id`,`confirmada`),
  KEY `idx_tes_planilla_ers_fecha_turno_deleted` (`fecha`,`turno`,`deleted_at`),
  KEY `idx_tes_planilla_ers_deleted_fecha_id` (`deleted_at`,`fecha`,`id`),
  CONSTRAINT `tes_planilla_ers_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tes_planilla_ers_deleted_by_foreign` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tes_planilla_ers_dependencia_id_foreign` FOREIGN KEY (`dependencia_id`) REFERENCES `siif_distribucion_dependencias` (`id`),
  CONSTRAINT `tes_planilla_ers_tipo_id_foreign` FOREIGN KEY (`tipo_id`) REFERENCES `siif_distribucion_tipos` (`id`),
  CONSTRAINT `tes_planilla_ers_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }

    public function down(): void
    {
        Schema::dropIfExists('tes_planilla_ers');
    }
};
