<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE TABLE `tes_cuentas_bancarias` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `banco_id` bigint(20) unsigned NOT NULL,
  `numero_cuenta` varchar(50) NOT NULL,
  `tipo` varchar(20) NOT NULL,
  `activa` tinyint(1) NOT NULL DEFAULT 1,
  `observaciones` text DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `updated_by` int(10) unsigned DEFAULT NULL,
  `deleted_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tes_cuentas_bancarias_banco_id_foreign` (`banco_id`),
  KEY `tes_cuentas_bancarias_created_by_foreign` (`created_by`),
  KEY `tes_cuentas_bancarias_updated_by_foreign` (`updated_by`),
  KEY `tes_cuentas_bancarias_deleted_by_foreign` (`deleted_by`),
  KEY `tes_cuentas_bancarias_created_by_index` (`created_by`),
  KEY `tes_cuentas_bancarias_updated_by_index` (`updated_by`),
  KEY `tes_cuentas_bancarias_deleted_by_index` (`deleted_by`),
  CONSTRAINT `tes_cuentas_bancarias_banco_id_foreign` FOREIGN KEY (`banco_id`) REFERENCES `tes_bancos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tes_cuentas_bancarias_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tes_cuentas_bancarias_deleted_by_foreign` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tes_cuentas_bancarias_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }

    public function down(): void
    {
        Schema::dropIfExists('tes_cuentas_bancarias');
    }
};
