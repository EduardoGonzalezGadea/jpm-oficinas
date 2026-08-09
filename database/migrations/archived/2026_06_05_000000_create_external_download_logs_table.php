<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('external_download_logs', function (Blueprint $table) {
            $table->id();

            // Información del servicio
            $table->string('service_name'); // 'valor_ur', 'sincronizacion_hora', 'valores_soa'
            $table->string('url');
            $table->string('status'); // 'success', 'failure', 'timeout', 'proxy_error', 'http_error'

            // Detalles de la respuesta
            $table->integer('http_status')->nullable();
            $table->integer('duration_ms')->nullable(); // milisegundos
            $table->integer('content_length')->nullable(); // bytes

            // Configuración usada
            $table->string('proxy_used')->nullable(); // 'none', 'configured' (enmascarado)
            $table->boolean('cache_hit')->default(false);

            // Error si aplica
            $table->text('error_message')->nullable();

            // Auditoria
            $table->timestamp('created_at')->useCurrent();
            $table->index('service_name');
            $table->index('status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('external_download_logs');
    }
};
