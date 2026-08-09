<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tes_multas_303_2023', function (Blueprint $table) {
            $table->id();
            $table->string('grupo', 255);
            $table->string('codigo', 50);
            $table->text('descripcion');
            $table->string('valor_ur', 100);
            
            // Audit fields required by Auditable trait
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('grupo');
            $table->index('codigo');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tes_multas_303_2023');
    }
};
