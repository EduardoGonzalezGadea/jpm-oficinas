<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE TABLE `tes_libretas_valores` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tipo_libreta_id` bigint(20) unsigned NOT NULL,
  `serie` varchar(255) DEFAULT NULL,
  `numero_inicial` int(11) NOT NULL,
  `numero_final` int(11) NOT NULL,
  `fecha_recepcion` date NOT NULL,
  `estado` varchar(255) NOT NULL DEFAULT \'en_stock\',
  `proximo_recibo_disponible` int(11) DEFAULT NULL,
  `servicio_asignado_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `updated_by` int(10) unsigned DEFAULT NULL,
  `deleted_by` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tes_libretas_valores_tipo_libreta_id_foreign` (`tipo_libreta_id`),
  KEY `tes_libretas_valores_servicio_asignado_id_foreign` (`servicio_asignado_id`),
  KEY `tes_libretas_valores_created_by_index` (`created_by`),
  KEY `tes_libretas_valores_updated_by_index` (`updated_by`),
  KEY `tes_libretas_valores_deleted_by_index` (`deleted_by`),
  CONSTRAINT `tes_libretas_valores_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tes_libretas_valores_deleted_by_foreign` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tes_libretas_valores_servicio_asignado_id_foreign` FOREIGN KEY (`servicio_asignado_id`) REFERENCES `tes_servicios` (`id`),
  CONSTRAINT `tes_libretas_valores_tipo_libreta_id_foreign` FOREIGN KEY (`tipo_libreta_id`) REFERENCES `tes_tipos_libretas` (`id`),
  CONSTRAINT `tes_libretas_valores_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }

    public function down(): void
    {
        Schema::dropIfExists('tes_libretas_valores');
    }
};
