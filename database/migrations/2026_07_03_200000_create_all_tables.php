<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateAllTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Squashed migration: carga el esquema completo desde legacy-schema.raw
        // (CREATE TABLE de todas las tablas del dominio, sin tocar `migrations`).
        $sqlPath = database_path('schema/legacy-schema.raw');
        if (!file_exists($sqlPath)) {
            throw new Exception("legacy-schema.raw not found!");
        }
        DB::unprepared(file_get_contents($sqlPath));

        // Marca como aplicadas las migraciones posteriores al squashed schema,
        // listadas en legacy-migrations.json. Sin esto, Laravel las re-ejecutaría
        // y chocarían con las columnas/tablas ya creadas por el dump.
        $jsonPath = database_path('schema/legacy-migrations.json');
        if (file_exists($jsonPath)) {
            $migs = json_decode(file_get_contents($jsonPath), true) ?: [];
            $batch = ((int) DB::table('migrations')->max('batch')) + 1;
            foreach ($migs as $m) {
                $exists = DB::table('migrations')
                    ->where('migration', $m['migration'])
                    ->exists();
                if (!$exists) {
                    DB::table('migrations')->insert([
                        'migration' => $m['migration'],
                        'batch' => $batch,
                    ]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // handled by migrate:fresh
    }
}
