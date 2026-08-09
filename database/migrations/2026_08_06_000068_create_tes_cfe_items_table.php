<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE TABLE `tes_cfe_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tes_cfe_id` bigint(20) unsigned NOT NULL,
  `siif_distribucion_id` bigint(20) unsigned DEFAULT NULL,
  `detalle` text NOT NULL,
  `descripcion` text DEFAULT NULL,
  `cantidad` decimal(10,2) NOT NULL DEFAULT 1.00,
  `precio` decimal(12,2) NOT NULL DEFAULT 0.00,
  `descuento` decimal(12,2) NOT NULL DEFAULT 0.00,
  `recargo` decimal(12,2) NOT NULL DEFAULT 0.00,
  `importe` decimal(12,2) NOT NULL DEFAULT 0.00,
  `confirmado` tinyint(1) NOT NULL DEFAULT 0,
  `planilla_er_id` bigint(20) unsigned DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `updated_by` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tes_cfe_items_tes_cfe_id_foreign` (`tes_cfe_id`),
  KEY `tes_cfe_items_siif_distribucion_id_foreign` (`siif_distribucion_id`),
  KEY `tes_cfe_items_planilla_er_id_foreign` (`planilla_er_id`),
  KEY `idx_tes_cfe_items_planilla_dist_conf` (`planilla_er_id`,`siif_distribucion_id`,`confirmado`),
  KEY `idx_tes_cfe_items_planilla_deleted` (`planilla_er_id`,`deleted_at`),
  KEY `idx_tes_cfe_items_cfe_deleted` (`tes_cfe_id`,`deleted_at`),
  CONSTRAINT `tes_cfe_items_planilla_er_id_foreign` FOREIGN KEY (`planilla_er_id`) REFERENCES `tes_planilla_ers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tes_cfe_items_siif_distribucion_id_foreign` FOREIGN KEY (`siif_distribucion_id`) REFERENCES `siif_distribucions` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tes_cfe_items_tes_cfe_id_foreign` FOREIGN KEY (`tes_cfe_id`) REFERENCES `tes_cfes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }

    public function down(): void
    {
        Schema::dropIfExists('tes_cfe_items');
    }
};
