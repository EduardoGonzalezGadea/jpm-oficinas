<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE TABLE `tes_multa_medios_pago` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `multa_id` bigint(20) unsigned NOT NULL,
  `medio_pago_id` bigint(20) unsigned NOT NULL,
  `monto` decimal(12,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tes_multa_medios_pago_multa_id_medio_pago_id_unique` (`multa_id`,`medio_pago_id`),
  KEY `tes_multa_medios_pago_medio_pago_id_foreign` (`medio_pago_id`),
  CONSTRAINT `tes_multa_medios_pago_medio_pago_id_foreign` FOREIGN KEY (`medio_pago_id`) REFERENCES `tes_medio_de_pagos` (`id`),
  CONSTRAINT `tes_multa_medios_pago_multa_id_foreign` FOREIGN KEY (`multa_id`) REFERENCES `tes_multas_cobradas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }

    public function down(): void
    {
        Schema::dropIfExists('tes_multa_medios_pago');
    }
};
