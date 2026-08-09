<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE TABLE `tes_lb_detalle` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `concepto_id` bigint(20) unsigned NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `updated_by` int(10) unsigned DEFAULT NULL,
  `deleted_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tes_lb_detalle_concepto_id_foreign` (`concepto_id`),
  KEY `tes_lb_detalle_created_by_index` (`created_by`),
  KEY `tes_lb_detalle_updated_by_index` (`updated_by`),
  KEY `tes_lb_detalle_deleted_by_index` (`deleted_by`),
  CONSTRAINT `tes_lb_detalle_concepto_id_foreign` FOREIGN KEY (`concepto_id`) REFERENCES `tes_lb_conceptos` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }

    public function down(): void
    {
        Schema::dropIfExists('tes_lb_detalle');
    }
};
