<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE TABLE `tes_cch_pagos` (
  `idPagos` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `relCajaChica_Pagos` bigint(20) unsigned NOT NULL,
  `fechaEgresoPagos` date NOT NULL,
  `egresoPagos` varchar(50) DEFAULT NULL,
  `fechaEgresoEfectivoPagos` date DEFAULT NULL,
  `relAcreedores` bigint(20) unsigned DEFAULT NULL,
  `conceptoPagos` varchar(255) NOT NULL,
  `montoPagos` decimal(15,2) NOT NULL,
  `rendidoPagos` decimal(15,2) DEFAULT NULL,
  `reintegradoPagos` decimal(15,2) DEFAULT NULL,
  `ingresoReintegroPagos` varchar(255) DEFAULT NULL,
  `fechaRendicionPagos` date DEFAULT NULL,
  `fechaIngresoPagos` date DEFAULT NULL,
  `ingresoPagos` varchar(50) DEFAULT NULL,
  `ingresoPagosBSE` varchar(50) DEFAULT NULL,
  `fechaIngresoBSEPagos` date DEFAULT NULL,
  `recuperadoPagos` decimal(15,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `updated_by` int(10) unsigned DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_by` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`idPagos`),
  KEY `tes_cch_pagos_relcajachica_pagos_foreign` (`relCajaChica_Pagos`),
  KEY `tes_cch_pagos_relacreedores_foreign` (`relAcreedores`),
  KEY `tes_cch_pagos_relcajachica_pagos_index` (`relCajaChica_Pagos`),
  KEY `tes_cch_pagos_relacreedores_index` (`relAcreedores`),
  KEY `tes_cch_pagos_created_by_foreign` (`created_by`),
  KEY `tes_cch_pagos_updated_by_foreign` (`updated_by`),
  KEY `tes_cch_pagos_deleted_by_foreign` (`deleted_by`),
  CONSTRAINT `tes_cch_pagos_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tes_cch_pagos_deleted_by_foreign` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tes_cch_pagos_relacreedores_foreign` FOREIGN KEY (`relAcreedores`) REFERENCES `tes_cch_acreedores` (`idAcreedores`) ON DELETE SET NULL,
  CONSTRAINT `tes_cch_pagos_relcajachica_pagos_foreign` FOREIGN KEY (`relCajaChica_Pagos`) REFERENCES `tes_caja_chica` (`idCajaChica`) ON DELETE CASCADE,
  CONSTRAINT `tes_cch_pagos_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }

    public function down(): void
    {
        Schema::dropIfExists('tes_cch_pagos');
    }
};
