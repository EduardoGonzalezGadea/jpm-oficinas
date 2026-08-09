<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE TABLE `tes_servicio_tipo_libreta` (
  `servicio_id` bigint(20) unsigned NOT NULL,
  `tipo_libreta_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`servicio_id`,`tipo_libreta_id`),
  KEY `tes_servicio_tipo_libreta_servicio_id_index` (`servicio_id`),
  KEY `tes_servicio_tipo_libreta_tipo_libreta_id_index` (`tipo_libreta_id`),
  CONSTRAINT `tes_servicio_tipo_libreta_servicio_id_foreign` FOREIGN KEY (`servicio_id`) REFERENCES `tes_servicios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tes_servicio_tipo_libreta_tipo_libreta_id_foreign` FOREIGN KEY (`tipo_libreta_id`) REFERENCES `tes_tipos_libretas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }

    public function down(): void
    {
        Schema::dropIfExists('tes_servicio_tipo_libreta');
    }
};
