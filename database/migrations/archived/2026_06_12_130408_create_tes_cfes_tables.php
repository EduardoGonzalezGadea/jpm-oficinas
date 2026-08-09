<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('tes_cfes', function (Blueprint $table) {
            $table->id();
            // Datos del Emisor
            $table->string('emisor_nombre')->nullable();
            $table->string('emisor_direccion')->nullable();
            $table->string('emisor_localidad')->nullable();
            $table->string('emisor_telefono')->nullable();
            $table->string('emisor_correo')->nullable();
            $table->string('emisor_ruc')->nullable();
            
            // Datos del Documento
            $table->string('documento_tipo')->nullable(); // Ej: 101, 111
            $table->string('documento_serie')->nullable();
            $table->string('documento_numero')->nullable();
            $table->string('forma_pago')->nullable();
            $table->date('vencimiento')->nullable(); // Solo eFactura
            $table->string('comprobante_tipo')->nullable(); // Ej: e-Ticket Cobranza
            
            // Datos del Receptor
            $table->string('receptor_documento_ruc')->nullable();
            $table->string('receptor_nombre_denominacion')->nullable();
            $table->string('receptor_domicilio_fiscal')->nullable();
            
            // Otros
            $table->string('periodo')->nullable();
            $table->string('nro_compra')->nullable(); // Solo eFactura
            $table->date('fecha')->nullable();
            $table->string('moneda', 10)->default('UYU');
            
            // Totales
            $table->decimal('monto_no_facturable', 12, 2)->default(0);
            $table->decimal('monto_total', 12, 2)->default(0);
            $table->decimal('total_a_pagar', 12, 2)->default(0);
            
            // Otra Información
            $table->text('referencias')->nullable();
            $table->text('adenda')->nullable();
            
            $table->string('archivo_pdf_path')->nullable();
            
            $table->timestamps();
        });

        Schema::create('tes_cfe_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tes_cfe_id')->constrained('tes_cfes')->onDelete('cascade');
            $table->text('detalle');
            $table->text('descripcion')->nullable();
            $table->decimal('cantidad', 10, 2)->default(1);
            $table->decimal('precio', 12, 2)->default(0);
            $table->decimal('descuento', 12, 2)->default(0);
            $table->decimal('recargo', 12, 2)->default(0);
            $table->decimal('importe', 12, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('tes_cfe_medios_pago', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tes_cfe_id')->constrained('tes_cfes')->onDelete('cascade');
            $table->string('medio_pago_tipo');
            $table->decimal('medio_pago_valor', 12, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('tes_cfe_medios_pago');
        Schema::dropIfExists('tes_cfe_items');
        Schema::dropIfExists('tes_cfes');
    }
};
