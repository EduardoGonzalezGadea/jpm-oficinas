<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        DB::statement("ALTER TABLE tes_cfe_pendientes MODIFY COLUMN estado ENUM(
            'pendiente', 'en_proceso', 'en_revision', 'confirmado',
            'rechazado', 'procesado', 'expirado', 'error'
        ) NOT NULL DEFAULT 'pendiente'");
    }

    public function down()
    {
        DB::statement("ALTER TABLE tes_cfe_pendientes MODIFY COLUMN estado ENUM(
            'pendiente', 'confirmado', 'rechazado', 'procesado',
            'en_revision', 'expirado'
        ) NOT NULL DEFAULT 'pendiente'");
    }
};
