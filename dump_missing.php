<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$allDbTables = Schema::getConnection()->getDoctrineSchemaManager()->listTableNames();
$migrationFile = file_get_contents(__DIR__.'/database/migrations/2026_07_03_200000_create_all_tables.php');

$missingTables = [];
foreach ($allDbTables as $table) {
    if (strpos($table, 'tes_') !== 0) continue;
    if (in_array($table, ['tes_lb_tipos', 'tes_lb_conceptos', 'tes_lb_detalle', 'tes_lb_medios', 'tes_libro_diario'])) continue;
    
    if (strpos($migrationFile, "Schema::create('$table'") === false && strpos($migrationFile, "Schema::create(\"$table\"") === false) {
        $missingTables[] = $table;
    }
}

$output = "";

foreach ($missingTables as $table) {
    $output .= "\n\n        Schema::create('$table', function (Blueprint \$table) {\n";
    $columns = DB::select("SHOW COLUMNS FROM `$table`");
    foreach ($columns as $column) {
        $type = strtolower($column->Type);
        $name = $column->Field;
        if ($name === 'id' && strpos($type, 'bigint') !== false) {
            $output .= "            \$table->id();\n";
            continue;
        }
        if ($name === 'created_at') {
            $output .= "            \$table->timestamps();\n";
            continue;
        }
        if ($name === 'updated_at') continue;
        if ($name === 'deleted_at') {
            $output .= "            \$table->softDeletes();\n";
            continue;
        }
        
        $line = "            \$table->";
        if (strpos($type, 'int') !== false) {
            if (strpos($type, 'bigint') !== false) {
                $line .= "bigInteger('$name')";
            } else {
                $line .= "integer('$name')";
            }
            if (strpos($type, 'unsigned') !== false) {
                $line = str_replace("integer(", "unsignedInteger(", $line);
                $line = str_replace("bigInteger(", "unsignedBigInteger(", $line);
            }
        } elseif (strpos($type, 'varchar') !== false) {
            preg_match('/varchar\((\d+)\)/', $type, $matches);
            $len = $matches[1] ?? 255;
            $line .= "string('$name', $len)";
        } elseif (strpos($type, 'text') !== false) {
            $line .= "text('$name')";
        } elseif (strpos($type, 'date') !== false && strpos($type, 'datetime') === false) {
            $line .= "date('$name')";
        } elseif (strpos($type, 'datetime') !== false) {
            $line .= "dateTime('$name')";
        } elseif (strpos($type, 'timestamp') !== false) {
            $line .= "timestamp('$name')";
        } elseif (strpos($type, 'decimal') !== false) {
            preg_match('/decimal\((\d+),(\d+)\)/', $type, $matches);
            $m = $matches[1] ?? 8;
            $d = $matches[2] ?? 2;
            $line .= "decimal('$name', $m, $d)";
        } elseif (strpos($type, 'enum') !== false) {
            preg_match('/enum\((.*)\)/', $type, $matches);
            $vals = $matches[1] ?? '';
            $line .= "enum('$name', [$vals])";
        } else {
            $line .= "string('$name')";
        }
        
        if ($column->Null === 'YES') {
            $line .= "->nullable()";
        }
        $output .= "$line;\n";
    }
    $output .= "        });\n";
}

$file = 'database/migrations/2026_07_03_200000_create_all_tables.php';
$original = file_get_contents($file);
$replacement = $output . "\n        Schema::enableForeignKeyConstraints();";
$original = str_replace('Schema::enableForeignKeyConstraints();', $replacement, $original);
file_put_contents($file, $original);

echo "Appended " . count($missingTables) . " missing tables directly to migration file.\n";
