<?php
require 'vendor/autoload.php';

use Smalot\PdfParser\Parser;

$filename = 'docs/CFEs/CFE_214988770019_101_A_236.pdf';
$parser = new Parser();
$pdf = $parser->parseFile($filename);
$text = $pdf->getText();

echo "--- START TEXT ---\n";
echo $text;
echo "\n--- END TEXT ---\n";
file_put_contents('pdf_debug.txt', $text);
