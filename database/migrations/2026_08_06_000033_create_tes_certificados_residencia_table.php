<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE TABLE `tes_certificados_residencia` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `fecha_recibido` date NOT NULL,
  `receptor_id` int(10) unsigned NOT NULL,
  `titular_nombre` varchar(255) NOT NULL,
  `titular_apellido` varchar(255) NOT NULL,
  `titular_tipo_documento` enum(\'Cédula\',\'Cédula Extranjera\',\'Pasaporte\',\'Otro\') NOT NULL,
  `titular_nro_documento` varchar(255) NOT NULL,
  `fecha_entregado` date DEFAULT NULL,
  `entregador_id` int(10) unsigned DEFAULT NULL,
  `retira_nombre` varchar(255) DEFAULT NULL,
  `retira_apellido` varchar(255) DEFAULT NULL,
  `retira_tipo_documento` enum(\'Cédula\',\'Cédula Extranjera\',\'Pasaporte\',\'Otro\') DEFAULT NULL,
  `retira_nro_documento` varchar(255) DEFAULT NULL,
  `retira_telefono` varchar(255) DEFAULT NULL,
  `numero_recibo` varchar(255) DEFAULT NULL,
  `monto` decimal(12,2) DEFAULT NULL,
  `fecha_devuelto` date DEFAULT NULL,
  `devolucion_user_id` int(10) unsigned DEFAULT NULL,
  `estado` enum(\'Recibido\',\'Entregado\',\'Devuelto\') NOT NULL DEFAULT \'Recibido\',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `updated_by` int(10) unsigned DEFAULT NULL,
  `deleted_by` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tes_certificados_residencia_receptor_id_foreign` (`receptor_id`),
  KEY `tes_certificados_residencia_entregador_id_foreign` (`entregador_id`),
  KEY `tes_certificados_residencia_devolucion_user_id_foreign` (`devolucion_user_id`),
  KEY `tes_certificados_residencia_created_by_foreign` (`created_by`),
  KEY `tes_certificados_residencia_updated_by_foreign` (`updated_by`),
  KEY `tes_certificados_residencia_deleted_by_foreign` (`deleted_by`),
  KEY `tes_certificados_residencia_receptor_id_index` (`receptor_id`),
  KEY `tes_certificados_residencia_entregador_id_index` (`entregador_id`),
  KEY `tes_certificados_residencia_devolucion_user_id_index` (`devolucion_user_id`),
  KEY `tes_certificados_residencia_created_by_index` (`created_by`),
  KEY `tes_certificados_residencia_updated_by_index` (`updated_by`),
  KEY `tes_certificados_residencia_deleted_by_index` (`deleted_by`),
  CONSTRAINT `tes_certificados_residencia_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `tes_certificados_residencia_deleted_by_foreign` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`),
  CONSTRAINT `tes_certificados_residencia_devolucion_user_id_foreign` FOREIGN KEY (`devolucion_user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `tes_certificados_residencia_entregador_id_foreign` FOREIGN KEY (`entregador_id`) REFERENCES `users` (`id`),
  CONSTRAINT `tes_certificados_residencia_receptor_id_foreign` FOREIGN KEY (`receptor_id`) REFERENCES `users` (`id`),
  CONSTRAINT `tes_certificados_residencia_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }

    public function down(): void
    {
        Schema::dropIfExists('tes_certificados_residencia');
    }
};
