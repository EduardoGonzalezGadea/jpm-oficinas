<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE TABLE `tes_prendas` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `planilla_id` bigint(20) unsigned DEFAULT NULL,
  `recibo_serie` varchar(255) NOT NULL,
  `recibo_numero` varchar(255) NOT NULL,
  `recibo_fecha` date NOT NULL,
  `orden_cobro` varchar(255) NOT NULL,
  `titular_nombre` varchar(255) NOT NULL,
  `titular_cedula` varchar(255) DEFAULT NULL,
  `titular_telefono` varchar(255) NOT NULL,
  `medio_pago_id` bigint(20) unsigned NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `concepto` varchar(255) NOT NULL,
  `transferencia` varchar(255) DEFAULT NULL,
  `transferencia_fecha` date DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `updated_by` int(10) unsigned DEFAULT NULL,
  `deleted_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tes_prendas_medio_pago_id_foreign` (`medio_pago_id`),
  KEY `tes_prendas_created_by_foreign` (`created_by`),
  KEY `tes_prendas_updated_by_foreign` (`updated_by`),
  KEY `tes_prendas_deleted_by_foreign` (`deleted_by`),
  KEY `tes_prendas_planilla_id_index` (`planilla_id`),
  CONSTRAINT `tes_prendas_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `tes_prendas_deleted_by_foreign` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`),
  CONSTRAINT `tes_prendas_medio_pago_id_foreign` FOREIGN KEY (`medio_pago_id`) REFERENCES `tes_medio_de_pagos` (`id`),
  CONSTRAINT `tes_prendas_planilla_id_foreign` FOREIGN KEY (`planilla_id`) REFERENCES `tes_prendas_planillas` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tes_prendas_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }

    public function down(): void
    {
        Schema::dropIfExists('tes_prendas');
    }
};
