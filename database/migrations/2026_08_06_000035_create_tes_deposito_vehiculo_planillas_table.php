<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE TABLE `tes_deposito_vehiculo_planillas` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `numero` varchar(255) NOT NULL,
  `fecha` date NOT NULL,
  `anulada` tinyint(1) NOT NULL DEFAULT 0,
  `anulada_fecha` datetime DEFAULT NULL,
  `anulada_by` bigint(20) unsigned DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_by` int(10) unsigned DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tes_deposito_vehiculo_planillas_numero_unique` (`numero`),
  KEY `tes_deposito_vehiculo_planillas_fecha_index` (`fecha`),
  KEY `tes_deposito_vehiculo_planillas_anulada_index` (`anulada`),
  KEY `tes_deposito_vehiculo_planillas_anulada_by_index` (`anulada_by`),
  KEY `tes_deposito_vehiculo_planillas_created_by_index` (`created_by`),
  KEY `tes_deposito_vehiculo_planillas_updated_by_index` (`updated_by`),
  KEY `tes_deposito_vehiculo_planillas_deleted_by_foreign` (`deleted_by`),
  CONSTRAINT `tes_deposito_vehiculo_planillas_deleted_by_foreign` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }

    public function down(): void
    {
        Schema::dropIfExists('tes_deposito_vehiculo_planillas');
    }
};
