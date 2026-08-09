<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE TABLE `tes_cajas_arqueos` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `caja_apertura_id` bigint(20) unsigned NOT NULL,
  `total_efectivo` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_transferencias` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_cheques` decimal(12,2) NOT NULL DEFAULT 0.00,
  `diferencia` decimal(12,2) NOT NULL DEFAULT 0.00,
  `observaciones` text DEFAULT NULL,
  `usuario_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tes_cajas_arqueos_caja_apertura_id_index` (`caja_apertura_id`),
  KEY `tes_cajas_arqueos_usuario_id_index` (`usuario_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }

    public function down(): void
    {
        Schema::dropIfExists('tes_cajas_arqueos');
    }
};
