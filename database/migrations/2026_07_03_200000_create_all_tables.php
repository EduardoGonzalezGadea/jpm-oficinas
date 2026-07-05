<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::disableForeignKeyConstraints();

        // ============================================================
        // 0. SESIONES (necesaria para SESSION_DRIVER=database)
        // ============================================================

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->text('payload');
            $table->integer('last_activity')->index();
        });

        // ============================================================
        // 1. TABLAS INDEPENDIENTES (sin FK a otras tablas propias)
        // ============================================================

        // ---- 1.1 modulos ----
        Schema::create('modulos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('clave', 50)->nullable()->unique();
            $table->string('descripcion')->nullable();
            $table->boolean('activo')->default(true);
            $table->softDeletes();
            $table->timestamps();
            $table->unsignedInteger('created_by')->nullable()->index();
            $table->unsignedInteger('updated_by')->nullable()->index();
            $table->unsignedInteger('deleted_by')->nullable()->index();
        });

        // ---- 1.2 users ----
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedBigInteger('modulo_id')->nullable();
            $table->string('nombre');
            $table->string('apellido');
            $table->string('email')->unique();
            $table->string('theme')->default('default');
            $table->string('telefono')->nullable();
            $table->text('direccion')->nullable();
            $table->string('cedula')->nullable()->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->timestamp('two_factor_confirmed_at')->nullable();
            $table->boolean('activo')->default(true);
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('modulo_id')->references('id')->on('modulos');
        });

        // ---- 1.3 external_download_logs ----
        Schema::create('external_download_logs', function (Blueprint $table) {
            $table->id();
            $table->string('service_name');
            $table->string('url');
            $table->string('status');
            $table->integer('http_status')->nullable();
            $table->integer('duration_ms')->nullable();
            $table->integer('content_length')->nullable();
            $table->string('proxy_used')->nullable();
            $table->boolean('cache_hit')->default(false);
            $table->text('error_message')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('service_name');
            $table->index('status');
            $table->index('created_at');
        });

        // ---- 1.4 tes_bancos ----
        Schema::create('tes_bancos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->string('codigo', 20)->unique();
            $table->text('observaciones')->nullable();
            $table->unsignedInteger('created_by')->nullable()->index();
            $table->unsignedInteger('updated_by')->nullable()->index();
            $table->unsignedInteger('deleted_by')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('deleted_by')->references('id')->on('users')->onDelete('set null');
        });

        // ---- 1.5 tes_categorias_222 ----
        Schema::create('tes_categorias_222', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->string('codigo', 30)->unique();
            $table->boolean('activo')->default(true);
            $table->integer('orden')->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // ---- 1.6 tes_instituciones_222 ----
        Schema::create('tes_instituciones_222', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 150);
            $table->string('codigo', 30)->nullable();
            $table->boolean('activo')->default(true);
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->unsignedInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // ---- 1.7 tes_medio_de_pagos ----
        Schema::create('tes_medio_de_pagos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->boolean('activo')->default(true);
            $table->boolean('contado')->default(false);
            $table->string('codigo_soniar')->nullable();
            $table->unsignedInteger('created_by')->nullable()->index();
            $table->unsignedInteger('updated_by')->nullable()->index();
            $table->unsignedInteger('deleted_by')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();
        });

        // ---- 1.8 tes_tipos_monedas ----
        Schema::create('tes_tipos_monedas', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 10);
            $table->string('nombre');
            $table->string('simbolo', 10)->nullable();
            $table->boolean('activo')->default(true);
            $table->unsignedInteger('created_by')->nullable()->index();
            $table->unsignedInteger('updated_by')->nullable()->index();
            $table->unsignedInteger('deleted_by')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();
        });

        // ---- 1.9 tes_servicios ----
        Schema::create('tes_servicios', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('descripcion')->nullable();
            $table->decimal('valor_ui', 10, 2)->nullable();
            $table->boolean('activo')->default(true);
            $table->unsignedInteger('created_by')->nullable()->index();
            $table->unsignedInteger('updated_by')->nullable()->index();
            $table->unsignedInteger('deleted_by')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();
        });

        // ---- 1.10 tes_tipos_libretas ----
        Schema::create('tes_tipos_libretas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('descripcion')->nullable();
            $table->unsignedInteger('created_by')->nullable()->index();
            $table->unsignedInteger('updated_by')->nullable()->index();
            $table->unsignedInteger('deleted_by')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();
        });

        // ---- 1.11 tes_tarjetas_cobro_brou ----
        Schema::create('tes_tarjetas_cobro_brou', function (Blueprint $table) {
            $table->id();
            $table->string('numero_tarjeta', 50);
            $table->string('descripcion')->nullable();
            $table->string('titular');
            $table->boolean('activa')->default(true);
            $table->unsignedInteger('created_by')->nullable()->index();
            $table->unsignedInteger('updated_by')->nullable()->index();
            $table->unsignedInteger('deleted_by')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();
        });

        // ---- 1.12 tes_eventuales_instituciones ----
        Schema::create('tes_eventuales_instituciones', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('descripcion')->nullable();
            $table->softDeletes();
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->unsignedInteger('deleted_by')->nullable();
            $table->timestamps();
        });

        // ---- 1.13 tes_eventuales_planillas ----
        Schema::create('tes_eventuales_planillas', function (Blueprint $table) {
            $table->id();
            $table->string('numero');
            $table->date('fecha_creacion');
            $table->unsignedInteger('user_id')->nullable()->index();
            $table->unsignedInteger('created_by')->nullable()->index();
            $table->unsignedInteger('updated_by')->nullable()->index();
            $table->unsignedInteger('deleted_by')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();
        });

        // ---- 1.14 tes_deposito_vehiculo_planillas ----
        Schema::create('tes_deposito_vehiculo_planillas', function (Blueprint $table) {
            $table->id();
            $table->string('numero')->unique();
            $table->date('fecha');
            $table->boolean('anulada')->default(false);
            $table->dateTime('anulada_fecha')->nullable();
            $table->unsignedBigInteger('anulada_by')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('fecha');
            $table->index('anulada');
            $table->index('anulada_by');
            $table->index('created_by');
            $table->index('updated_by');
            $table->foreign('deleted_by')->references('id')->on('users')->onDelete('set null');
        });

        // ---- 1.15 tes_porte_armas ----
        Schema::create('tes_porte_armas', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');
            $table->string('nombre');
            $table->string('cedula');
            $table->string('telefono')->nullable();
            $table->string('recibo_serie');
            $table->string('recibo_numero');
            $table->date('recibo_fecha');
            $table->string('orden_cobro')->nullable();
            $table->unsignedBigInteger('medio_pago_id');
            $table->decimal('monto', 10, 2);
            $table->text('concepto');
            $table->unsignedBigInteger('planilla_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['recibo_serie', 'recibo_numero']);
            $table->index('recibo_fecha');
            $table->index('planilla_id');
            $table->index('created_by');
            $table->index('updated_by');
            $table->index('deleted_by');
        });

        // ---- 1.16 tes_tenencia_armas ----
        Schema::create('tes_tenencia_armas', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');
            $table->string('nombre');
            $table->string('cedula');
            $table->string('telefono')->nullable();
            $table->string('domicilio')->nullable();
            $table->string('recibo_serie');
            $table->string('recibo_numero');
            $table->date('recibo_fecha');
            $table->string('orden_cobro')->nullable();
            $table->unsignedBigInteger('medio_pago_id');
            $table->decimal('monto', 10, 2);
            $table->text('concepto');
            $table->unsignedBigInteger('planilla_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['recibo_serie', 'recibo_numero']);
            $table->index('recibo_fecha');
            $table->index('planilla_id');
            $table->index('created_by');
            $table->index('updated_by');
            $table->index('deleted_by');
        });

        // ---- 1.17 tes_caja_chica ----
        Schema::create('tes_caja_chica', function (Blueprint $table) {
            $table->id('idCajaChica');
            $table->string('mes', 20);
            $table->integer('anio');
            $table->decimal('montoCajaChica', 15, 2)->default(0.00);
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->unsignedInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('deleted_by')->references('id')->on('users')->onDelete('set null');
        });

        // ---- 1.18 tes_cch_acreedores ----
        Schema::create('tes_cch_acreedores', function (Blueprint $table) {
            $table->id('idAcreedores');
            $table->string('acreedor');
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->unsignedInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('deleted_by')->references('id')->on('users')->onDelete('set null');
        });

        // ---- 1.19 tes_cch_dependencias ----
        Schema::create('tes_cch_dependencias', function (Blueprint $table) {
            $table->id('idDependencias');
            $table->string('dependencia');
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->unsignedInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('deleted_by')->references('id')->on('users')->onDelete('set null');
        });

        // ---- 1.20 tes_conceptos ----
        Schema::create('tes_conceptos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 200);
            $table->string('codigo_siif', 50)->nullable();
            $table->enum('tipo', ['Ingreso', 'Egreso', 'Ambos'])->default('Ingreso');
            $table->boolean('requiere_institucion')->default(false);
            $table->boolean('activo')->default(true);
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->unsignedInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('activo');
            $table->index('tipo');
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
            $table->foreign('deleted_by')->references('id')->on('users');
        });

        // ---- 1.21 tes_denominaciones_monedas ----
        Schema::create('tes_denominaciones_monedas', function (Blueprint $table) {
            $table->id();
            $table->string('denominacion');
            $table->decimal('valor', 18, 2);
            $table->string('moneda');
            $table->string('tipo_moneda');
            $table->string('descripcion')->nullable();
            $table->boolean('activo')->default(true);
            $table->unsignedInteger('created_by')->nullable()->index();
            $table->unsignedInteger('updated_by')->nullable()->index();
            $table->unsignedInteger('deleted_by')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
            $table->foreign('deleted_by')->references('id')->on('users');
        });

        // ---- 1.22 tes_multas ----
        Schema::create('tes_multas', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->nullable();
            $table->string('articulo', 50);
            $table->string('literal', 50)->nullable();
            $table->string('apartado', 50)->nullable();
            $table->text('descripcion');
            $table->text('articulo_completo')->nullable();
            $table->string('moneda', 10)->default('UR');
            $table->decimal('importe_original', 12, 2)->nullable();
            $table->decimal('importe_unificado', 12, 2)->nullable();
            $table->string('decreto')->nullable();
            $table->decimal('monto_ur', 10, 4)->default(0);
            $table->decimal('monto_ui', 10, 4)->default(0);
            $table->decimal('monto_pesos', 12, 2)->default(0);
            $table->string('inciso_legal')->nullable();
            $table->boolean('visible')->default(true);
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->unsignedInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // ---- 1.23 tes_multas_303_2023 ----
        Schema::create('tes_multas_303_2023', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 100);
            $table->text('descripcion');
            $table->text('grupo')->nullable();
            $table->text('detalle')->nullable();
            $table->decimal('monto_ur', 10, 4);
            $table->string('valor_ur')->nullable();
            $table->decimal('monto_pesos', 12, 2)->default(0);
            $table->boolean('activo')->default(true);
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->unsignedInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // ---- 1.24 tes_arr_planillas ----
        Schema::create('tes_arr_planillas', function (Blueprint $table) {
            $table->id();
            $table->string('numero');
            $table->date('fecha_creacion');
            $table->unsignedInteger('user_id')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->unsignedInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['numero', 'deleted_at']);
            $table->index('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('deleted_by')->references('id')->on('users')->onDelete('set null');
        });

        // ---- 1.25 siif_distribucion_dependencias ----
        Schema::create('siif_distribucion_dependencias', function (Blueprint $table) {
            $table->id();
            $table->string('dependencia');
            $table->string('abreviatura');
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->unsignedInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('deleted_by')->references('id')->on('users')->onDelete('set null');
        });

        // ---- 1.26 siif_distribucion_tipos ----
        Schema::create('siif_distribucion_tipos', function (Blueprint $table) {
            $table->id();
            $table->string('tipo');
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->unsignedInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('deleted_by')->references('id')->on('users')->onDelete('set null');
        });

        // ---- 1.27 tes_planilla_ers ----
        Schema::create('tes_planilla_ers', function (Blueprint $table) {
            $table->id();
            $table->string('numero');
            $table->date('fecha');
            $table->string('turno', 50)->nullable();
            $table->string('er_numero')->nullable();
            $table->date('transferencia_fecha')->nullable();
            $table->string('transferencia_confirmacion')->nullable();
            $table->unsignedBigInteger('tipo_id');
            $table->unsignedBigInteger('dependencia_id');
            $table->string('ingresos_numero')->nullable();
            $table->string('egresos_numero')->nullable();
            $table->date('fecha_confirmacion')->nullable();
            $table->boolean('confirmada')->default(false);
            $table->string('confirmacion_numero')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->unsignedInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tipo_id')->references('id')->on('siif_distribucion_tipos');
            $table->foreign('dependencia_id')->references('id')->on('siif_distribucion_dependencias');
        });

        // ---- 1.28 tes_certificados_residencia ----
        Schema::create('tes_certificados_residencia', function (Blueprint $table) {
            $table->id();
            $table->date('fecha_recibido');
            $table->unsignedInteger('receptor_id');
            $table->string('titular_nombre');
            $table->string('titular_apellido');
            $table->enum('titular_tipo_documento', ['Cédula', 'Cédula Extranjera', 'Pasaporte', 'Otro']);
            $table->string('titular_nro_documento');
            $table->date('fecha_entregado')->nullable();
            $table->unsignedInteger('entregador_id')->nullable();
            $table->string('retira_nombre')->nullable();
            $table->string('retira_apellido')->nullable();
            $table->enum('retira_tipo_documento', ['Cédula', 'Cédula Extranjera', 'Pasaporte', 'Otro'])->nullable();
            $table->string('retira_nro_documento')->nullable();
            $table->string('retira_telefono')->nullable();
            $table->string('numero_recibo')->nullable();
            $table->decimal('monto', 12, 2)->nullable();
            $table->date('fecha_devuelto')->nullable();
            $table->unsignedInteger('devolucion_user_id')->nullable();
            $table->enum('estado', ['Recibido', 'Entregado', 'Devuelto'])->default('Recibido');
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->unsignedInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('receptor_id');
            $table->index('entregador_id');
            $table->index('devolucion_user_id');
            $table->index('created_by');
            $table->index('updated_by');
            $table->index('deleted_by');
            $table->foreign('receptor_id')->references('id')->on('users');
            $table->foreign('entregador_id')->references('id')->on('users');
            $table->foreign('devolucion_user_id')->references('id')->on('users');
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
            $table->foreign('deleted_by')->references('id')->on('users');
        });

        // ---- 1.29 tes_multas_cobradas ----
        Schema::create('tes_multas_cobradas', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');
            $table->string('nombre');
            $table->string('cedula');
            $table->string('domicilio')->nullable();
            $table->string('telefono')->nullable();
            $table->string('recibo_serie');
            $table->string('recibo_numero');
            $table->date('recibo_fecha');
            $table->string('orden_cobro')->nullable();
            $table->unsignedBigInteger('medio_pago_id');
            $table->string('medio_pago_nombre');
            $table->decimal('monto', 10, 2);
            $table->text('concepto');
            $table->unsignedBigInteger('planilla_id')->nullable();
            $table->string('forma_pago', 50)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['recibo_serie', 'recibo_numero']);
            $table->index('recibo_fecha');
            $table->index('planilla_id');
            $table->index('created_by');
            $table->index('updated_by');
            $table->index('deleted_by');
            $table->foreign('medio_pago_id')->references('id')->on('tes_medio_de_pagos');
            $table->foreign('planilla_id')->references('id')->on('tes_eventuales_planillas')->onDelete('set null');
        });

        // ---- 1.30 tes_multas_items ----
        Schema::create('tes_multas_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tes_multas_cobradas_id');
            $table->string('codigo');
            $table->string('descripcion');
            $table->text('detalle')->nullable();
            $table->decimal('importe', 12, 2)->nullable();
            $table->decimal('monto_ur', 10, 4);
            $table->decimal('monto_pesos', 12, 2)->default(0);
            $table->decimal('subtotal', 12, 2);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tes_multas_cobradas_id')->references('id')->on('tes_multas_cobradas');
        });

        // ---- 1.31 tes_er_definiciones ----
        Schema::create('tes_er_definiciones', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->string('codigo', 50)->unique();
            $table->enum('tipo_recaudacion', ['LD', '222'])->default('LD');
            $table->unsignedBigInteger('institucion_222_id')->nullable();
            $table->string('turno', 20)->nullable();
            $table->boolean('activo')->default(true);
            $table->integer('orden')->default(0);
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->unsignedInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('institucion_222_id')->references('id')->on('tes_instituciones_222');
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
            $table->foreign('deleted_by')->references('id')->on('users');
        });

        // ---- 1.32 tes_arrendamientos ----
        Schema::create('tes_arrendamientos', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');
            $table->integer('ingreso')->nullable();
            $table->string('nombre')->nullable();
            $table->string('cedula')->nullable();
            $table->string('telefono')->nullable();
            $table->decimal('monto', 10, 2);
            $table->text('detalle')->nullable();
            $table->string('orden_cobro')->nullable();
            $table->string('recibo')->nullable();
            $table->string('medio_de_pago')->default('Transferencia')->comment('Medios de pago: Efectivo, Transferencia, POS, Cheque');
            $table->boolean('confirmado')->default(false);
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->unsignedInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('planilla_id')->nullable();

            $table->index('planilla_id');
            $table->foreign('planilla_id')->references('id')->on('tes_arr_planillas');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('deleted_by')->references('id')->on('users')->onDelete('set null');
        });

        // ---- 1.33 tes_eventuales ----
        Schema::create('tes_eventuales', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');
            $table->integer('ingreso')->nullable();
            $table->string('institucion')->nullable();
            $table->string('titular')->nullable();
            $table->decimal('monto', 10, 2);
            $table->string('medio_de_pago');
            $table->text('detalle')->nullable();
            $table->string('orden_cobro')->nullable();
            $table->string('recibo')->nullable();
            $table->boolean('confirmado')->default(false);
            $table->unsignedBigInteger('planilla_id')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->unsignedInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('planilla_id');
            $table->foreign('planilla_id')->references('id')->on('tes_eventuales_planillas');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('deleted_by')->references('id')->on('users')->onDelete('set null');
        });

        // ---- 1.34 tes_libretas_valores ----
        Schema::create('tes_libretas_valores', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tipo_libreta_id');
            $table->unsignedBigInteger('servicio_id');
            $table->string('numero_libreta');
            $table->date('fecha_alta');
            $table->decimal('monto', 10, 2);
            $table->string('serie_recibo')->nullable();
            $table->string('numero_recibo')->nullable();
            $table->string('ano_recibo', 4)->nullable();
            $table->string('estado')->default('activo');
            $table->text('observaciones')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->unsignedInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tipo_libreta_id')->references('id')->on('tes_tipos_libretas');
            $table->foreign('servicio_id')->references('id')->on('tes_servicios');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('deleted_by')->references('id')->on('users')->onDelete('set null');
        });

        // ---- 1.35 tes_servicio_tipo_libreta (pivot) ----
        Schema::create('tes_servicio_tipo_libreta', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('servicio_id');
            $table->unsignedBigInteger('tipo_libreta_id');
            $table->timestamps();

            $table->foreign('servicio_id')->references('id')->on('tes_servicios');
            $table->foreign('tipo_libreta_id')->references('id')->on('tes_tipos_libretas');
        });

        // ---- 1.36 tes_entregas_libretas_valores ----
        Schema::create('tes_entregas_libretas_valores', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('libreta_valor_id');
            $table->unsignedBigInteger('servicio_id');
            $table->string('numero_recibo_entrega');
            $table->date('fecha_entrega');
            $table->text('observaciones')->nullable();
            $table->string('estado')->default('activo');
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->unsignedInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('created_by');
            $table->index('updated_by');
            $table->index('deleted_by');
            $table->foreign('libreta_valor_id')->references('id')->on('tes_libretas_valores');
            $table->foreign('servicio_id')->references('id')->on('tes_servicios');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('deleted_by')->references('id')->on('users')->onDelete('set null');
        });

        // ---- 1.37 siif_distribucions ----
        Schema::create('siif_distribucions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tipo_id');
            $table->unsignedBigInteger('dependencia_id');
            $table->string('rubro')->nullable();
            $table->string('sub_rubro')->nullable();
            $table->string('recurso')->nullable();
            $table->string('concepto')->nullable();
            $table->string('codigo_sir')->nullable();
            $table->decimal('porcentaje', 6, 3);
            $table->string('financiacion')->nullable();
            $table->string('inciso')->nullable();
            $table->string('unidad_ejecutora')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->unsignedInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tipo_id')->references('id')->on('siif_distribucion_tipos');
            $table->foreign('dependencia_id')->references('id')->on('siif_distribucion_dependencias');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('deleted_by')->references('id')->on('users')->onDelete('set null');
        });

        // ---- 1.38 tes_caja_conceptos ----
        Schema::create('tes_caja_conceptos', function (Blueprint $table) {
            $table->id();
            $table->string('caja_concepto');
            $table->boolean('requiere_confirmacion')->default(false);
            $table->boolean('requiere_distribucion')->default(false);
            $table->boolean('permite_planilla')->default(false);
            $table->boolean('requiere_organismo')->default(false)->comment('Indica si el concepto requiere seleccionar un organismo/entidad');
            $table->unsignedBigInteger('siif_distribucion_tipo_id')->nullable()->comment('Tipo de distribución SIIF asociado a este concepto');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('created_by');
            $table->index('updated_by');
            $table->index('deleted_by');
            $table->foreign('siif_distribucion_tipo_id')->references('id')->on('siif_distribucion_tipos')->onDelete('set null');
        });

        // ---- 1.39 tes_cuentas_bancarias ----
        Schema::create('tes_cuentas_bancarias', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('banco_id');
            $table->string('numero_cuenta', 50);
            $table->string('tipo', 20);
            $table->boolean('activa')->default(true);
            $table->text('observaciones')->nullable();
            $table->unsignedInteger('created_by')->nullable()->index();
            $table->unsignedInteger('updated_by')->nullable()->index();
            $table->unsignedInteger('deleted_by')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('banco_id')->references('id')->on('tes_bancos')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('deleted_by')->references('id')->on('users')->onDelete('set null');
        });

        // ---- 1.40 tes_planillas_cheques ----
        Schema::create('tes_planillas_cheques', function (Blueprint $table) {
            $table->id();
            $table->string('numero');
            $table->date('fecha');
            $table->unsignedInteger('created_by')->nullable()->index();
            $table->unsignedInteger('updated_by')->nullable()->index();
            $table->unsignedInteger('deleted_by')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();
        });

        // ---- 1.41 tes_cheques ----
        Schema::create('tes_cheques', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cuenta_bancaria_id');
            $table->string('serie', 20)->nullable();
            $table->string('numero_cheque', 20);
            $table->string('documento_tipo')->nullable();
            $table->string('documento_numero')->nullable();
            $table->date('fecha_emision')->nullable();
            $table->string('beneficiario', 150)->nullable();
            $table->decimal('monto', 15, 2)->nullable();
            $table->text('concepto')->nullable();
            $table->enum('estado', ['disponible', 'emitido', 'anulado', 'en_planilla'])->default('disponible');
            $table->unsignedBigInteger('planilla_id')->nullable();
            $table->date('fecha_anulacion')->nullable();
            $table->text('motivo_anulacion')->nullable();
            $table->date('fecha_planilla_anulada')->nullable();
            $table->unsignedInteger('planilla_anulada_por')->nullable();
            $table->unsignedInteger('emitido_por')->nullable();
            $table->unsignedInteger('anulado_por')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->unsignedInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['estado', 'numero_cheque']);
            $table->index('emitido_por');
            $table->index('anulado_por');
            $table->index('created_by');
            $table->index('updated_by');
            $table->index('deleted_by');
            $table->index('planilla_anulada_por');
            $table->foreign('cuenta_bancaria_id')->references('id')->on('tes_cuentas_bancarias')->onDelete('cascade');
            $table->foreign('planilla_id')->references('id')->on('tes_planillas_cheques')->onDelete('set null');
            $table->foreign('planilla_anulada_por')->references('id')->on('users')->onDelete('set null');
            $table->foreign('emitido_por')->references('id')->on('users')->onDelete('set null');
            $table->foreign('anulado_por')->references('id')->on('users')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('deleted_by')->references('id')->on('users')->onDelete('set null');
        });

        // ---- 1.42 tes_anulaciones (polymorphic) ----
        Schema::create('tes_anulaciones', function (Blueprint $table) {
            $table->id();
            $table->morphs('anulable');
            $table->longText('datos_originales');
            $table->text('motivo');
            $table->unsignedInteger('anulado_por');
            $table->timestamp('fecha_anulacion')->useCurrent()->useCurrentOnUpdate();
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->unsignedInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('anulado_por');
            $table->foreign('anulado_por')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('deleted_by')->references('id')->on('users')->onDelete('set null');
        });

        // ---- 1.43 tes_cch_pendientes ----
        Schema::create('tes_cch_pendientes', function (Blueprint $table) {
            $table->id('idPendientes');
            $table->unsignedBigInteger('relCajaChica');
            $table->integer('pendiente');
            $table->date('fechaPendientes');
            $table->unsignedBigInteger('relDependencia');
            $table->decimal('montoPendientes', 15, 2);
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->unsignedInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('relCajaChica');
            $table->index('relDependencia');
            $table->foreign('relCajaChica')->references('idCajaChica')->on('tes_caja_chica')->onDelete('cascade');
            $table->foreign('relDependencia')->references('idDependencias')->on('tes_cch_dependencias')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('deleted_by')->references('id')->on('users')->onDelete('set null');
        });

        // ---- 1.44 tes_cch_movimientos ----
        Schema::create('tes_cch_movimientos', function (Blueprint $table) {
            $table->id('idMovimientos');
            $table->unsignedBigInteger('relPendiente');
            $table->date('fechaMovimientos');
            $table->string('documentos')->nullable();
            $table->decimal('rendido', 15, 2)->default(0.00);
            $table->decimal('reintegrado', 15, 2)->default(0.00);
            $table->decimal('recuperado', 15, 2)->default(0.00);
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->unsignedInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('relPendiente');
            $table->foreign('relPendiente')->references('idPendientes')->on('tes_cch_pendientes')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('deleted_by')->references('id')->on('users')->onDelete('set null');
        });

        // ---- 1.45 tes_cch_pagos ----
        Schema::create('tes_cch_pagos', function (Blueprint $table) {
            $table->id('idPagos');
            $table->unsignedBigInteger('relCajaChica_Pagos');
            $table->date('fechaEgresoPagos');
            $table->string('egresoPagos', 50)->nullable();
            $table->date('fechaEgresoEfectivoPagos')->nullable();
            $table->unsignedBigInteger('relAcreedores')->nullable();
            $table->string('conceptoPagos');
            $table->decimal('montoPagos', 15, 2);
            $table->decimal('rendidoPagos', 15, 2)->nullable();
            $table->decimal('reintegradoPagos', 15, 2)->nullable();
            $table->string('ingresoReintegroPagos')->nullable();
            $table->date('fechaRendicionPagos')->nullable();
            $table->date('fechaIngresoPagos')->nullable();
            $table->string('ingresoPagos', 50)->nullable();
            $table->string('ingresoPagosBSE', 50)->nullable();
            $table->date('fechaIngresoBSEPagos')->nullable();
            $table->decimal('recuperadoPagos', 15, 2)->default(0.00);
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->unsignedInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('relCajaChica_Pagos');
            $table->index('relAcreedores');
            $table->foreign('relCajaChica_Pagos')->references('idCajaChica')->on('tes_caja_chica')->onDelete('cascade');
            $table->foreign('relAcreedores')->references('idAcreedores')->on('tes_cch_acreedores')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('deleted_by')->references('id')->on('users')->onDelete('set null');
        });

        // ---- 1.46 tes_prendas ----
        Schema::create('tes_prendas', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');
            $table->string('nombre');
            $table->string('cedula');
            $table->string('telefono')->nullable();
            $table->string('recibo_serie');
            $table->string('recibo_numero');
            $table->date('recibo_fecha');
            $table->string('orden_cobro')->nullable();
            $table->unsignedBigInteger('medio_pago_id');
            $table->decimal('monto', 10, 2);
            $table->text('concepto');
            $table->unsignedBigInteger('planilla_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['recibo_serie', 'recibo_numero']);
            $table->index('recibo_fecha');
            $table->index('planilla_id');
            $table->index('created_by');
            $table->index('updated_by');
            $table->index('deleted_by');
            $table->foreign('medio_pago_id')->references('id')->on('tes_medio_de_pagos');
        });

        // ---- 1.47 tes_prendas_planillas ----
        Schema::create('tes_prendas_planillas', function (Blueprint $table) {
            $table->id();
            $table->string('numero');
            $table->date('fecha');
            $table->unsignedBigInteger('prenda_id');
            $table->boolean('anulada')->default(false);
            $table->dateTime('anulada_fecha')->nullable();
            $table->unsignedBigInteger('anulada_by')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['numero', 'deleted_at']);
            $table->index('prenda_id');
            $table->index('fecha');
            $table->index('anulada');
            $table->index('anulada_by');
            $table->index('created_by');
            $table->index('updated_by');
            $table->index('deleted_by');
            $table->foreign('prenda_id')->references('id')->on('tes_prendas');
        });

        // ---- 1.48 tes_deposito_vehiculos ----
        Schema::create('tes_deposito_vehiculos', function (Blueprint $table) {
            $table->id();
            $table->string('titular');
            $table->string('cedula');
            $table->string('telefono')->nullable();
            $table->string('recibo_serie');
            $table->string('recibo_numero');
            $table->date('recibo_fecha');
            $table->string('orden_cobro')->nullable();
            $table->unsignedBigInteger('medio_pago_id');
            $table->decimal('monto', 10, 2);
            $table->text('concepto');
            $table->unsignedBigInteger('planilla_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['recibo_serie', 'recibo_numero']);
            $table->index('recibo_fecha');
            $table->index('planilla_id');
            $table->index('created_by');
            $table->index('updated_by');
            $table->index('deleted_by');
            $table->foreign('medio_pago_id')->references('id')->on('tes_medio_de_pagos');
            $table->foreign('planilla_id')->references('id')->on('tes_deposito_vehiculo_planillas')->onDelete('set null');
        });

        // ---- 1.49 tes_porte_armas_planillas ----
        Schema::create('tes_porte_armas_planillas', function (Blueprint $table) {
            $table->id();
            $table->string('numero');
            $table->date('fecha');
            $table->boolean('anulada')->default(false);
            $table->dateTime('anulada_fecha')->nullable();
            $table->unsignedBigInteger('anulada_by')->nullable();
            $table->unsignedBigInteger('porte_arma_id');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['numero', 'deleted_at']);
            $table->index('porte_arma_id');
            $table->index('fecha');
            $table->index('anulada');
            $table->index('anulada_by');
            $table->index('created_by');
            $table->index('updated_by');
            $table->index('deleted_by');
            $table->foreign('porte_arma_id')->references('id')->on('tes_porte_armas');
        });

        // ---- 1.50 tes_tenencia_armas_planillas ----
        Schema::create('tes_tenencia_armas_planillas', function (Blueprint $table) {
            $table->id();
            $table->string('numero');
            $table->date('fecha');
            $table->boolean('anulada')->default(false);
            $table->dateTime('anulada_fecha')->nullable();
            $table->unsignedBigInteger('anulada_by')->nullable();
            $table->unsignedBigInteger('tenencia_arma_id');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['numero', 'deleted_at']);
            $table->index('tenencia_arma_id');
            $table->index('fecha');
            $table->index('anulada');
            $table->index('anulada_by');
            $table->index('created_by');
            $table->index('updated_by');
            $table->index('deleted_by');
            $table->foreign('tenencia_arma_id')->references('id')->on('tes_tenencia_armas');
        });

        // ---- 1.51 tes_cfes ----
        Schema::create('tes_cfes', function (Blueprint $table) {
            $table->id();
            $table->string('emisor_nombre')->nullable();
            $table->string('emisor_direccion')->nullable();
            $table->string('emisor_localidad')->nullable();
            $table->string('emisor_telefono')->nullable();
            $table->string('emisor_correo')->nullable();
            $table->string('emisor_ruc')->nullable();
            $table->string('documento_tipo')->nullable();
            $table->string('documento_serie')->nullable();
            $table->string('documento_numero')->nullable();
            $table->string('forma_pago')->nullable();
            $table->date('vencimiento')->nullable();
            $table->string('comprobante_tipo')->nullable();
            $table->string('receptor_documento_ruc')->nullable();
            $table->string('receptor_nombre_denominacion')->nullable();
            $table->string('receptor_domicilio_fiscal')->nullable();
            $table->string('periodo')->nullable();
            $table->string('nro_compra')->nullable();
            $table->date('fecha')->nullable();
            $table->string('moneda', 10)->default('UYU');
            $table->decimal('monto_no_facturable', 12, 2)->default(0.00);
            $table->decimal('monto_total', 12, 2)->default(0.00);
            $table->decimal('total_a_pagar', 12, 2)->default(0.00);
            $table->text('referencias')->nullable();
            $table->text('adenda')->nullable();
            $table->string('archivo_pdf_path')->nullable();
            $table->unsignedBigInteger('tes_caja_concepto_id')->nullable();
            $table->unsignedBigInteger('siif_distribucion_tipo_id')->nullable();
            $table->unsignedBigInteger('siif_distribucion_dependencia_id')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->unsignedInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['documento_tipo', 'documento_numero']);
            $table->index('fecha');
            $table->index('receptor_documento_ruc');
            $table->index('comprobante_tipo');
            $table->index('deleted_at');
            $table->foreign('tes_caja_concepto_id')->references('id')->on('tes_caja_conceptos')->onDelete('set null');
            $table->foreign('siif_distribucion_tipo_id')->references('id')->on('siif_distribucion_tipos')->onDelete('set null');
            $table->foreign('siif_distribucion_dependencia_id')->references('id')->on('siif_distribucion_dependencias')->onDelete('set null');
        });

        // ---- 1.52 tes_cfe_items ----
        Schema::create('tes_cfe_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tes_cfe_id');
            $table->unsignedBigInteger('siif_distribucion_id')->nullable();
            $table->text('detalle');
            $table->text('descripcion')->nullable();
            $table->decimal('cantidad', 10, 2)->default(1.00);
            $table->decimal('precio', 12, 2)->default(0.00);
            $table->decimal('descuento', 12, 2)->default(0.00);
            $table->decimal('recargo', 12, 2)->default(0.00);
            $table->decimal('importe', 12, 2)->default(0.00);
            $table->boolean('confirmado')->default(false);
            $table->unsignedBigInteger('planilla_er_id')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->unsignedInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tes_cfe_id')->references('id')->on('tes_cfes')->onDelete('cascade');
            $table->foreign('siif_distribucion_id')->references('id')->on('siif_distribucions')->onDelete('set null');
            $table->foreign('planilla_er_id')->references('id')->on('tes_planilla_ers')->onDelete('set null');
        });

        // ---- 1.53 tes_cfe_medios_pago ----
        Schema::create('tes_cfe_medios_pago', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tes_cfe_id');
            $table->string('medio_pago_tipo');
            $table->decimal('medio_pago_valor', 12, 2)->default(0.00);
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->unsignedInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tes_cfe_id')->references('id')->on('tes_cfes')->onDelete('cascade');
        });

        // ---- 1.54 tes_cfe_pendientes ----
        Schema::create('tes_cfe_pendientes', function (Blueprint $table) {
            $table->id();
            $table->enum('tipo_cfe', ['certificado_residencia', 'multas_cobradas', 'porte_armas', 'tenencia_armas', 'desconocido']);
            $table->string('serie')->nullable();
            $table->string('numero')->nullable();
            $table->date('fecha')->nullable();
            $table->decimal('monto', 10, 2)->nullable();
            $table->string('moneda', 3)->default('UYU');
            $table->longText('datos_extraidos');
            $table->longText('datos_modificados')->nullable();
            $table->string('pdf_path')->nullable();
            $table->string('source_url')->nullable();
            $table->char('pdf_hash', 64)->nullable()->unique();
            $table->string('extractor_version', 50)->nullable();
            $table->enum('estado', ['pendiente', 'en_proceso', 'en_revision', 'confirmado', 'rechazado', 'procesado', 'expirado', 'error'])->default('pendiente');
            $table->text('motivo_rechazo')->nullable();
            $table->unsignedInteger('user_id')->nullable();
            $table->unsignedInteger('procesado_por')->nullable();
            $table->timestamp('procesado_at')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->unsignedInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tipo_cfe', 'estado']);
            $table->index(['serie', 'numero', 'fecha']);
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('procesado_por')->references('id')->on('users');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('deleted_by')->references('id')->on('users')->onDelete('set null');
        });

        Schema::enableForeignKeyConstraints();
    }

    public function down()
    {
        Schema::disableForeignKeyConstraints();

        $tables = [
            'tes_cfe_pendientes', 'tes_cfe_medios_pago', 'tes_cfe_items', 'tes_cfes',
            'tes_tenencia_armas_planillas', 'tes_porte_armas_planillas',
            'tes_deposito_vehiculos', 'tes_prendas_planillas', 'tes_prendas',
            'tes_cch_pagos', 'tes_cch_movimientos', 'tes_cch_pendientes',
            'tes_anulaciones', 'tes_cheques', 'tes_planillas_cheques', 'tes_cuentas_bancarias',
            'tes_caja_conceptos', 'siif_distribucions',
            'tes_entregas_libretas_valores', 'tes_servicio_tipo_libreta', 'tes_libretas_valores',
            'tes_eventuales', 'tes_arrendamientos', 'tes_er_definiciones',
            'tes_multas_items', 'tes_multas_cobradas', 'tes_certificados_residencia',
            'tes_planilla_ers', 'siif_distribucion_tipos', 'siif_distribucion_dependencias',
            'tes_arr_planillas', 'tes_multas_303_2023', 'tes_multas',
            'tes_denominaciones_monedas', 'tes_conceptos',
            'tes_cch_dependencias', 'tes_cch_acreedores', 'tes_caja_chica',
            'tes_tenencia_armas', 'tes_porte_armas', 'tes_deposito_vehiculo_planillas',
            'tes_eventuales_planillas', 'tes_eventuales_instituciones',
            'tes_tarjetas_cobro_brou', 'tes_tipos_libretas', 'tes_servicios',
            'tes_tipos_monedas', 'tes_medio_de_pagos', 'tes_instituciones_222',
            'tes_categorias_222', 'tes_bancos', 'external_download_logs',
            'users', 'modulos', 'sessions',
        ];

        foreach ($tables as $table) {
            Schema::dropIfExists($table);
        }

        Schema::enableForeignKeyConstraints();
    }
};
