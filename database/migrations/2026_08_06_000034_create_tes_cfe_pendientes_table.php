<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE TABLE `tes_cfe_pendientes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tipo_cfe` enum(\'certificado_residencia\',\'multas_cobradas\',\'porte_armas\',\'tenencia_armas\',\'desconocido\') NOT NULL,
  `serie` varchar(255) DEFAULT NULL,
  `numero` varchar(255) DEFAULT NULL,
  `fecha` date DEFAULT NULL,
  `monto` decimal(10,2) DEFAULT NULL,
  `moneda` varchar(3) NOT NULL DEFAULT \'UYU\',
  `datos_extraidos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`datos_extraidos`)),
  `datos_modificados` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`datos_modificados`)),
  `pdf_path` varchar(255) DEFAULT NULL,
  `source_url` varchar(255) DEFAULT NULL,
  `pdf_hash` char(64) DEFAULT NULL,
  `extractor_version` varchar(50) DEFAULT NULL,
  `estado` enum(\'pendiente\',\'en_proceso\',\'en_revision\',\'confirmado\',\'rechazado\',\'procesado\',\'expirado\',\'error\') NOT NULL DEFAULT \'pendiente\',
  `motivo_rechazo` text DEFAULT NULL,
  `user_id` int(10) unsigned DEFAULT NULL,
  `procesado_por` int(10) unsigned DEFAULT NULL,
  `procesado_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `updated_by` int(10) unsigned DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_by` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tes_cfe_pendientes_pdf_hash_unique` (`pdf_hash`),
  KEY `tes_cfe_pendientes_user_id_foreign` (`user_id`),
  KEY `tes_cfe_pendientes_procesado_por_foreign` (`procesado_por`),
  KEY `tes_cfe_pendientes_tipo_cfe_estado_index` (`tipo_cfe`,`estado`),
  KEY `tes_cfe_pendientes_serie_numero_fecha_index` (`serie`,`numero`,`fecha`),
  KEY `tes_cfe_pendientes_created_by_foreign` (`created_by`),
  KEY `tes_cfe_pendientes_updated_by_foreign` (`updated_by`),
  KEY `tes_cfe_pendientes_deleted_by_foreign` (`deleted_by`),
  CONSTRAINT `tes_cfe_pendientes_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tes_cfe_pendientes_deleted_by_foreign` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tes_cfe_pendientes_procesado_por_foreign` FOREIGN KEY (`procesado_por`) REFERENCES `users` (`id`),
  CONSTRAINT `tes_cfe_pendientes_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tes_cfe_pendientes_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }

    public function down(): void
    {
        Schema::dropIfExists('tes_cfe_pendientes');
    }
};
