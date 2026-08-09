<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE TABLE `tes_anulaciones` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `anulable_type` varchar(255) NOT NULL,
  `anulable_id` bigint(20) unsigned NOT NULL,
  `datos_originales` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`datos_originales`)),
  `motivo` text NOT NULL,
  `anulado_por` int(10) unsigned NOT NULL,
  `fecha_anulacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NULL DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `updated_by` int(10) unsigned DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_by` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tes_anulaciones_anulable_type_anulable_id_index` (`anulable_type`,`anulable_id`),
  KEY `tes_anulaciones_anulado_por_foreign` (`anulado_por`),
  KEY `tes_anulaciones_anulado_por_index` (`anulado_por`),
  KEY `tes_anulaciones_created_by_foreign` (`created_by`),
  KEY `tes_anulaciones_updated_by_foreign` (`updated_by`),
  KEY `tes_anulaciones_deleted_by_foreign` (`deleted_by`),
  CONSTRAINT `tes_anulaciones_anulado_por_foreign` FOREIGN KEY (`anulado_por`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tes_anulaciones_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tes_anulaciones_deleted_by_foreign` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tes_anulaciones_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }

    public function down(): void
    {
        Schema::dropIfExists('tes_anulaciones');
    }
};
