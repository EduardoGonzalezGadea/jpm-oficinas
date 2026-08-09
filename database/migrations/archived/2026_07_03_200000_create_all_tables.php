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
        // Squashed migration: Loads the exact state of the production database
        $sqlPath = database_path('schema/legacy-schema.raw');
        if (file_exists($sqlPath)) {
            DB::unprepared(file_get_contents($sqlPath));
        } else {
            throw new Exception("legacy-schema.sql not found!");
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
