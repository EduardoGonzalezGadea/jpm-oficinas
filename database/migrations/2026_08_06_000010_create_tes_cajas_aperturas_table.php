<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE TABLE `tes_cajas_aperturas` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `cajero_id` bigint(20) unsigned NOT NULL,
  `fecha_apertura` date NOT NULL,
  `hora_apertura` time NOT NULL,
  `saldo_inicial` decimal(12,2) NOT NULL DEFAULT 0.00,
  `saldo_cierre` decimal(12,2) DEFAULT NULL,
  `fecha_cierre` timestamp NULL DEFAULT NULL,
  `estado` varchar(20) NOT NULL DEFAULT \'abierta\',
  `observaciones` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `deleted_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tes_cajas_aperturas_cajero_id_index` (`cajero_id`),
  KEY `tes_cajas_aperturas_estado_index` (`estado`),
  KEY `tes_cajas_aperturas_created_by_index` (`created_by`),
  KEY `tes_cajas_aperturas_updated_by_index` (`updated_by`),
  KEY `tes_cajas_aperturas_deleted_by_index` (`deleted_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }

    public function down(): void
    {
        Schema::dropIfExists('tes_cajas_aperturas');
    }
};
