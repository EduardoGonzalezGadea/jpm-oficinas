<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE TABLE `siif_distribucions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tipo_id` bigint(20) unsigned NOT NULL,
  `dependencia_id` bigint(20) unsigned NOT NULL,
  `rubro` varchar(255) DEFAULT NULL,
  `sub_rubro` varchar(255) DEFAULT NULL,
  `recurso` varchar(255) DEFAULT NULL,
  `concepto` varchar(255) DEFAULT NULL,
  `codigo_sir` varchar(255) DEFAULT NULL,
  `porcentaje` decimal(6,3) NOT NULL,
  `financiacion` varchar(255) DEFAULT NULL,
  `inciso` varchar(255) DEFAULT NULL,
  `unidad_ejecutora` varchar(255) DEFAULT NULL,
  `distribucion` varchar(255) DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `updated_by` int(10) unsigned DEFAULT NULL,
  `deleted_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `siif_distribucions_tipo_id_foreign` (`tipo_id`),
  KEY `siif_distribucions_dependencia_id_foreign` (`dependencia_id`),
  KEY `siif_distribucions_created_by_foreign` (`created_by`),
  KEY `siif_distribucions_updated_by_foreign` (`updated_by`),
  KEY `siif_distribucions_deleted_by_foreign` (`deleted_by`),
  CONSTRAINT `siif_distribucions_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `siif_distribucions_deleted_by_foreign` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `siif_distribucions_dependencia_id_foreign` FOREIGN KEY (`dependencia_id`) REFERENCES `siif_distribucion_dependencias` (`id`),
  CONSTRAINT `siif_distribucions_tipo_id_foreign` FOREIGN KEY (`tipo_id`) REFERENCES `siif_distribucion_tipos` (`id`),
  CONSTRAINT `siif_distribucions_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }

    public function down(): void
    {
        Schema::dropIfExists('siif_distribucions');
    }
};
