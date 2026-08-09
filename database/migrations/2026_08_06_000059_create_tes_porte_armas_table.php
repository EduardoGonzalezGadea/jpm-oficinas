<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE TABLE `tes_porte_armas` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `planilla_id` bigint(20) unsigned DEFAULT NULL,
  `fecha` date NOT NULL,
  `orden_cobro` varchar(255) NOT NULL,
  `numero_tramite` varchar(255) NOT NULL,
  `ingreso_contabilidad` varchar(255) DEFAULT NULL,
  `recibo` varchar(255) NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `titular` varchar(255) NOT NULL,
  `cedula` varchar(255) NOT NULL,
  `telefono` varchar(255) DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `updated_by` int(10) unsigned DEFAULT NULL,
  `deleted_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tes_porte_armas_created_by_foreign` (`created_by`),
  KEY `tes_porte_armas_updated_by_foreign` (`updated_by`),
  KEY `tes_porte_armas_deleted_by_foreign` (`deleted_by`),
  KEY `tes_porte_armas_created_by_index` (`created_by`),
  KEY `tes_porte_armas_updated_by_index` (`updated_by`),
  KEY `tes_porte_armas_deleted_by_index` (`deleted_by`),
  KEY `tes_porte_armas_planilla_id_foreign` (`planilla_id`),
  CONSTRAINT `tes_porte_armas_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `tes_porte_armas_deleted_by_foreign` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`),
  CONSTRAINT `tes_porte_armas_planilla_id_foreign` FOREIGN KEY (`planilla_id`) REFERENCES `tes_porte_armas_planillas` (`id`),
  CONSTRAINT `tes_porte_armas_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }

    public function down(): void
    {
        Schema::dropIfExists('tes_porte_armas');
    }
};
