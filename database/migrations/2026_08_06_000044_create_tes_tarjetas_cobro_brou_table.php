<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE TABLE `tes_tarjetas_cobro_brou` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `fecha_recibido` date NOT NULL,
  `receptor_id` int(10) unsigned NOT NULL,
  `titular_cedula` varchar(255) NOT NULL,
  `titular_nombre` varchar(255) NOT NULL,
  `titular_apellido` varchar(255) NOT NULL,
  `numero_tarjeta` varchar(255) NOT NULL,
  `fecha_entregado` date DEFAULT NULL,
  `entregador_id` int(10) unsigned DEFAULT NULL,
  `fecha_devuelto` date DEFAULT NULL,
  `devolucion_user_id` int(10) unsigned DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `estado` enum(\'Recibido\',\'Entregado\',\'Devuelto\') NOT NULL DEFAULT \'Recibido\',
  `created_by` int(10) unsigned DEFAULT NULL,
  `updated_by` int(10) unsigned DEFAULT NULL,
  `deleted_by` int(10) unsigned DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tes_tarjetas_cobro_brou_receptor_id_foreign` (`receptor_id`),
  KEY `tes_tarjetas_cobro_brou_entregador_id_foreign` (`entregador_id`),
  KEY `tes_tarjetas_cobro_brou_devolucion_user_id_foreign` (`devolucion_user_id`),
  KEY `tes_tarjetas_cobro_brou_created_by_foreign` (`created_by`),
  KEY `tes_tarjetas_cobro_brou_updated_by_foreign` (`updated_by`),
  KEY `tes_tarjetas_cobro_brou_deleted_by_foreign` (`deleted_by`),
  CONSTRAINT `tes_tarjetas_cobro_brou_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `tes_tarjetas_cobro_brou_deleted_by_foreign` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`),
  CONSTRAINT `tes_tarjetas_cobro_brou_devolucion_user_id_foreign` FOREIGN KEY (`devolucion_user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `tes_tarjetas_cobro_brou_entregador_id_foreign` FOREIGN KEY (`entregador_id`) REFERENCES `users` (`id`),
  CONSTRAINT `tes_tarjetas_cobro_brou_receptor_id_foreign` FOREIGN KEY (`receptor_id`) REFERENCES `users` (`id`),
  CONSTRAINT `tes_tarjetas_cobro_brou_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }

    public function down(): void
    {
        Schema::dropIfExists('tes_tarjetas_cobro_brou');
    }
};
