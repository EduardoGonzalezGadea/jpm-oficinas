<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE TABLE `tes_cch_movimientos` (
  `idMovimientos` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `relPendiente` bigint(20) unsigned NOT NULL,
  `fechaMovimientos` date NOT NULL,
  `documentos` varchar(255) DEFAULT NULL,
  `rendido` decimal(15,2) NOT NULL DEFAULT 0.00,
  `reintegrado` decimal(15,2) NOT NULL DEFAULT 0.00,
  `recuperado` decimal(15,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `updated_by` int(10) unsigned DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_by` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`idMovimientos`),
  KEY `tes_cch_movimientos_relpendiente_foreign` (`relPendiente`),
  KEY `tes_cch_movimientos_relpendiente_index` (`relPendiente`),
  KEY `tes_cch_movimientos_created_by_foreign` (`created_by`),
  KEY `tes_cch_movimientos_updated_by_foreign` (`updated_by`),
  KEY `tes_cch_movimientos_deleted_by_foreign` (`deleted_by`),
  CONSTRAINT `tes_cch_movimientos_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tes_cch_movimientos_deleted_by_foreign` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tes_cch_movimientos_relpendiente_foreign` FOREIGN KEY (`relPendiente`) REFERENCES `tes_cch_pendientes` (`idPendientes`) ON DELETE CASCADE,
  CONSTRAINT `tes_cch_movimientos_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }

    public function down(): void
    {
        Schema::dropIfExists('tes_cch_movimientos');
    }
};
