<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE TABLE `tes_entregas_libretas_valores` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `libreta_valor_id` bigint(20) unsigned NOT NULL,
  `servicio_id` bigint(20) unsigned NOT NULL,
  `numero_recibo_entrega` varchar(255) NOT NULL,
  `fecha_entrega` date NOT NULL,
  `observaciones` text DEFAULT NULL,
  `estado` varchar(255) NOT NULL DEFAULT \'activo\',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `updated_by` int(10) unsigned DEFAULT NULL,
  `deleted_by` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tes_entregas_libretas_valores_libreta_valor_id_foreign` (`libreta_valor_id`),
  KEY `tes_entregas_libretas_valores_servicio_id_foreign` (`servicio_id`),
  KEY `tes_entregas_libretas_valores_created_by_index` (`created_by`),
  KEY `tes_entregas_libretas_valores_updated_by_index` (`updated_by`),
  KEY `tes_entregas_libretas_valores_deleted_by_index` (`deleted_by`),
  CONSTRAINT `tes_entregas_libretas_valores_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tes_entregas_libretas_valores_deleted_by_foreign` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tes_entregas_libretas_valores_libreta_valor_id_foreign` FOREIGN KEY (`libreta_valor_id`) REFERENCES `tes_libretas_valores` (`id`),
  CONSTRAINT `tes_entregas_libretas_valores_servicio_id_foreign` FOREIGN KEY (`servicio_id`) REFERENCES `tes_servicios` (`id`),
  CONSTRAINT `tes_entregas_libretas_valores_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }

    public function down(): void
    {
        Schema::dropIfExists('tes_entregas_libretas_valores');
    }
};
