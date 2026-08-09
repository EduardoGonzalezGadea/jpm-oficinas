<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE TABLE `tes_porte_armas_planillas` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `fecha` date NOT NULL,
  `numero` varchar(255) NOT NULL,
  `anulada_fecha` timestamp NULL DEFAULT NULL,
  `anulada_user_id` int(10) unsigned DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `updated_by` int(10) unsigned DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_by` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tes_porte_armas_planillas_numero_unique` (`numero`),
  KEY `tes_porte_armas_planillas_anulada_user_id_foreign` (`anulada_user_id`),
  KEY `tes_porte_armas_planillas_created_by_foreign` (`created_by`),
  KEY `tes_porte_armas_planillas_updated_by_foreign` (`updated_by`),
  KEY `tes_porte_armas_planillas_deleted_by_foreign` (`deleted_by`),
  CONSTRAINT `tes_porte_armas_planillas_anulada_user_id_foreign` FOREIGN KEY (`anulada_user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `tes_porte_armas_planillas_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `tes_porte_armas_planillas_deleted_by_foreign` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tes_porte_armas_planillas_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }

    public function down(): void
    {
        Schema::dropIfExists('tes_porte_armas_planillas');
    }
};
