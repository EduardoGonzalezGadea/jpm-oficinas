<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE TABLE `tes_cch_pendientes` (
  `idPendientes` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `relCajaChica` bigint(20) unsigned NOT NULL,
  `pendiente` int(11) NOT NULL,
  `fechaPendientes` date NOT NULL,
  `relDependencia` bigint(20) unsigned NOT NULL,
  `montoPendientes` decimal(15,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `updated_by` int(10) unsigned DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_by` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`idPendientes`),
  KEY `tes_cch_pendientes_relcajachica_foreign` (`relCajaChica`),
  KEY `tes_cch_pendientes_reldependencia_foreign` (`relDependencia`),
  KEY `tes_cch_pendientes_relcajachica_index` (`relCajaChica`),
  KEY `tes_cch_pendientes_reldependencia_index` (`relDependencia`),
  KEY `tes_cch_pendientes_created_by_foreign` (`created_by`),
  KEY `tes_cch_pendientes_updated_by_foreign` (`updated_by`),
  KEY `tes_cch_pendientes_deleted_by_foreign` (`deleted_by`),
  CONSTRAINT `tes_cch_pendientes_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tes_cch_pendientes_deleted_by_foreign` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tes_cch_pendientes_relcajachica_foreign` FOREIGN KEY (`relCajaChica`) REFERENCES `tes_caja_chica` (`idCajaChica`) ON DELETE CASCADE,
  CONSTRAINT `tes_cch_pendientes_reldependencia_foreign` FOREIGN KEY (`relDependencia`) REFERENCES `tes_cch_dependencias` (`idDependencias`) ON DELETE CASCADE,
  CONSTRAINT `tes_cch_pendientes_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }

    public function down(): void
    {
        Schema::dropIfExists('tes_cch_pendientes');
    }
};
