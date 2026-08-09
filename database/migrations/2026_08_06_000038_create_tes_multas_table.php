<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE TABLE `tes_multas` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `articulo` int(11) NOT NULL,
  `apartado` varchar(10) DEFAULT NULL,
  `articulo_completo` varchar(20) DEFAULT NULL,
  `descripcion` text NOT NULL,
  `moneda` varchar(3) NOT NULL DEFAULT \'UR\',
  `importe_original` decimal(10,2) NOT NULL,
  `importe_unificado` decimal(10,2) DEFAULT NULL,
  `decreto` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `updated_by` int(10) unsigned DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_by` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tes_multas_articulo_apartado_index` (`articulo`,`apartado`),
  KEY `idx_multas_articulo_apartado` (`articulo`,`apartado`),
  KEY `idx_multas_descripcion` (`descripcion`(50)),
  KEY `idx_multas_deleted_at` (`deleted_at`),
  KEY `idx_multas_importe_original` (`importe_original`),
  KEY `idx_multas_importe_unificado` (`importe_unificado`),
  KEY `idx_multas_articulo_completo` (`articulo_completo`),
  KEY `tes_multas_created_by_foreign` (`created_by`),
  KEY `tes_multas_updated_by_foreign` (`updated_by`),
  KEY `tes_multas_deleted_by_foreign` (`deleted_by`),
  CONSTRAINT `tes_multas_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tes_multas_deleted_by_foreign` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tes_multas_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }

    public function down(): void
    {
        Schema::dropIfExists('tes_multas');
    }
};
