<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SchemaDumpLegacyCommand extends Command
{
    protected $signature = 'schema:dump-legacy {--force : Sobrescribe sin confirmar}';

    protected $description = 'Regenera database/schema/legacy-schema.raw desde la BD activa (squashed schema)';

    public function handle(): int
    {
        $db = DB::connection()->getDatabaseName();

        if (empty($db) || str_contains($db, '_test')) {
            $this->error("La BD activa es de test ({$db}). Ejecuta este comando en la BD de desarrollo/producción.");
            return self::FAILURE;
        }

        $this->info("Regenerando dump legacy-schema.raw desde BD: {$db}");

        if (!$this->option('force') && !$this->confirm('¿Continuar? Esto sobrescribirá database/schema/legacy-schema.raw')) {
            $this->line('Abortado.');
            return self::SUCCESS;
        }

        $pdo = DB::connection()->getPdo();

        $tables = $pdo->query("SHOW TABLES")->fetchAll(\PDO::FETCH_COLUMN);

        $out = [
            "-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)",
            "-- Regenerado el " . date('Y-m-d H:i:s') . " desde la BD {$db}",
            "--",
            "/*!40101 SET NAMES utf8mb4 */;",
            "/*!40014 SET FOREIGN_KEY_CHECKS=0 */;",
            "",
        ];

        foreach ($tables as $t) {
            if ($t === 'migrations') {
                continue;
            }
            $out[] = "--";
            $out[] = "-- Table structure for table `{$t}`";
            $out[] = "--";
            $out[] = "DROP TABLE IF EXISTS `{$t}`;";
            $row = $pdo->query("SHOW CREATE TABLE `{$t}`")->fetch(\PDO::FETCH_ASSOC);
            $out[] = $row['Create Table'] . ';';
            $out[] = "";
        }

        $out[] = "--";
        $out[] = "-- Migraciones legacy previas al squashed schema (marcadas como aplicadas";
        $out[] = "-- por la migración 2026_07_03_200000_create_all_tables, no desde este dump).";
        $out[] = "-- Se conservan aquí como referencia; el INSERT lo gestiona la migración.";
        $out[] = "--";
        $migs = $pdo->query("SELECT id, migration, batch FROM migrations ORDER BY id")->fetchAll(\PDO::FETCH_ASSOC);
        $pending = [];
        foreach ($migs as $m) {
            if ($m['migration'] === '2026_07_03_200000_create_all_tables') {
                $pending = [];
                continue;
            }
            $pending[] = $m;
        }
        $out[] = "-- " . count($pending) . " migraciones posteriores al squashed a registrar.";
        $out[] = "";
        $out[] = "/*!40014 SET FOREIGN_KEY_CHECKS=1 */;";

        file_put_contents(database_path('schema/legacy-schema.raw'), implode("\n", $out));
        file_put_contents(
            database_path('schema/legacy-migrations.json'),
            json_encode(array_map(fn ($m) => [
                'migration' => $m['migration'],
                'batch' => (int) $m['batch'],
            ], $pending), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );

        $this->info("Dump legacy-schema.raw regenerado con " . (count($tables) - 1) . " tablas (excluye `migrations`).");
        $this->info("Generado legacy-migrations.json con " . count($pending) . " migraciones posteriores al squashed.");
        $this->line("\nRecuerda:");
        $this->line("  - Recrear la BD de test con: composer test:setup");
        $this->line("  - Si la BD test ya está parcialmente cargada, dropea todas las tablas y luego migrate.");

        return self::SUCCESS;
    }
}
