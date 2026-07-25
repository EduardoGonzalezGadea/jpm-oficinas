<?php

$content = file_get_contents('missing_tables.txt');

// Check for UTF-16LE BOM and decode
if (strpos($content, "\xFF\xFE") === 0) {
    $content = substr($content, 2);
    $content = mb_convert_encoding($content, 'UTF-8', 'UTF-16LE');
} else {
    // If it's pure UTF-16LE without BOM but has null bytes
    $content = mb_convert_encoding($content, 'UTF-8', 'UTF-16LE');
}

$pos = strpos($content, "Schema::create");
if ($pos !== false) {
    $content = substr($content, $pos);
}

$file = 'database/migrations/2026_07_03_200000_create_all_tables.php';
$original = file_get_contents($file);

$replacement = $content . "\n        Schema::enableForeignKeyConstraints();";
$original = str_replace('Schema::enableForeignKeyConstraints();', $replacement, $original);

file_put_contents($file, $original);
echo "Successfully decoded and appended missing tables.\n";
