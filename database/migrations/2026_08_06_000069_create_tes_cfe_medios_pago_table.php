<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE TABLE `tes_cfe_medios_pago` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tes_cfe_id` bigint(20) unsigned NOT NULL,
  `medio_pago_tipo` varchar(255) NOT NULL,
  `medio_pago_id` bigint(20) unsigned DEFAULT NULL,
  `medio_pago_valor` decimal(12,2) NOT NULL DEFAULT 0.00,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `updated_by` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tes_cfe_medios_pago_tes_cfe_id_foreign` (`tes_cfe_id`),
  KEY `tes_cfe_medios_pago_medio_pago_id_foreign` (`medio_pago_id`),
  CONSTRAINT `tes_cfe_medios_pago_medio_pago_id_foreign` FOREIGN KEY (`medio_pago_id`) REFERENCES `tes_medio_de_pagos` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tes_cfe_medios_pago_tes_cfe_id_foreign` FOREIGN KEY (`tes_cfe_id`) REFERENCES `tes_cfes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }

    public function down(): void
    {
        Schema::dropIfExists('tes_cfe_medios_pago');
    }
};
