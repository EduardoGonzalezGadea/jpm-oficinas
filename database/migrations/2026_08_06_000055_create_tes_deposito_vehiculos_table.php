<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE TABLE `tes_deposito_vehiculos` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `titular` varchar(255) NOT NULL,
  `cedula` varchar(255) NOT NULL,
  `telefono` varchar(255) DEFAULT NULL,
  `recibo_serie` varchar(255) NOT NULL,
  `recibo_numero` varchar(255) NOT NULL,
  `recibo_fecha` date NOT NULL,
  `orden_cobro` varchar(255) DEFAULT NULL,
  `medio_pago_id` bigint(20) unsigned NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `concepto` text NOT NULL,
  `planilla_id` bigint(20) unsigned DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `deleted_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_recibo` (`recibo_serie`,`recibo_numero`),
  KEY `tes_deposito_vehiculos_medio_pago_id_foreign` (`medio_pago_id`),
  KEY `tes_deposito_vehiculos_recibo_fecha_index` (`recibo_fecha`),
  KEY `tes_deposito_vehiculos_planilla_id_index` (`planilla_id`),
  KEY `tes_deposito_vehiculos_created_by_index` (`created_by`),
  KEY `tes_deposito_vehiculos_updated_by_index` (`updated_by`),
  KEY `tes_deposito_vehiculos_deleted_by_index` (`deleted_by`),
  CONSTRAINT `tes_deposito_vehiculos_medio_pago_id_foreign` FOREIGN KEY (`medio_pago_id`) REFERENCES `tes_medio_de_pagos` (`id`),
  CONSTRAINT `tes_deposito_vehiculos_planilla_id_foreign` FOREIGN KEY (`planilla_id`) REFERENCES `tes_deposito_vehiculo_planillas` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }

    public function down(): void
    {
        Schema::dropIfExists('tes_deposito_vehiculos');
    }
};
