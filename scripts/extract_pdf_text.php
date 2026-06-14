<?php
require_once __DIR__ . '/../vendor/autoload.php';

$parser = new \Smalot\PdfParser\Parser();
$pdf = $parser->parseFile($argv[1]);
echo $pdf->getText();
