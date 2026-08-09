<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE TABLE `external_download_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `service_name` varchar(255) NOT NULL,
  `url` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL,
  `http_status` int(11) DEFAULT NULL,
  `duration_ms` int(11) DEFAULT NULL,
  `content_length` int(11) DEFAULT NULL,
  `proxy_used` varchar(255) DEFAULT NULL,
  `cache_hit` tinyint(1) NOT NULL DEFAULT 0,
  `error_message` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `external_download_logs_service_name_index` (`service_name`),
  KEY `external_download_logs_status_index` (`status`),
  KEY `external_download_logs_created_at_index` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }

    public function down(): void
    {
        Schema::dropIfExists('external_download_logs');
    }
};
