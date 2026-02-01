<?php
require 'vendor/autoload.php';

use Smalot\PdfParser\Parser;

$filename = 'docs/CFEs/CFE_214988770019_101_A_202.pdf';
if (!file_exists($filename)) {
    die("File not found: $filename");
}

$parser = new Parser();
$pdf = $parser->parseFile($filename);
$text = $pdf->getText();

file_put_contents('pdf_debug_202.txt', $text);
echo "Text extracted to pdf_debug_202.txt\n";
