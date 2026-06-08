<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$parser = new Smalot\PdfParser\Parser();
$pdf = $parser->parseFile(__DIR__ . '/docs/CFE_214988770019_101_A_118.pdf');
$texto = $pdf->getText();

$extractor = new \App\Services\CfeExtractor\ArmasExtractor();
$datos = $extractor->extraer($texto);

print_r($datos);
