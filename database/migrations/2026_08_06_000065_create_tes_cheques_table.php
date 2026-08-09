<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE TABLE `tes_cheques` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `cuenta_bancaria_id` bigint(20) unsigned NOT NULL,
  `serie` varchar(20) DEFAULT NULL,
  `numero_cheque` varchar(20) NOT NULL,
  `documento_tipo` varchar(255) DEFAULT NULL,
  `documento_numero` varchar(255) DEFAULT NULL,
  `fecha_emision` date DEFAULT NULL,
  `beneficiario` varchar(150) DEFAULT NULL,
  `monto` decimal(15,2) DEFAULT NULL,
  `concepto` text DEFAULT NULL,
  `estado` enum(\'disponible\',\'emitido\',\'anulado\',\'en_planilla\') NOT NULL DEFAULT \'disponible\',
  `planilla_id` bigint(20) unsigned DEFAULT NULL,
  `fecha_anulacion` date DEFAULT NULL,
  `motivo_anulacion` text DEFAULT NULL,
  `fecha_planilla_anulada` date DEFAULT NULL,
  `planilla_anulada_por` int(10) unsigned DEFAULT NULL,
  `emitido_por` int(10) unsigned DEFAULT NULL,
  `anulado_por` int(10) unsigned DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `updated_by` int(10) unsigned DEFAULT NULL,
  `deleted_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tes_cheques_cuenta_bancaria_id_foreign` (`cuenta_bancaria_id`),
  KEY `tes_cheques_estado_numero_cheque_index` (`estado`,`numero_cheque`),
  KEY `tes_cheques_planilla_anulada_por_foreign` (`planilla_anulada_por`),
  KEY `tes_cheques_emitido_por_foreign` (`emitido_por`),
  KEY `tes_cheques_anulado_por_foreign` (`anulado_por`),
  KEY `tes_cheques_created_by_foreign` (`created_by`),
  KEY `tes_cheques_updated_by_foreign` (`updated_by`),
  KEY `tes_cheques_deleted_by_foreign` (`deleted_by`),
  KEY `tes_cheques_planilla_id_foreign` (`planilla_id`),
  KEY `tes_cheques_emitido_por_index` (`emitido_por`),
  KEY `tes_cheques_anulado_por_index` (`anulado_por`),
  KEY `tes_cheques_created_by_index` (`created_by`),
  KEY `tes_cheques_updated_by_index` (`updated_by`),
  KEY `tes_cheques_deleted_by_index` (`deleted_by`),
  KEY `tes_cheques_planilla_anulada_por_index` (`planilla_anulada_por`),
  CONSTRAINT `tes_cheques_anulado_por_foreign` FOREIGN KEY (`anulado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tes_cheques_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tes_cheques_cuenta_bancaria_id_foreign` FOREIGN KEY (`cuenta_bancaria_id`) REFERENCES `tes_cuentas_bancarias` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tes_cheques_deleted_by_foreign` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tes_cheques_emitido_por_foreign` FOREIGN KEY (`emitido_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tes_cheques_planilla_anulada_por_foreign` FOREIGN KEY (`planilla_anulada_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tes_cheques_planilla_id_foreign` FOREIGN KEY (`planilla_id`) REFERENCES `tes_planillas_cheques` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tes_cheques_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }

    public function down(): void
    {
        Schema::dropIfExists('tes_cheques');
    }
};
