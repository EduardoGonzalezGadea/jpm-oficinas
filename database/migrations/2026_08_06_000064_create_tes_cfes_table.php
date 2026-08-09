<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE TABLE `tes_cfes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `emisor_nombre` varchar(255) DEFAULT NULL,
  `emisor_direccion` varchar(255) DEFAULT NULL,
  `emisor_localidad` varchar(255) DEFAULT NULL,
  `emisor_telefono` varchar(255) DEFAULT NULL,
  `emisor_correo` varchar(255) DEFAULT NULL,
  `emisor_ruc` varchar(255) DEFAULT NULL,
  `documento_tipo` varchar(255) DEFAULT NULL,
  `documento_serie` varchar(255) DEFAULT NULL,
  `documento_numero` varchar(255) DEFAULT NULL,
  `forma_pago` varchar(255) DEFAULT NULL,
  `vencimiento` date DEFAULT NULL,
  `comprobante_tipo` varchar(255) DEFAULT NULL,
  `receptor_documento_ruc` varchar(255) DEFAULT NULL,
  `receptor_nombre_denominacion` varchar(255) DEFAULT NULL,
  `receptor_domicilio_fiscal` varchar(255) DEFAULT NULL,
  `periodo` varchar(255) DEFAULT NULL,
  `nro_compra` varchar(255) DEFAULT NULL,
  `fecha` date DEFAULT NULL,
  `moneda` varchar(10) NOT NULL DEFAULT \'UYU\',
  `monto_no_facturable` decimal(12,2) NOT NULL DEFAULT 0.00,
  `monto_total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_a_pagar` decimal(12,2) NOT NULL DEFAULT 0.00,
  `referencias` text DEFAULT NULL,
  `adenda` text DEFAULT NULL,
  `archivo_pdf_path` varchar(255) DEFAULT NULL,
  `tes_caja_concepto_id` bigint(20) unsigned DEFAULT NULL,
  `siif_distribucion_tipo_id` bigint(20) unsigned DEFAULT NULL,
  `siif_distribucion_dependencia_id` bigint(20) unsigned DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `updated_by` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tes_cfes_tes_caja_concepto_id_foreign` (`tes_caja_concepto_id`),
  KEY `tes_cfes_siif_distribucion_tipo_id_foreign` (`siif_distribucion_tipo_id`),
  KEY `tes_cfes_siif_distribucion_dependencia_id_foreign` (`siif_distribucion_dependencia_id`),
  KEY `idx_tes_cfes_documento` (`documento_tipo`,`documento_numero`),
  KEY `idx_tes_cfes_fecha` (`fecha`),
  KEY `idx_tes_cfes_receptor_ruc` (`receptor_documento_ruc`),
  KEY `idx_tes_cfes_comprobante_tipo` (`comprobante_tipo`),
  KEY `idx_tes_cfes_deleted_at` (`deleted_at`),
  KEY `tes_cfes_documento_serie_index` (`documento_serie`),
  KEY `tes_cfes_emisor_ruc_index` (`emisor_ruc`),
  KEY `tes_cfes_vencimiento_index` (`vencimiento`),
  KEY `idx_tes_cfes_fecha_dependencia` (`fecha`,`siif_distribucion_dependencia_id`),
  KEY `idx_tes_cfes_deleted_fecha` (`deleted_at`,`fecha`),
  CONSTRAINT `tes_cfes_siif_distribucion_dependencia_id_foreign` FOREIGN KEY (`siif_distribucion_dependencia_id`) REFERENCES `siif_distribucion_dependencias` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tes_cfes_siif_distribucion_tipo_id_foreign` FOREIGN KEY (`siif_distribucion_tipo_id`) REFERENCES `siif_distribucion_tipos` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tes_cfes_tes_caja_concepto_id_foreign` FOREIGN KEY (`tes_caja_concepto_id`) REFERENCES `tes_caja_conceptos` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }

    public function down(): void
    {
        Schema::dropIfExists('tes_cfes');
    }
};
