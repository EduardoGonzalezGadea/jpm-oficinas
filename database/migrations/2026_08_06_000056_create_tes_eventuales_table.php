<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE TABLE `tes_eventuales` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `fecha` date NOT NULL,
  `ingreso` int(11) DEFAULT NULL,
  `institucion` varchar(255) DEFAULT NULL,
  `titular` varchar(255) DEFAULT NULL,
  `monto` decimal(10,2) NOT NULL,
  `medio_de_pago` varchar(255) NOT NULL,
  `medio_pago_id` bigint(20) unsigned DEFAULT NULL,
  `detalle` text DEFAULT NULL,
  `orden_cobro` varchar(255) DEFAULT NULL,
  `recibo` varchar(255) DEFAULT NULL,
  `confirmado` tinyint(1) NOT NULL DEFAULT 0,
  `planilla_id` bigint(20) unsigned DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `updated_by` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tes_eventuales_planilla_id_foreign` (`planilla_id`),
  KEY `tes_eventuales_created_by_foreign` (`created_by`),
  KEY `tes_eventuales_updated_by_foreign` (`updated_by`),
  KEY `tes_eventuales_deleted_by_foreign` (`deleted_by`),
  KEY `tes_eventuales_medio_pago_id_foreign` (`medio_pago_id`),
  CONSTRAINT `tes_eventuales_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tes_eventuales_deleted_by_foreign` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tes_eventuales_medio_pago_id_foreign` FOREIGN KEY (`medio_pago_id`) REFERENCES `tes_medio_de_pagos` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tes_eventuales_planilla_id_foreign` FOREIGN KEY (`planilla_id`) REFERENCES `tes_eventuales_planillas` (`id`),
  CONSTRAINT `tes_eventuales_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }

    public function down(): void
    {
        Schema::dropIfExists('tes_eventuales');
    }
};
