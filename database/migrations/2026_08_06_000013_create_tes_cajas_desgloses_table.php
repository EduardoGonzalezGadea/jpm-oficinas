<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE TABLE `tes_cajas_desgloses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `caja_apertura_id` bigint(20) unsigned DEFAULT NULL,
  `arqueo_id` bigint(20) unsigned DEFAULT NULL,
  `tes_discriminacion_monetaria_id` bigint(20) unsigned NOT NULL,
  `cantidad` int(11) NOT NULL DEFAULT 0,
  `subtotal` decimal(12,2) NOT NULL DEFAULT 0.00,
  `tipo_referencia` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tes_cajas_desgloses_caja_apertura_id_index` (`caja_apertura_id`),
  KEY `tes_cajas_desgloses_arqueo_id_index` (`arqueo_id`),
  KEY `tes_cajas_desgloses_tes_discriminacion_monetaria_id_index` (`tes_discriminacion_monetaria_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }

    public function down(): void
    {
        Schema::dropIfExists('tes_cajas_desgloses');
    }
};
