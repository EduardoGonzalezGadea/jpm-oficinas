<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE TABLE `tes_planillas_cheques` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `numero_planilla` varchar(20) NOT NULL,
  `fecha_generacion` date NOT NULL,
  `estado` enum(\'generada\',\'anulada\') NOT NULL DEFAULT \'generada\',
  `fecha_anulacion` date DEFAULT NULL,
  `motivo_anulacion` text DEFAULT NULL,
  `generada_por` int(10) unsigned NOT NULL,
  `anulada_por` int(10) unsigned DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `updated_by` int(10) unsigned DEFAULT NULL,
  `deleted_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tes_planillas_cheques_generada_por_foreign` (`generada_por`),
  KEY `tes_planillas_cheques_anulada_por_foreign` (`anulada_por`),
  KEY `tes_planillas_cheques_created_by_foreign` (`created_by`),
  KEY `tes_planillas_cheques_updated_by_foreign` (`updated_by`),
  KEY `tes_planillas_cheques_deleted_by_foreign` (`deleted_by`),
  KEY `tes_planillas_cheques_generada_por_index` (`generada_por`),
  KEY `tes_planillas_cheques_anulada_por_index` (`anulada_por`),
  KEY `tes_planillas_cheques_created_by_index` (`created_by`),
  KEY `tes_planillas_cheques_updated_by_index` (`updated_by`),
  KEY `tes_planillas_cheques_deleted_by_index` (`deleted_by`),
  CONSTRAINT `tes_planillas_cheques_anulada_por_foreign` FOREIGN KEY (`anulada_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tes_planillas_cheques_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tes_planillas_cheques_deleted_by_foreign` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tes_planillas_cheques_generada_por_foreign` FOREIGN KEY (`generada_por`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tes_planillas_cheques_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }

    public function down(): void
    {
        Schema::dropIfExists('tes_planillas_cheques');
    }
};
