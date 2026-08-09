<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE TABLE `tes_multas_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tes_multas_cobradas_id` bigint(20) unsigned DEFAULT NULL,
  `detalle` varchar(255) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `importe` decimal(15,2) NOT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `updated_by` int(10) unsigned DEFAULT NULL,
  `deleted_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tes_multas_items_created_by_foreign` (`created_by`),
  KEY `tes_multas_items_updated_by_foreign` (`updated_by`),
  KEY `tes_multas_items_deleted_by_foreign` (`deleted_by`),
  KEY `tes_multas_items_tes_multas_cobradas_id_foreign` (`tes_multas_cobradas_id`),
  KEY `tes_multas_items_deleted_at_index` (`deleted_at`),
  CONSTRAINT `tes_multas_items_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `tes_multas_items_deleted_by_foreign` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`),
  CONSTRAINT `tes_multas_items_tes_multas_cobradas_id_foreign` FOREIGN KEY (`tes_multas_cobradas_id`) REFERENCES `tes_multas_cobradas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tes_multas_items_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }

    public function down(): void
    {
        Schema::dropIfExists('tes_multas_items');
    }
};
